<?php
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");

CModule::IncludeModule('sale');

$APPLICATION->SetPageProperty("NOT_SHOW_NAV_CHAIN", "Y");
$APPLICATION->SetTitle(GetMessage("MIXPLAT.PAYMENT_SUCCESS_TITLE"));

$orderID = $_REQUEST['order_id'];
$order = CSaleOrder::GetByID($orderID);

if($order){
	$statusPageURL = sprintf('%s/%s', GetPagePath('personal/orders'), (int)$orderID);
}

?>

<?php if (!($arOrder = CSaleOrder::GetByID($orderID))): ?>
	<?=GetMessage("MIXPLAT.PAYMENT_SUCCESS_NOTFOUND", array('#ORDER_ID#' => htmlspecialchars($orderID)))?>
<?php else: ?>
	<?=GetMessage("MIXPLAT.PAYMENT_SUCCESS_THNX")?><br/>
	<?=GetMessage("MIXPLAT.PAYMENT_SUCCESS_LINK", array('#LINK#' => $statusPageURL))?>
<?php endif; ?>

<?php require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php"); ?>