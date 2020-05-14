<?php
use \Bitrix\Sale\Order;
require_once $_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_admin_before.php";
require_once $_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/sale/include.php";
require_once $_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/sale/general/admin_tool.php";

IncludeModuleLangFile(__FILE__);

$saleModulePermissions = $APPLICATION->GetGroupRight("mixplat.payment");
if ($saleModulePermissions == "D") {
    $APPLICATION->AuthForm(GetMessage("ACCESS_DENIED"));
}
ClearVars("l_");

CModule::IncludeModule("mixplat.payment");
CModule::IncludeModule("sale");

$arUserGroups = $USER->GetUserGroupArray();
$intUserID    = intval($USER->GetID());

$sTableID = "mixplat_payment";

$oSort  = new CAdminSorting($sTableID, "ORDER_ID", "desc");
$lAdmin = new CAdminList($sTableID, $oSort);

$arPost         = array();
$action_success = false;
if (is_array($_POST) && count($_POST)) {
    $arPost = $_POST;
    $result = CMixplatPayment::proccessAdminAction($arPost);
    if (!$result->isSuccess()) {
        $lAdmin->AddUpdateError(implode(", ",$result->getErrorMessages()));
    }
}

$arFilterFields = array(
    "filter_order_id",
);

$lAdmin->InitFilter($arFilterFields);
$arFilter = array();
if (IntVal($filter_order_id) > 0) {
    $arFilter["ORDER_ID"] = IntVal($filter_order_id);
}

$arFilterFieldsTmp = array(
    "filter_order_id" => GetMessage("MIXPLAT.PAYMENT_ORDER_NUMBER"),
);

$oFilter = new CAdminFilter(
    $sTableID . "_filter",
    $arFilterFieldsTmp
);

$arHeaders = array(
    array("id" => "order_id", "content" => GetMessage('MIXPLAT.PAYMENT_ORDER_NUMBER'), "sort" => false, "default" => true),
    array("id" => "id", "content" => GetMessage('MIXPLAT.PAYMENT_INVOICE_ID'), "sort" => false, "default" => true),
    array("id" => "amount", "content" => GetMessage('MIXPLAT.PAYMENT_ORDER_SUM'), "sort" => false, "default" => true),
    array("id" => "date", "content" => GetMessage('MIXPLAT.PAYMENT_DATE'), "sort" => false, "default" => true),
    array("id" => "status", "content" => GetMessage('MIXPLAT.PAYMENT_STATUS'), "sort" => false, "default" => true),
    array("id" => "ACTION", "content" => GetMessage('MIXPLAT.PAYMENT_ACTION'), "sort" => false, "default" => true),
);

$lAdmin->AddHeaders($arHeaders);


$dbOrderList = CMixplatPayment::getTransactionList($arFilter);

$dbOrderList = new CAdminResult($dbOrderList, $sTableID);
$dbOrderList->NavStart();

$lAdmin->NavText($dbOrderList->GetNavPrint(""));

while ($arOrder = $dbOrderList->NavNext(true, "f_")) {
    $order = Order::load($arOrder['order_id']);
    if (is_null($order)) {
        continue;
    }
    $orderAr = CSaleOrder::GetByID($arOrder['order_id']);
    $payment = $order->getPaymentCollection()->getItemById($arOrder['payment_id']);
    if (is_null($payment)) {
        continue;
    }
    $row = &$lAdmin->AddRow($f_ID, $arOrder, "sale_order_view.php?ID=" . $arOrder['order_id'] . "&lang=" . LANGUAGE_ID . GetFilterParams("filter_"));

    $idTmp = '<a href="/bitrix/admin/sale_order_view.php?ID=' . $arOrder["order_id"] . '" title="' . GetMessage("MIXPLAT.PAYMENT_VIEW_ORDER") . '">' . $orderAr['ACCOUNT_NUMBER'] . '</a>';

    $row->AddField("order_id", $idTmp);
    $currentStatus = $arOrder["status"];
    if (in_array($arOrder["status"], array( 'pending', 'failure'))) {
        $currentStatus = $arOrder["status_extended"];
    }
    $status = GetMessage('MIXPLAT.PAYMENT_STATUS_' . strtoupper($currentStatus));
    $row->AddField("status", $status);

    $action = '';
    if (in_array($currentStatus, array("success", "pending_authorized")) && $payment->isPaid()) {

        if (in_array($currentStatus, array("success"))) {
            $action .= '
            '. GetMessage('MIXPLAT.PAYMENT_ACTION_RETURN_SUM') .':<br>
            <input type="text" name="sum['.$arOrder['id'].']" value="'.$payment->getSum().'">
                <button class="adm-btn" type="submit" name="action[' . $arOrder['id'] . ']" value="return">' . GetMessage('MIXPLAT.PAYMENT_ACTION_RETURN') . '</button>';
        }
        if (in_array($currentStatus, array("pending_authorized"))) {
            $action = '
         '. GetMessage('MIXPLAT.PAYMENT_ACTION_CONFIRM_SUM') .':<br>
            <input type="text" name="sum['.$arOrder['id'].']" value="'.$payment->getSum().'">
            ';
            $action .= '
                <button class="adm-btn" type="submit" name="action[' . $arOrder['id'] . ']" value="confirm">' . GetMessage('MIXPLAT.PAYMENT_ACTION_CONFIRM') . '</button>';
            $action .= '<br>
                <button class="adm-btn" type="submit" name="action[' . $arOrder['id'] . ']" value="cancel">' . GetMessage('MIXPLAT.PAYMENT_ACTION_CANCEL') . '</button>';
        }
    }
    $row->AddField('ACTION', $action);

}

$lAdmin->CheckListMode();
require_once $_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/sale/prolog.php";

$APPLICATION->SetTitle(GetMessage("MIXPLAT.PAYMENT_TRANSACTION_TITLE"));

require $_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_admin_after.php";
?>
<form name="find_form" method="GET" action="<?echo $APPLICATION->GetCurPage() ?>?">
<?
$oFilter->Begin();
?>
<tr>
    <td><?=GetMessage("MIXPLAT.PAYMENT_ORDER_NUMBER")?>:</td>
    <td>
        <input type="text" name="filter_order_id" value="<?echo htmlspecialcharsbx($filter_order_id) ?>" size="40">
    </td>
</tr>
<?
$oFilter->Buttons(
    array(
        "table_id" => $sTableID,
        "url"      => $APPLICATION->GetCurPage(),
        "form"     => "find_form",
    )
);
$oFilter->End();
?>
</form>
<?
$lAdmin->DisplayList();
?>
<?
require $_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/epilog_admin.php";
?>