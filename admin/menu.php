<?php

IncludeModuleLangFile(__FILE__);
$res = $DB->Query("SHOW TABLES LIKE 'mixplat_payment'");
if ($res->SelectedRowsCount() && $APPLICATION->GetGroupRight("mixplat_payment") != "D") {
	$aMenu = array(
		"parent_menu" => "global_menu_store",
		"section" => "mixplat_payment",
		"sort" => 100,
		"text" => GetMessage("MIXPLAT.PAYMENT_MENU_TITLE"),
		"title" => GetMessage("MIXPLAT.PAYMENT_MENU_TITLE"),
		"url" => "mixplat_payment.php?lang=".LANGUAGE_ID,
		"icon" => "mixplat_payment_menu_icon",
		"page_icon" => "mixplat_payment_menu_icon",
		"items_id" => "mixplat_payment",
	);
	return $aMenu;
}

return false;
