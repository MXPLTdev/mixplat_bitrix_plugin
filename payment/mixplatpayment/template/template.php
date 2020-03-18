<?
	use Bitrix\Main\Localization\Loc;
	\Bitrix\Main\Page\Asset::getInstance()->addCss("/bitrix/themes/.default/sale.css");
	Loc::loadMessages(__FILE__);

	$sum = roundEx($params['SUM'], 2);
?>

<div class="sale-paysystem-wrapper">
	<span class="tablebodytext">
		<?=Loc::getMessage('SALE_HANDLERS_PAY_SYSTEM_YANDEX_CHECKOUT_DESCRIPTION')." ".SaleFormatCurrency($params['SUM'], $params['CURRENCY']);?>
	</span>
	<div class="sale-paysystem-yandex-button-container">
		<span class="sale-paysystem-yandex-button">
			<a class="btn btn-primary btn-lg d-none d-sm-inline-block sale-paysystem-yandex-button-item" href="<?=$params['URL'];?>">
				<?=Loc::getMessage('SALE_HANDLERS_PAY_SYSTEM_YANDEX_CHECKOUT_BUTTON_PAID')?>
			</a>
		</span>
	</div>

</div>