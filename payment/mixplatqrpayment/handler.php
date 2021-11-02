<?php

namespace Sale\Handlers\PaySystem;

use Bitrix\Main;
use Bitrix\Main\Localization;
use Bitrix\Main\Request;
use Bitrix\Sale\Payment;
use Bitrix\Sale\PaySystem;
use Bitrix\Sale\PriceMaths;

Localization\Loc::loadMessages(__FILE__);

\CModule::IncludeModule("mixplat.payment");

if (!(interface_exists("Sale\Handlers\PaySystem\MixplatHandlerProxy"))) {

	if (interface_exists("\Bitrix\Sale\PaySystem\IPartialHold")) {
		interface MixplatHandlerProxy extends PaySystem\IRefund, PaySystem\IPartialHold
		{}
	} else {
		interface MixplatHandlerProxy extends PaySystem\IRefund, PaySystem\IHold
		{}
	}
}



/**
 * Class MixplatPaymentHandler
 * @package Sale\Handlers\PaySystem
 */
class MixplatQrPaymentHandler extends PaySystem\ServiceHandler implements MixplatHandlerProxy
{

	const URL = 'https://api.mixplat.com';

	const
	PAYMENT_METHOD_MOBILE = 'mobile',
	PAYMENT_METHOD_CARD   = 'card',
	PAYMENT_METHOD_WALLET = 'wallet',
	PAYMENT_METHOD_BANK   = 'bank';

	public $email = false;

	/**
	 * @param Payment $payment
	 * @param Request|null $request
	 * @return PaySystem\ServiceResult
	 * @throws Main\ArgumentNullException
	 * @throws Main\ArgumentOutOfRangeException
	 * @throws Main\ArgumentTypeException
	 * @throws Main\ObjectException
	 */
	public function initiatePay(Payment $payment, Request $request = null)
	{
		if ($request === null) {
			$request = Main\Context::getCurrent()->getRequest();
		}

		if ($request->get('template') == 'qr') {
			$template = "template_qr";
			$params = array(
				'SUM'      => PriceMaths::roundPrecision($payment->getSum()),
			);
			$this->setExtraParams($params);

			$showTemplateResult = $this->showTemplate($payment, $template);
			exit();
		}

		$result = $this->initiatePayInternal($payment, $request);
		if (!$result->isSuccess()) {
			$error = 'Mixplat payment: initiatePay: ' . join('\n', $result->getErrorMessages());
			if (class_exists('Bitrix\Sale\PaySystem\Logger')) {
				PaySystem\Logger::addError($error);
			}
		}

		return $result;
	}

	/**
	 * @param Payment $payment
	 * @param Request $request
	 * @return PaySystem\ServiceResult
	 * @throws Main\ArgumentNullException
	 */
	private function initiatePayInternal(Payment $payment, Request $request)
	{
		$this->email = $this->getBusinessValue($payment, 'BUYER_PERSON_EMAIL');
		if (!$this->email) {
			$collection = $payment->getCollection();
			$order      = $collection->getOrder();
			try {
				$this->email = $order->getPropertyCollection()->getUserEmail()->getValue();
			} catch (\Exception $e) {}
		}

		$result = new PaySystem\ServiceResult();

		/*$createResult = $this->createPayment($payment, $request);
		if (!$createResult->isSuccess()) {
			$result->addErrors($createResult->getErrors());
			return $result;
		}

		$paymentData = $createResult->getData();

		$result->setPsData(array(
			'PS_INVOICE_ID'   => $paymentData['payment_id'],
			'PAY_VOUCHER_NUM' => $this->email,
		));
*/
		$orderNumber = $this->getBusinessValue($payment, 'ORDER_NUMBER');

		$params = array(
			'URL'      => "/personal/order/payment/?template=qr&ORDER_ID=$orderNumber&PAYMENT_ID=$orderNumber/".$payment->getId(),
			'CURRENCY' => $payment->getField('CURRENCY'),
			'SUM'      => PriceMaths::roundPrecision($payment->getSum()),
		);
		$this->setExtraParams($params);

		$template = "template";

		$showTemplateResult = $this->showTemplate($payment, $template);
		if ($showTemplateResult->isSuccess()) {
			$result->setTemplate($showTemplateResult->getTemplate());
		} else {
			$result->addErrors($showTemplateResult->getErrors());
		}

		return $result;
	}

