<?php
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

$description = array(
	'RETURN'      => Loc::getMessage('MIXPLAT.PAYMENT_RETURN'),
	'RESTRICTION' => Loc::getMessage('MIXPLAT.PAYMENT_RESTRICTION'),
	'COMMISSION'  => Loc::getMessage('MIXPLAT.PAYMENT_COMMISSION'),
	'MAIN'        => Loc::getMessage('MIXPLAT.PAYMENT_DESCRIPTION'),
);

$data = array(
	'NAME'  => Loc::getMessage('MIXPLAT.PAYMENT'),
	'SORT'  => 500,
	'CODES' => array(
		'ORDER_NUMBER'                             => array(
			'NAME'    => Loc::getMessage('MIXPLAT.PAYMENT_OPTIONS_ORDER_NUMBER'),
			'SORT'    => 750,
			//'GROUP' => Loc::getMessage('MIXPLAT.PAYMENT_PAYMENT_SETTINGS'),
			'DEFAULT' => array(
				'PROVIDER_VALUE' => 'ACCOUNT_NUMBER',
				'PROVIDER_KEY'   => 'ORDER',
			),
		),

		'BUYER_PERSON_EMAIL'                       => array(
			'NAME'    => Loc::getMessage('MIXPLAT.PAYMENT_OPTIONS_EMAIL_USER'),
			'SORT'    => 1100,
			//'GROUP' => 'BUYER_PERSON',
			'DEFAULT' => array(
				'PROVIDER_VALUE' => 'EMAIL',
				'PROVIDER_KEY'   => 'USER',
			),
		),
		"API_KEY"                  => array(
			"NAME"        => Loc::getMessage("MIXPLAT.PAYMENT_API_KEY"),
			"DESCRIPTION" => Loc::getMessage("MIXPLAT.PAYMENT_API_KEY_DESC"),
			'SORT'        => 100,
			'GROUP'       => Loc::getMessage("MIXPLAT.PAYMENT_CONNECT_SETTINGS"),
		),
		"PROJECT_ID"               => array(
			"NAME"        => Loc::getMessage("MIXPLAT.PAYMENT_PROJECT_ID"),
			"DESCRIPTION" => Loc::getMessage("MIXPLAT.PAYMENT_PROJECT_ID_DESC"),
			'SORT'        => 200,
			'GROUP'       => Loc::getMessage("MIXPLAT.PAYMENT_CONNECT_SETTINGS"),
		),
		"FORM_ID"          => array(
			"NAME"        => Loc::getMessage("MIXPLAT.PAYMENT_FORM_ID"),
			"DESCRIPTION" => Loc::getMessage("MIXPLAT.PAYMENT_FORM_ID_DESC"),
			'SORT'        => 300,
			'GROUP'       => Loc::getMessage("MIXPLAT.PAYMENT_CONNECT_SETTINGS"),
		),
		"TEST"                      => array(
			"NAME"        => Loc::getMessage("MIXPLAT.PAYMENT_TEST"),
			"DESCRIPTION" => Loc::getMessage("MIXPLAT.PAYMENT_TEST_DESC"),
			'SORT'        => 400,
			'GROUP'       => Loc::getMessage("MIXPLAT.PAYMENT_CONNECT_SETTINGS"),
			"INPUT"       => array(
				'TYPE'    => 'ENUM',
				'OPTIONS' => array(
					'1' => Loc::getMessage("MIXPLAT.PAYMENT_OPTION_TEST_NO"),
					'2' => Loc::getMessage("MIXPLAT.PAYMENT_OPTION_TEST_YES"),
				),
			),
		),
		"DESCRIPTION"              => array(
			"NAME"        => Loc::getMessage("MIXPLAT.PAYMENT_TRANSACTION_DESCRIPTION"),
			"DESCRIPTION" => Loc::getMessage("MIXPLAT.PAYMENT_TRANSACTION_DESCRIPTION_DESC"),
			'SORT'        => 450,
			'GROUP'       => Loc::getMessage("MIXPLAT.PAYMENT_CONNECT_SETTINGS"),
			'DEFAULT'     => array(
				'PROVIDER_KEY'   => 'VALUE',
				'PROVIDER_VALUE' => Loc::getMessage("MIXPLAT.PAYMENT_DESCRIPTION_TEMPLATE"),
			),
		),
		"FILTER_IPS"                      => array(
			"NAME"        => Loc::getMessage("MIXPLAT.PAYMENT_FILTER_IPS"),
			"DESCRIPTION" => Loc::getMessage("MIXPLAT.PAYMENT_FILTER_IPS_DESC"),
			'SORT'        => 500,
			'GROUP'       => Loc::getMessage("MIXPLAT.PAYMENT_CONNECT_SETTINGS"),
			"INPUT"       => array(
				'TYPE'    => 'ENUM',
				'OPTIONS' => array(
					'1' => Loc::getMessage("MIXPLAT.PAYMENT_OPTION_FILTER_IPS_NO"),
					'2' => Loc::getMessage("MIXPLAT.PAYMENT_OPTION_FILTER_IPS_YES"),
				),
			),
		),
		"IP_LIST"          => array(
			"NAME"        => Loc::getMessage("MIXPLAT.PAYMENT_IP_LIST"),
			"DESCRIPTION" => Loc::getMessage("MIXPLAT.PAYMENT_IP_LIST_DESC"),
			'SORT'        => 600,
			'GROUP'       => Loc::getMessage("MIXPLAT.PAYMENT_CONNECT_SETTINGS"),
			'DEFAULT'     => array(
				'PROVIDER_KEY'   => 'VALUE',
				'PROVIDER_VALUE' => '185.77.233.27,185.77.233.29',
			),
		),
		"HOLD" => array(
			"NAME"        => Loc::getMessage("MIXPLAT.PAYMENT_HOLD"),
			"DESCRIPTION" => Loc::getMessage("MIXPLAT.PAYMENT_HOLD_DESC"),
			'SORT'        => 700,
			'GROUP'       => Loc::getMessage("MIXPLAT.PAYMENT_CONNECT_SETTINGS"),
			"INPUT"       => array(
				'TYPE'    => 'ENUM',
				'OPTIONS' => array(
					'2' => Loc::getMessage("MIXPLAT.PAYMENT_OPTION_HOLD_YES"),
					'1' => Loc::getMessage("MIXPLAT.PAYMENT_OPTION_HOLD_NO"),
				),
			),
		),
		"RECEIPT" => array(
			"NAME"        => Loc::getMessage("MIXPLAT.PAYMENT_RECEIPT"),
			"DESCRIPTION" => Loc::getMessage("MIXPLAT.PAYMENT_RECEIPT_DESC"),
			'SORT'        => 800,
			'GROUP'       => Loc::getMessage("MIXPLAT.PAYMENT_RECEIPT_SETTINGS"),
			"INPUT"       => array(
				'TYPE'    => 'ENUM',
				'OPTIONS' => array(
					'2' => Loc::getMessage("MIXPLAT.PAYMENT_OPTION_RECEIPT_YES"),
					'1' => Loc::getMessage("MIXPLAT.PAYMENT_OPTION_RECEIPT_NO"),
				),
			),
		),
		"SNO"                      => array(
			"NAME"        => Loc::getMessage("MIXPLAT.PAYMENT_SNO"),
			"DESCRIPTION" => Loc::getMessage("MIXPLAT.PAYMENT_SNO_DESC"),
			'SORT'        => 900,
			'GROUP'       => Loc::getMessage("MIXPLAT.PAYMENT_RECEIPT_SETTINGS"),
			"INPUT"       => array(
				'TYPE'    => 'ENUM',
				'OPTIONS' => array(
					'1' => Loc::getMessage("MIXPLAT.PAYMENT_OPTION_SNO_OSN"),
					'2' => Loc::getMessage("MIXPLAT.PAYMENT_OPTION_SNO_USN_INCOME"),
					'3' => Loc::getMessage("MIXPLAT.PAYMENT_OPTION_SNO_USN_INCOME_OUTCOME"),
					'4' => Loc::getMessage("MIXPLAT.PAYMENT_OPTION_SNO_ENVD"),
					'5' => Loc::getMessage("MIXPLAT.PAYMENT_OPTION_SNO_ESN"),
					'6' => Loc::getMessage("MIXPLAT.PAYMENT_OPTION_SNO_PATENT"),
				),
			),
		),
		"PRODUCT_NDS"              => array(
			"NAME"        => Loc::getMessage("MIXPLAT.PAYMENT_PRODUCT_NDS"),
			"DESCRIPTION" => Loc::getMessage("MIXPLAT.PAYMENT_PRODUCT_NDS_DESC"),
			'SORT'        => 1000,
			'GROUP'       => Loc::getMessage("MIXPLAT.PAYMENT_RECEIPT_SETTINGS"),
			"INPUT"       => array(
				'TYPE'    => 'ENUM',
				'OPTIONS' => array(
					'0' => Loc::getMessage("MIXPLAT.PAYMENT_OPTION_NDS_CATALOG"),
					'none' => Loc::getMessage("MIXPLAT.PAYMENT_OPTION_NDS_NONE"),
					'vat0' => Loc::getMessage("MIXPLAT.PAYMENT_OPTION_NDS_VAT0"),
					'vat10' => Loc::getMessage("MIXPLAT.PAYMENT_OPTION_NDS_VAT10"),
					'vat20' => Loc::getMessage("MIXPLAT.PAYMENT_OPTION_NDS_VAT20"),
					'vat110' => Loc::getMessage("MIXPLAT.PAYMENT_OPTION_NDS_VAT110"),
					'vat120' => Loc::getMessage("MIXPLAT.PAYMENT_OPTION_NDS_VAT120"),
				),
			),
		),
		"DELIVERY_NDS"             => array(
			"NAME"        => Loc::getMessage("MIXPLAT.PAYMENT_DELIVERY_NDS"),
			"DESCRIPTION" => Loc::getMessage("MIXPLAT.PAYMENT_DELIVERY_NDS_DESC"),
			'SORT'        => 1100,
			'GROUP'       => Loc::getMessage("MIXPLAT.PAYMENT_RECEIPT_SETTINGS"),
			"INPUT"       => array(
				'TYPE'    => 'ENUM',
				'OPTIONS' => array(
					'0' => Loc::getMessage("MIXPLAT.PAYMENT_OPTION_NDS_SETTINGS"),
					'none' => Loc::getMessage("MIXPLAT.PAYMENT_OPTION_NDS_NONE"),
					'vat0' => Loc::getMessage("MIXPLAT.PAYMENT_OPTION_NDS_VAT0"),
					'vat10' => Loc::getMessage("MIXPLAT.PAYMENT_OPTION_NDS_VAT10"),
					'vat20' => Loc::getMessage("MIXPLAT.PAYMENT_OPTION_NDS_VAT20"),
					'vat110' => Loc::getMessage("MIXPLAT.PAYMENT_OPTION_NDS_VAT110"),
					'vat120' => Loc::getMessage("MIXPLAT.PAYMENT_OPTION_NDS_VAT120"),
				),
			),
		),
		"PAYMENT_MODE"             => array(
			"NAME"        => Loc::getMessage("MIXPLAT.PAYMENT_PAYMENT_MODE"),
			"DESCRIPTION" => Loc::getMessage("MIXPLAT.PAYMENT_PAYMENT_MODE_DESC"),
			'SORT'        => 1200,
			'GROUP'       => Loc::getMessage("MIXPLAT.PAYMENT_RECEIPT_SETTINGS"),
			"INPUT"       => array(
				'TYPE'    => 'ENUM',
				'OPTIONS' => array(
					'full_prepayment'    => Loc::getMessage("MIXPLAT.PAYMENT_OPTION_MODE_FULL_PREPAYMENT"),
					'partial_prepayment' => Loc::getMessage("MIXPLAT.PAYMENT_OPTION_MODE_PREPAYMENT"),
					'advance'            => Loc::getMessage("MIXPLAT.PAYMENT_OPTION_MODE_ADVANCE"),
					'full_payment'       => Loc::getMessage("MIXPLAT.PAYMENT_OPTION_MODE_FULL_PAYMENT"),
					'partial_payment'    => Loc::getMessage("MIXPLAT.PAYMENT_OPTION_MODE_PARTIAL_PAYMENT"),
					'credit'             => Loc::getMessage("MIXPLAT.PAYMENT_OPTION_MODE_CREDIT"),
					'credit_payment'     => Loc::getMessage("MIXPLAT.PAYMENT_OPTION_MODE_CREDIT_PAYMENT"),
				),
			),
		),
		"PAYMENT_SUBJECT"          => array(
			"NAME"        => Loc::getMessage("MIXPLAT.PAYMENT_PAYMENT_SUBJECT"),
			"DESCRIPTION" => Loc::getMessage("MIXPLAT.PAYMENT_PAYMENT_SUBJECT_DESC"),
			'SORT'        => 1300,
			'GROUP'       => Loc::getMessage("MIXPLAT.PAYMENT_RECEIPT_SETTINGS"),
			"INPUT"       => array(
				'TYPE'    => 'ENUM',
				'OPTIONS' => array(
					'commodity'             => Loc::getMessage("MIXPLAT.PAYMENT_OPTION_SUBJECT_COMMODITY"),
					'excise'                => Loc::getMessage("MIXPLAT.PAYMENT_OPTION_SUBJECT_USN_EXCISE"),
					'job'                   => Loc::getMessage("MIXPLAT.PAYMENT_OPTION_SUBJECT_USN_INCOME_JOB"),
					'service'               => Loc::getMessage("MIXPLAT.PAYMENT_OPTION_SUBJECT_SERVICE"),
					'gambling_bet'          => Loc::getMessage("MIXPLAT.PAYMENT_OPTION_SUBJECT_GAMBLING_BET"),
					'gambling_prize'        => Loc::getMessage("MIXPLAT.PAYMENT_OPTION_SUBJECT_GAMBLING_PRIZE"),
					'lottery'               => Loc::getMessage("MIXPLAT.PAYMENT_OPTION_SUBJECT_LOTTERY"),
					'lottery_prize'         => Loc::getMessage("MIXPLAT.PAYMENT_OPTION_SUBJECT_LOTTERY_PRIZE"),
					'intellectual_activity' => Loc::getMessage("MIXPLAT.PAYMENT_OPTION_SUBJECT_INTELLECTUAL_ACTIVITY"),
					'payment'               => Loc::getMessage("MIXPLAT.PAYMENT_OPTION_SUBJECT_PAYMENT"),
					'agent_commission'      => Loc::getMessage("MIXPLAT.PAYMENT_OPTION_SUBJECT_AGENT_COMMISSION"),
					'composite'             => Loc::getMessage("MIXPLAT.PAYMENT_OPTION_SUBJECT_COMPOSITE"),
					'another'               => Loc::getMessage("MIXPLAT.PAYMENT_OPTION_SUBJECT_ANOTHER"),
				),
			),
		),
		"SUBJECT_DELIVERY" => array(
			"NAME"        => Loc::getMessage("MIXPLAT.PAYMENT_PAYMENT_SUBJECT_DELIVERY"),
			"DESCRIPTION" => Loc::getMessage("MIXPLAT.PAYMENT_PAYMENT_SUBJECT_DELIVERY_DESC"),
			'SORT'        => 1400,
			'GROUP'       => Loc::getMessage("MIXPLAT.PAYMENT_RECEIPT_SETTINGS"),
			"INPUT"       => array(
				'TYPE'    => 'ENUM',
				'OPTIONS' => array(
					'commodity'             => Loc::getMessage("MIXPLAT.PAYMENT_OPTION_SUBJECT_COMMODITY"),
					'excise'                => Loc::getMessage("MIXPLAT.PAYMENT_OPTION_SUBJECT_USN_EXCISE"),
					'job'                   => Loc::getMessage("MIXPLAT.PAYMENT_OPTION_SUBJECT_USN_INCOME_JOB"),
					'service'               => Loc::getMessage("MIXPLAT.PAYMENT_OPTION_SUBJECT_SERVICE"),
					'gambling_bet'          => Loc::getMessage("MIXPLAT.PAYMENT_OPTION_SUBJECT_GAMBLING_BET"),
					'gambling_prize'        => Loc::getMessage("MIXPLAT.PAYMENT_OPTION_SUBJECT_GAMBLING_PRIZE"),
					'lottery'               => Loc::getMessage("MIXPLAT.PAYMENT_OPTION_SUBJECT_LOTTERY"),
					'lottery_prize'         => Loc::getMessage("MIXPLAT.PAYMENT_OPTION_SUBJECT_LOTTERY_PRIZE"),
					'intellectual_activity' => Loc::getMessage("MIXPLAT.PAYMENT_OPTION_SUBJECT_INTELLECTUAL_ACTIVITY"),
					'payment'               => Loc::getMessage("MIXPLAT.PAYMENT_OPTION_SUBJECT_PAYMENT"),
					'agent_commission'      => Loc::getMessage("MIXPLAT.PAYMENT_OPTION_SUBJECT_AGENT_COMMISSION"),
					'composite'             => Loc::getMessage("MIXPLAT.PAYMENT_OPTION_SUBJECT_COMPOSITE"),
					'another'               => Loc::getMessage("MIXPLAT.PAYMENT_OPTION_SUBJECT_ANOTHER"),
				),
			),
		),
	),
);
