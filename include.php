<?
use \Bitrix\Main;
use \Bitrix\Sale\Order;
use \Bitrix\Sale\Payment;
use \Bitrix\Sale\PaySystem;
use \Bitrix\Sale\PriceMaths;
use \Bitrix\Sale\Result;
use \Bitrix\Main\Localization;
use \Bitrix\Main\Web\HttpClient;

class CMixplatPayment {

	static $module_id = "mixplat.payment";

	static $errorMsg = '';

	static function getModuleId() {
		return self::$module_id;
	}


	static function send($url, array $params = array()) {
		$result = new PaySystem\ServiceResult();

		$httpClient = new HttpClient();

		$postData = null;
		if ($params) {
			$postData = static::JSencode($params);
		}

		if (class_exists('Bitrix\Sale\PaySystem\Logger')) {
			PaySystem\Logger::addDebugInfo('Mixplat: url: ' . $url);
			PaySystem\Logger::addDebugInfo('Mixplat: request data: ' . $postData);
		}
		$response = $httpClient->post($url, $postData);

		if ($response === false) {
			$errors = $httpClient->getError();
			foreach ($errors as $code => $message) {
				$result->addError(new Main\Error($message, $code));
			}

			return $result;
		}

		if (class_exists('Bitrix\Sale\PaySystem\Logger')) {
			PaySystem\Logger::addDebugInfo('Mixplat: response data: ' . $response);
		}

		$response = static::JSdecode($response);

		$httpStatus = $httpClient->getStatus();

		if ($httpStatus == 200) {
			$result->setData($response);
			if ($response['result'] != "ok") {
				$result->addError(new Main\Error($response['error_description']));
			}
		} else {
			$result->addError(new Main\Error(Localization\Loc::getMessage('MIXPLAT.PAYMENT_CONNECTION_ERROR_CODE')." $httpStatus"));
		}

		return $result;
	}
	/*
	 * @param array $data
	 * @return mixed
	 * @throws Main\ArgumentException
	 */
	public static function JSencode(array $data)
	{
		return Main\Web\Json::encode($data, JSON_UNESCAPED_UNICODE);
	}

	/**
	 * @param string $data
	 * @return mixed
	 */
	public static function JSdecode($data)
	{
		try
		{
			return Main\Web\Json::decode($data);
		} catch (Main\ArgumentException $exception) {
			return false;
		}
	}

	static function insertTransaction(Payment $payment, $data, $params) {
		global $DB;
		$sum = PriceMaths::roundPrecision($payment->getSum());
		$collection = $payment->getCollection();
		$order = $collection->getOrder();
		$orderId = $order->getId();
		$paymentId = $payment->getId();
		$id = $data['payment_id'];
		$DB->Query("INSERT INTO mixplat_payment (id, payment_id, order_id, amount, status, date) VALUES ('".$DB->ForSql($id)."','$paymentId','".$DB->ForSql($orderId)."','".$DB->ForSql($sum)."', 'new',now())");
		return $DB->LastID();
	}

	static function updateTransaction(Payment $payment, $data) {
		global $DB;
		$DB->Query("UPDATE mixplat_payment SET
			status='".$DB->ForSql($data['status'])."',
			status_extended='".$DB->ForSql($data['status_extended'])."',
			extra = '".$DB->ForSql(self::JSencode($data))."'
			WHERE id='".$DB->ForSql($data['payment_id'])."'");

	}

	static function getTransactionList($filter) {
		global $DB;
		$where = array();
		foreach($filter as $key=>$item) {
			$where[] = "$key = '".$DB->ForSql($item)."'";
		}
		if($where) {
			$where = ' AND '.implode(" AND ", $where);
		} else {
			$where = '';
		}
		$sql = "SELECT * FROM mixplat_payment WHERE status!='new' $WHERE order by order_id desc, `date` desc";
		$dbRes = $DB->Query($sql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		return $dbRes;
	}

	static function proccessAdminAction($arPost) {
		global $DB;
		$res = false;
		$result = new Result();
		foreach($arPost['action'] as $id => $action) {
			$sum     = $arPost['sum'][$id];
			$res = $DB->Query("SELECT * FROM mixplat_payment WHERE id='".$DB->ForSql($id)."'");
			$data = $res->Fetch();
			$order = Order::load($data['order_id']);
			$payment = $order->getPaymentCollection()->getItemById($data['payment_id']);
			$service = PaySystem\Manager::getObjectById($payment->getPaymentSystemId());
			switch($action) {
				case 'cancel':
					$canResult = $service->cancel($payment);
					if (!$canResult->isSuccess())
					{
						return $result->addErrors($canResult->getErrors());
					}
					$DB->Query("UPDATE mixplat_payment SET status='failure', status_extended='failure_canceled_by_merchant' WHERE id='".$DB->ForSql($id)."'");
					$r = $payment->setPaid('N');

					$payment->setFields($canResult->getPsData());
					break;
				case 'confirm':
					$conResult = $service->confirm($payment, $sum);
					if (!$conResult->isSuccess())
					{
						return $result->addErrors($conResult->getErrors());
					}
					$DB->Query("UPDATE mixplat_payment SET status='success' WHERE id='".$DB->ForSql($id)."'");
					$payment->setField('PAID','N');
					$payment->setField('SUM', $sum);
					$payment->setField('PAID','Y');
					$payment->setFields($conResult->getPsData());
				break;
				case 'return':
					$sum = $payment->getSum();
					$refResult = $service->refund($payment, $sum);
					if (!$refResult->isSuccess())
					{
						return $result->addErrors($refResult->getErrors());
					}
					$resultSum = $payment->getSum() - $sum;
					if ($resultSum == 0){
						$payment->setField('PAID', 'N');

					} else {
						$payment->setField('PAID','N');
						$payment->setField('SUM', $resultSum);
						$payment->setField('PAID','Y');
					}
					$payment->setFields($refResult->getPsData());
					break;
			}
			$r = $order->getPaymentCollection()->save();
			if (!$r->isSuccess())
			{
				$result->addErrors($r->getErrors());
				return $result;
			}
			//$order->refreshData();
			$order->save();
			return $result;

		}
		return $result;
	}

}

?>