	/**
	 * @param Payment $payment
	 * @param Request $request
	 * @return PaySystem\ServiceResult
	 * @throws Main\ArgumentException
	 * @throws Main\ArgumentNullException
	 */
	private function createPayment(Payment $payment, Request $request)
	{
		$result = new PaySystem\ServiceResult();

		$url = $this->getUrl($payment, 'pay');

		$params = $this->getPaymentQueryParams($payment, $request);

		$sendResult = $this->send($url, $params);
		if (!$sendResult->isSuccess()) {
			$result->addErrors($sendResult->getErrors());
			return $result;
		}

		$response = $sendResult->getData();
		\CMixplatPayment::insertTransaction($payment, $response, $params);
		$result->setData($response);

		return $result;
	}

	/**
	 * @param $url
	 * @param array $params
	 * @return PaySystem\ServiceResult
	 * @throws Main\ArgumentException
	 */
	private function send($url, array $params = array())
	{
		return \CMixplatPayment::send($url, $params);
	}

	/**
	 * @param Payment $payment
	 * @param Request $request
	 * @return array
	 * @throws Main\ArgumentNullException
	 */
	private function getPaymentQueryParams(Payment $payment, Request $request)
	{
		$orderId          = $payment->getCollection()->getOrder()->getId();
		$host             = $request->isHttps() ? 'https' : 'http';
		$successUrl       = $host . '://' . $request->getHttpHost() . '/bitrix/tools/mixplat_success.php?order_id=' . $orderId;
		$failUrl          = $host . '://' . $request->getHttpHost() . '/bitrix/tools/mixplat_fail.php?order_id=' . $orderId;
		$notifyUrl        = $host . '://' . $request->getHttpHost() . '/bitrix/tools/sale_ps_result.php';
		$paymentShouldPay = PriceMaths::roundPrecision($payment->getSum());
		$query            = array(
			'api_version'         => '3',
			'amount'              => intval($paymentShouldPay * 100),
			'test'                => $this->getBusinessValue($payment, 'TEST') === '2' ? 1 : 0,
			'project_id'          => $this->getBusinessValue($payment, 'PROJECT_ID'),
			'payment_form_id'     => $this->getBusinessValue($payment, 'FORM_ID'),
			'request_id'          => $this->getIdempotenceKey(),
			'merchant_payment_id' => $payment->getId(),
			'user_email'          => $this->email,
			'url_success'         => $successUrl,
			'url_failure'         => $failUrl,
			'notify_url'          => $notifyUrl,
			'payment_scheme'      => $this->getBusinessValue($payment, 'HOLD') === '2' ? 'dms' : 'sms',
			'description'         => $this->getPaymentDescription($payment),
			'merchant_data'       => \CMixplatPayment::JSencode(array(
				'BX_PAYMENT_NUMBER' => $payment->getId(),
				'BX_PAYSYSTEM_CODE' => $this->service->getField('ID'),
				'BX_HANDLER'        => 'MIXPLAT',
			)),
		);

		$query['signature'] = $this->getCreateSignature($query, $this->getBusinessValue($payment, 'API_KEY'));

		if ($this->service->getField('PS_MODE')) {
			$query['payment_method'] = $this->service->getField('PS_MODE');
		}
		if ($this->getBusinessValue($payment, 'RECEIPT') === '2') {
			$query['items'] = $this->getReceiptItems($payment);
		}

		return $query;
	}

	/**
	 * @param string $query
	 * @param string $apiKey
	 * @return string
	 */
	private function getCreateSignature($query, $apiKey)
	{
		return md5($query['request_id'] . $query['project_id'] . $query['merchant_payment_id'] . $apiKey);
	}

