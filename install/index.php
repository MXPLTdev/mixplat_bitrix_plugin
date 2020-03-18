<?
global $MESS;
$strPath2Lang = str_replace("\\", "/", __FILE__);
$strPath2Lang = substr($strPath2Lang, 0, strlen($strPath2Lang)-strlen("/install/index.php"));
include(GetLangFileName($strPath2Lang."/lang/", "/install.php"));

Class mixplat_payment extends CModule
{
	var $MODULE_ID = "mixplat.payment";
	var $MODULE_VERSION;
	var $MODULE_VERSION_DATE;
	var $MODULE_NAME;
	var $MODULE_DESCRIPTION;
	var $MODULE_CSS;
	var $MODULE_GROUP_RIGHTS = "Y";
	var $PARTNER_NAME;
	var $PARTNER_URI;

	function mixplat_payment()
	{
		$arModuleVersion = array();

		$path = str_replace("\\", "/", __FILE__);
		$path = substr($path, 0, strlen($path) - strlen("/index.php"));
		include($path."/version.php");

		if (is_array($arModuleVersion) && array_key_exists("VERSION", $arModuleVersion))
		{
			$this->MODULE_VERSION = $arModuleVersion["VERSION"];
			$this->MODULE_VERSION_DATE = $arModuleVersion["VERSION_DATE"];
		}
		else
		{
			$this->MODULE_VERSION = CURRENCY_VERSION;
			$this->MODULE_VERSION_DATE = CURRENCY_VERSION_DATE;
		}

		$this->PARTNER_URI  = "https://mixplat.ru";
		$this->PARTNER_NAME = GetMessage("MIXPLAT.PAYMENT_PARTNER_NAME");
		$this->MODULE_NAME = GetMessage("MIXPLAT.PAYMENT_INSTALL_NAME");
		$this->MODULE_DESCRIPTION = GetMessage("MIXPLAT.PAYMENT_INSTALL_DESCRIPTION");
	}

	function DoInstall()
	{
		global $APPLICATION, $step;
		$GLOBALS["errors"] = false;
			$this->InstallFiles();
			$this->InstallDB();
			$GLOBALS["errors"] = $this->errors;

	}

	function DoUninstall()
	{
		global $APPLICATION, $step;
		$this->UnInstallFiles();
		$this->UnInstallDB();
		UnRegisterModule("mixplat.payment");
	}

	function InstallDB()
	{
		global $DB, $DBType, $APPLICATION;
		$this->errors = false;
		RegisterModule("mixplat.payment");
		if(!$DB->Query("SELECT 'x' FROM mixplat_payment WHERE 1=0", true)){
            $this->errors = $DB->RunSQLBatch($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/mixplat.payment/install/db/".$DBType."/install.sql");
        }
		return true;
	}

	function UnInstallDB($arParams = array())
	{
		global $DB, $DBType, $APPLICATION;
		$this->errors = $DB->RunSQLBatch($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/mixplat.payment/install/db/".$DBType."/uninstall.sql");
		return true;
	}


	function InstallFiles()
	{
		CopyDirFiles($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/mixplat.payment/install/sale_payment/mixplatpayment/", $_SERVER["DOCUMENT_ROOT"]."/bitrix/php_interface/include/sale_payment/mixplatpayment/");
		CopyDirFiles($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/mixplat.payment/install/images", $_SERVER["DOCUMENT_ROOT"]."/bitrix/images/sale/sale_payments");
		CopyDirFiles($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/mixplat.payment/install/sale_payment/mixplatpayment/template", $_SERVER["DOCUMENT_ROOT"]."/bitrix/php_interface/include/sale_payment/mixplatpayment/template");
		CopyDirFiles($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/mixplat.payment/install/tools/", $_SERVER["DOCUMENT_ROOT"]."/bitrix/tools/");
		CopyDirFiles($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/".$this->MODULE_ID."/install/themes/.default", $_SERVER["DOCUMENT_ROOT"]."/bitrix/themes/.default", true, true);
        CopyDirFiles($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/".$this->MODULE_ID."/install/themes/.default/icons", $_SERVER["DOCUMENT_ROOT"]."/bitrix/themes/icons", true, true);
        CopyDirFiles($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/".$this->MODULE_ID."/install/admin/mixplat_payment.php", $_SERVER["DOCUMENT_ROOT"]."/bitrix/admin/mixplat_payment.php");
		return true;
	}

	function UnInstallFiles()
	{
		DeleteDirFilesEx("/bitrix/admin/mixplat_payment.php");
		DeleteDirFilesEx("/bitrix/php_interface/include/sale_payment/mixplatpayment");
		DeleteDirFiles($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/mixplat.payment/install/images/", $_SERVER["DOCUMENT_ROOT"]."/bitrix/images/sale/sale_payments/");
		DeleteDirFiles($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/mixplat.payment/install/tools/", $_SERVER["DOCUMENT_ROOT"]."/bitrix/tools/");
		DeleteDirFiles($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/".$this->MODULE_ID."/install/themes/.default", $_SERVER["DOCUMENT_ROOT"]."/bitrix/themes/.default");
        DeleteDirFilesEx( $_SERVER["DOCUMENT_ROOT"]."/bitrix/themes/.default/icons/mixplat.payment");
		return true;
	}

}
?>