	/**
	 * @return string
	 */
	private function getIdempotenceKey()
	{
		return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
			mt_rand(0, 0xffff), mt_rand(0, 0xffff),
			mt_rand(0, 0xffff),
			mt_rand(0, 0x0fff) | 0x4000,
			mt_rand(0, 0x3fff) | 0x8000,
			mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
		);
	}

	/**
	 * @param Payment $payment
	 * @return array
	 */
	private function getReceiptItems(Payment $payment)
	{
		$items              = array();
		$shipmentCollection = $payment
			->getCollection()
			->getOrder()
			->getShipmentCollection();

		$productVat             = intval($this->getBusinessValue($payment, 'PRODUCT_NDS'));
		$deliveryVat            = intval($this->getBusinessValue($payment, 'DELIVERY_NDS'));
		$paymentSubject         = $this->getPaymentSubject($payment);
		$paymentSubjectDelivery = $this->getPaymentSubjectDelivery($payment);
		$paymentMode            = $this->getPaymentMode($payment);

		foreach ($shipmentCollection as $shipment) {
			$shipmentItemCollection = $shipment->getShipmentItemCollection();

			foreach ($shipmentItemCollection as $shipmentItem) {
				$basketItem = $shipmentItem->getBasketItem();
				if ($basketItem->isBundleChild()) {
					continue;
				}

				if (!$basketItem->getFinalPrice()) {
					continue;
				}

				if ($shipmentItem->getQuantity() <= 0) {
					continue;
				}

				$items[] = array(
					"name"     => substr($basketItem->getField('NAME'), 0, 128),
					"quantity" => $shipmentItem->getQuantity(),
					"sum"      => intval(\Bitrix\Sale\PriceMaths::roundPrecision($basketItem->getFinalPrice()) * 100),
					"vat"      => $productVat ? $productVat : $this->getProductVat($basketItem),
					"method"   => $paymentMode,
					"object"   => $paymentSubject,
				);
			}
			if ($noDelivery != 2 && !$shipment->isSystem() && $shipment->getPrice()) {

				$items[] = array(
					"name"     => substr($shipment->getDeliveryName(), 0, 128),
					"quantity" => 1,
					"sum"      => intval(\Bitrix\Sale\PriceMaths::roundPrecision($shipment->getPrice()) * 100),
					"vat"      => $deliveryVat ? $deliveryVat : $this->getShipmentVat($shipment),
					"method"   => $paymentMode,
					"object"   => $paymentSubjectDelivery,
				);
			}
		}

		$total = intval(PriceMaths::roundPrecision($payment->getSum() * 100));

		$items = $this->normalizeReceiptItems($items, $total);

		return $items;
	}

	private function normalizeReceiptItems($items, $total)
	{
		$result    = array();
		$realTotal = 0;
		foreach ($items as $item) {
			$realTotal += $item['sum'];
		}
		if (abs($realTotal - $total) > 0.0001) {
			$subtotal = 0;
			$coef     = $total / $realTotal;
			$lastItem = count($items) - 1;
			foreach ($items as $id => $item) {
				if ($id == $lastItem) {
					$sum         = $total - $subtotal;
					$item['sum'] = $sum;
					$result[]    = $item;
				} else {
					$sum         = intval(round($item['sum'] * $coef));
					$item['sum'] = $sum;
					$subtotal += $sum;
					$result[] = $item;
				}
			}
		} else {
			$result = $items;
		}
		return $result;
	}

	private function getProductVat($item)
	{
		if (\Bitrix\Main\Loader::includeModule('catalog')) {
			$dbRes  = \CCatalogProduct::GetVATInfo($item->getProductId());
			$ndsArr = $dbRes->Fetch();
			$taxId  = $this->convertVatId($ndsArr['ID']);
			return $taxId;
		}
		return 'none';
	}

	private function getShipmentVat($shipment)
	{
		$delivery = \Bitrix\Sale\Delivery\Services\Manager::getById($shipment->getDeliveryId());
		if (is_null($delivery['VAT_ID'])) {
			return 'none';
		}
		return $this->convertVatId($delivery['VAT_ID']);
	}

	private function convertVatId($vatId)
	{
		$ndsArr = \CCatalogVat::GetByID($vatId)->Fetch();
		if ($ndsArr['NAME'] == GetMessage('MIXPLAT.PAYMENT_QR_NO_NSD')) {
			$taxId = 'none';
		} else {
			$rate        = intval($ndsArr['RATE']);
			$vatIncluded = !isset($ndsArr['VAT_INCLUDED']) || $ndsArr['VAT_INCLUDED'] == 'Y';
			switch ($rate) {
				case 20:
					if (!$vatIncluded) {
						$taxId = 'vat120';
					} else {
						$taxId = 'vat20';
					}

					break;
				case 10:
					if (!$vatIncluded) {
						$taxId = 'vat110';
						break;
					} else {
						$taxId = 'vat10';
						break;
					}

				case 0:$taxId = 'vat0';
					break;
				default:$taxId = 'none';
					break;
			}
		}
		return $taxId;
	}

	/**
	 * @param Payment $payment
	 * @return mixed
	 * @throws Main\ArgumentException
	 * @throws Main\ArgumentOutOfRangeException
	 * @throws Main\NotImplementedException
	 */
	protected function getPaymentDescription(Payment $payment)
	{
		/** @var PaymentCollection $collection */
		$collection = $payment->getCollection();
		$order      = $collection->getOrder();
		$userEmail  = $order->getPropertyCollection()->getUserEmail();

		$description = str_replace(
			[
				'#PAYMENT_NUMBER#',
				'#ORDER_NUMBER#',
				'#PAYMENT_ID#',
				'#ORDER_ID#',
				'#USER_EMAIL#',
			],
			[
				$payment->getField('ACCOUNT_NUMBER'),
				$order->getField('ACCOUNT_NUMBER'),
				$payment->getId(),
				$order->getId(),
				($userEmail) ? $userEmail->getValue() : '',
			],
			$this->getBusinessValue($payment, 'DESCRIPTION')
		);

		return substr($description, 0, 128);
	}

	public function getPaymentSubject(Payment $payment)
	{
		$paymentObject = $this->getBusinessValue($payment, 'PAYMENT_SUBJECT');
		if (!$paymentObject) {
			$paymentObject = 'commodity';
		}
		return $paymentObject;
	}

	public function getPaymentSubjectDelivery(Payment $payment)
	{
		$paymentObjectDelivery = $this->getBusinessValue($payment, 'SUBJECT_DELIVERY');
		if (!$paymentObjectDelivery) {
			$paymentObjectDelivery = 'commodity';
		}
		return $paymentObjectDelivery;
	}

	public function getPaymentMode(Payment $payment)
	{
		$paymentMethod = $this->getBusinessValue($payment, 'PAYMENT_MODE');
		if (!$paymentMethod) {
			$paymentMethod = 'full_prepayment';
		}
		return $paymentMethod;
	}

	/**
	 * @return array
	 */
	public function getCurrencyList()
	{
		return array('RUB');
	}

	private function isCorrectRequestSource()
	{
		if ($this->getBusinessValue($payment, 'FILTER_IPS') != '2') {
			return true;
		}
		$ip            = $this->getClientIpAddress();
		$mixplatIPList = explode(',', $this->getBusinessValue($payment, 'IP_LIST'));
		$mixplatIPList = array_map(function ($item) {
			return trim($item);
		}, $mixplatIPList);

		if (in_array($ip, $mixplatIPList)) {
			return true;
		}
		return false;
	}

	private function checkRequestSignature($payment, $data)
	{
		$apiKey    = $this->getBusinessValue($payment, 'API_KEY');
		$signature = $this->getActionSignature($data, $apiKey);
		return strcmp($signature, $data['signature']) === 0;
	}

	/**
	 * @param Payment $payment
	 * @param Request $request
	 * @return PaySystem\ServiceResult
	 * @throws Main\ObjectException
	 * @throws \Exception
	 *
	 */
	public function processRequest(Payment $payment, Request $request)
	{
		$result = new PaySystem\ServiceResult();

		$inputStream = static::readFromStream();

		if (class_exists('Bitrix\Sale\PaySystem\Logger')) {
			PaySystem\Logger::addDebugInfo('Mixplat: inputStream: ' . $inputStream);
		}

		if (!$this->isCorrectRequestSource()) {
			return $result->addError(new Main\Error(Localization\Loc::getMessage('MIXPLAT.PAYMENT_QR_INCORRECT_SOURCE')));
		}

		$data = \CMixplatPayment::JSdecode($inputStream);
		if ($data !== false) {
			$response = $data;
			if (!$this->checkRequestSignature($payment, $data)) {
				return $result->addError(new Main\Error(Localization\Loc::getMessage('MIXPLAT.PAYMENT_QR_WRONG_SIGNATURE')));
			}
			\CMixplatPayment::updateTransaction($payment, $response);

			if (
				(
					$response['status'] === 'success'
					|| $response['status_extended'] === 'pending_authorized'
				) && !$payment->isPaid()
			) {
				$description = Localization\Loc::getMessage('MIXPLAT.PAYMENT_QR_TRANSACTION') . $response['payment_id'];
				$fields      = array(
					"PS_STATUS_CODE"        => substr($response['status'], 0, 5),
					"PS_STATUS_DESCRIPTION" => $description,
					"PS_SUM"                => $response['amount'],
					"PS_STATUS"             => 'N',
					"PS_INVOICE_ID"         => $response['payment_id'],
					"PS_RESPONSE_DATE"      => new Main\Type\DateTime(),
					"PS_STATUS_MESSAGE"     => $this->getPaymentInfo($response),
				);

				if ($this->isSumCorrect($payment, $response)) {
					$fields["PS_STATUS"] = 'Y';
					$result->setOperationType(PaySystem\ServiceResult::MONEY_COMING);
				} else {
					$error = Localization\Loc::getMessage('MIXPLAT.PAYMENT_QR_ERROR_SUM');
					$fields['PS_STATUS_DESCRIPTION'] .= ' ' . $error;
					$result->addError(new Main\Error($error));
				}

				$result->setPsData($fields);
			}
		} else {
			$result->addError(new Main\Error('MIXPLAT.PAYMENT_QR_ERROR_QUERY'));
		}

		if (!$result->isSuccess()) {
			$error = 'Mixplat: processRequest: ' . join('\n', $result->getErrorMessages());
			if (class_exists('Bitrix\Sale\PaySystem\Logger')) {
				PaySystem\Logger::addError($error);
			}
		}

		return $result;
	}

	private function getPaymentInfo($response)
	{
		$key = false;
		if (isset($response['card'])) {
			$key = 'card';
		}
		if (isset($response['mobile'])) {
			$key = 'mobile';
		}
		if (isset($response['wallet'])) {
			$key = 'wallet';
		}
		if (isset($response['bank'])) {
			$key = 'bank';
		}

		if ($key) {
			return Main\Web\Json::encode($response[$key], JSON_UNESCAPED_UNICODE);
		}

		return '';
	}

	/**
	 * @param Payment $payment
	 * @param array $paymentData
	 * @return bool
	 * @throws Main\ArgumentNullException
	 * @throws Main\ArgumentOutOfRangeException
	 * @throws Main\ObjectException
	 */
	private function isSumCorrect(Payment $payment, array $paymentData)
	{
		if (class_exists('Bitrix\Sale\PaySystem\Logger')) {
			PaySystem\Logger::addDebugInfo(
				'Mixplat: Sum=' . PriceMaths::roundPrecision($paymentData['amount']) . "; paymentSum=" . PriceMaths::roundPrecision($payment->getSum())
			);
		}

		return PriceMaths::roundPrecision($paymentData['amount']) === PriceMaths::roundPrecision($payment->getSum() * 100);
	}

	/**
	 * @param Request $request
	 * @return mixed
	 */
	public function getPaymentIdFromRequest(Request $request)
	{
		$inputStream = static::readFromStream();

		if ($inputStream) {
			$data = \CMixplatPayment::JSdecode($inputStream);
			if ($data === false) {
				return false;
			}

			$merchantData = \CMixplatPayment::JSdecode($data['merchant_data']);

			return $merchantData['BX_PAYMENT_NUMBER'];

		}

		return false;
	}

	/**
	 * @return array
	 */
	public static function getHandlerModeList()
	{
		return array(
			''                            => Localization\Loc::getMessage('MIXPLAT.PAYMENT_QR_METHOD_ALL'),
			static::PAYMENT_METHOD_MOBILE => Localization\Loc::getMessage('MIXPLAT.PAYMENT_QR_METHOD_MOBILE'),
			static::PAYMENT_METHOD_CARD   => Localization\Loc::getMessage('MIXPLAT.PAYMENT_QR_METHOD_CARD'),
			static::PAYMENT_METHOD_WALLET => Localization\Loc::getMessage('MIXPLAT.PAYMENT_QR_METHOD_WALLET'),
			static::PAYMENT_METHOD_BANK   => Localization\Loc::getMessage('MIXPLAT.PAYMENT_QR_METHOD_BANK'),
		);
	}

	/**
	 * @return array
	 */
	protected function getUrlList()
	{
		return array(
			'pay'     => static::URL . '/create_payment_form',
			'refund'  => static::URL . '/refund_payment',
			'confirm' => static::URL . '/confirm_payment',
			'cancel'  => static::URL . '/cancel_payment',
		);
	}

	/**
	 * @param Request $request
	 * @param int $paySystemId
	 * @return bool
	 * @throws Main\ArgumentNullException
	 * @throws Main\ArgumentOutOfRangeException
	 * @throws Main\ArgumentTypeException
	 * @throws Main\ObjectException
	 */
	public static function isMyResponse(Request $request, $paySystemId)
	{
		$inputStream = static::readFromStream();

		if ($inputStream) {
			if (class_exists('Bitrix\Sale\PaySystem\Logger')) {
				PaySystem\Logger::addDebugInfo('Mixplat: Check my response: paySystemId=' . $paySystemId . ' inputStream=' . $inputStream);
			}

			$data = \CMixplatPayment::JSdecode($inputStream);
			if ($data === false) {
				return false;
			}
			$merchantData = \CMixplatPayment::JSdecode($data['merchant_data']);

			if (isset($data['request']) && $data['request'] === 'payment_status'
				&& isset($merchantData['BX_HANDLER'])
				&& $merchantData['BX_HANDLER'] === 'MIXPLAT'
				&& isset($merchantData['BX_PAYSYSTEM_CODE'])
				&& (int) $merchantData['BX_PAYSYSTEM_CODE'] === (int) $paySystemId
			) {
				return true;
			}
		}

		return false;
	}

	/**
	 * @return bool|string
	 */
	private static function readFromStream()
	{
		return file_get_contents('php://input');
	}

	/**
	 * @param Payment $payment
	 * @param $refundableSum
	 * @return array
	 * @throws Main\ArgumentNullException
	 */
	private function getRefundQueryParams(Payment $payment, $refundableSum)
	{
		$query = array(
			'api_version' => 3,
			'payment_id'  => $payment->getField('PS_INVOICE_ID'),
			'amount'      => PriceMaths::roundPrecision($refundableSum) * 100,
		);
		$query['signature'] = $this->getActionSignature($query, $this->getBusinessValue($payment, 'API_KEY'));
		return $query;
	}

	/**
	 * @param string $query
	 * @param string $apiKey
	 * @return string
	 */
	private function getActionSignature($query, $apiKey)
	{
		return md5($query['payment_id'] . $apiKey);
	}

	/**
	 * @param Payment $payment
	 * @return array
	 * @throws Main\ArgumentNullException
	 */
	private function getCancelQueryParams(Payment $payment)
	{
		$query = array(
			'api_version' => 3,
			'payment_id'  => $payment->getField('PS_INVOICE_ID'),
		);
		$query['signature'] = $this->getActionSignature($query, $this->getBusinessValue($payment, 'API_KEY'));
		return $query;
	}

	/**
	 * @param Payment $payment
	 * @param $sum
	 * @return array
	 * @throws Main\ArgumentNullException
	 */
	private function getConfirmQueryParams(Payment $payment, $sum)
	{
		$query = array(
			'api_version' => 3,
			'payment_id'  => $payment->getField('PS_INVOICE_ID'),
		);
		if ($sum) {
			$query['amount'] = PriceMaths::roundPrecision($sum) * 100;
		}
		$query['signature'] = $this->getActionSignature($query, $this->getBusinessValue($payment, 'API_KEY'));
		return $query;
	}

	/**
	 * @param Payment $payment
	 * @param $refundableSum
	 * @return PaySystem\ServiceResult
	 * @throws Main\ArgumentNullException
	 * @throws \Exception
	 */
	public function refund(Payment $payment, $refundableSum)
	{
		$result = new PaySystem\ServiceResult();

		$url    = $this->getUrl($payment, 'refund');
		$params = $this->getRefundQueryParams($payment, $refundableSum);

		$sendResult = $this->send($url, $params);
		if (!$sendResult->isSuccess()) {
			$result->addErrors($sendResult->getErrors());

			$error = 'Mixplat: refund: ' . join('\n', $sendResult->getErrorMessages());
			if (class_exists('Bitrix\Sale\PaySystem\Logger')) {
				PaySystem\Logger::addError($error);
			}

			return $result;
		}

		$response = $sendResult->getData();

		if ($response['result'] === 'ok') {
			$result->setOperationType(PaySystem\ServiceResult::MONEY_LEAVING);
			$fields = array(
				"PS_STATUS_CODE"        => substr("refund", 0, 5),
				"PS_STATUS_DESCRIPTION" => Localization\Loc::getMessage('MIXPLAT.PAYMENT_QR_CHECKOUT_PAYMENT_REFUND') . ' ' . $refundableSum,
				"PS_RESPONSE_DATE"      => new Main\Type\DateTime(),
			);
			$result->setPsData($fields);
		}

		return $result;
	}

	/**
	 * @param Payment $payment
	 * @return PaySystem\ServiceResult
	 * @throws \Exception
	 */
	public function cancel(Payment $payment)
	{
		$result     = new PaySystem\ServiceResult();
		$url        = $this->getUrl($payment, 'cancel');
		$params     = $this->getCancelQueryParams($payment);
		$sendResult = $this->send($url, $params);
		if (!$sendResult->isSuccess()) {
			$result->addErrors($sendResult->getErrors());
			$error = 'Mixplat: cancel: ' . join('\n', $sendResult->getErrorMessages());
			if (class_exists('Bitrix\Sale\PaySystem\Logger')) {
				PaySystem\Logger::addError($error);
			}
			return $result;
		}
		$fields = array(
			"PS_STATUS_CODE"        => substr("canceled", 0, 5),
			"PS_STATUS_DESCRIPTION" => Localization\Loc::getMessage('MIXPLAT.PAYMENT_QR_ERROR_PAYMENT_CANCELED'),
			"PS_RESPONSE_DATE"      => new Main\Type\DateTime(),
			"PS_STATUS"             => 'N',
		);
		$result->setPsData($fields);

		return $result;
	}

	/**
	 * @param Payment $payment
	 * @return PaySystem\ServiceResult
	 * @throws Main\ArgumentNullException
	 * @throws Main\ObjectException
	 * @throws \Exception
	 */
	public function confirm(Payment $payment, $sum = 0)
	{
		global $DB;

		$result = new PaySystem\ServiceResult();
		$url    = $this->getUrl($payment, 'confirm');
		$params = $this->getConfirmQueryParams($payment, $sum);

		$sendResult = $this->send($url, $params);
		if ($sendResult->isSuccess()) {
			$id          = $payment->getField('PS_INVOICE_ID');
			$response    = $sendResult->getData();
			$description = Localization\Loc::getMessage('VAMPIRUS.YANDEXKASSA_CHECKOUT_TRANSACTION') . $response['id'];

			$fields = array(
				"PS_STATUS_CODE"        => substr($response['status'], 0, 5),
				"PS_STATUS_DESCRIPTION" => $description,
				"PS_SUM"                => $sum,
				"PS_RESPONSE_DATE"      => new Main\Type\DateTime(),
				"PS_STATUS"             => "Y",
			);

			$result->setPsData($fields);
		} else {
			$result->addErrors($sendResult->getErrors());
			$error = 'Mixplat: confirm: ' . join('\n', $sendResult->getErrorMessages());
			if (class_exists('Bitrix\Sale\PaySystem\Logger')) {
				PaySystem\Logger::addError($error);
			}
			return $result;
		}

		return $result;
	}

	/**
	 * @param PaySystem\ServiceResult $result
	 * @param Request $request
	 * @return mixed
	 */
	public function sendResponse(PaySystem\ServiceResult $result, Request $request)
	{
		global $APPLICATION;
		if ($result->isResultApplied()) {
			$APPLICATION->RestartBuffer();
			header('Content-Type: application/json');
			echo \CMixplatPayment::JSencode(array('result' => 'ok'));
		}
	}

	public function getClientIpAddress()
	{
		$keys = array(
			'HTTP_CLIENT_IP',
			'HTTP_X_FORWARDED_FOR',
			'HTTP_X_FORWARDED',
			'HTTP_X_CLUSTER_CLIENT_IP',
			'HTTP_FORWARDED_FOR',
			'HTTP_FORWARDED',
			'REMOTE_ADDR',
		);
		foreach ($keys as $key) {
			if (array_key_exists($key, $_SERVER) === true) {
				foreach (explode(',', $_SERVER[$key]) as $ip) {
					$ip = trim($ip); // just to be safe

					if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
						return $ip;
					}
				}
			}
		}
	}

}
