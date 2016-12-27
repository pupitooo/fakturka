<?php

namespace App\Extensions\Settings\DI;

use App\Model\Entity\Vat;
use Nette\DI\CompilerExtension;

class SettingsExtension extends CompilerExtension
{

	/** @var array */
	public $defaults = [
		'modules' => [
			'cron' => [ // access to cron scripts
				'enabled' => FALSE,
				'allowedIps' => ['127.0.0.1'],
			],
			'vats' => [ // vat levels
				Vat::HIGH => 21,
				Vat::LOW => 15,
				Vat::NONE => 0,
			],
			'googleAnalytics' => [
				'enabled' => FALSE,
				'code' => NULL,
			],
			'googleSiteVerification' => [
				'enabled' => FALSE,
				'code' => NULL,
			],
			'smartSupp' => [
				'enabled' => FALSE,
				'key' => NULL,
			],
			'smartLook' => [
				'enabled' => FALSE,
				'key' => NULL,
			],
			'facebookApplet' => [
				'enabled' => FALSE,
				'id' => NULL,
			],
		],
		'pageInfo' => [
			'projectName' => 'projectName',
			'author' => 'Mediahost.sk',
			'authorUrl' => 'http://www.mediahost.sk/',
			'keywords' => 'keywords',
			'description' => 'description',
			'termPageId' => 1, // TODO: move to pageConfig
			'complaintPageId' => 1, // TODO: move to pageConfig
			'contactPageId' => 1, // TODO: move to pageConfig
			'orderByPhonePageId' => 1, // TODO: move to pageConfig
		],
		'pageConfig' => [
			'itemsPerRow' => 3,
			'rowsPerPage' => 4,
		],
		'companyInfo' => [
			'address' => [
				'company' => 'Company name',
				'street' => 'Main street 2',
				'zip' => '123 45',
				'city' => 'City',
				'country' => 'CZ',
			],
			'contact' => [
				'phone' => '+420 123 456 789',
				'mail' => 'contact@company.cz',
			],
			'bank' => [
				'eur' => [
					'base' => '123456789012/0000',
					'iban' => 'CZ1234567890123456789012',
					'bic' => 'BANKBIC',
				],
				'czk' => [
					'base' => '123456789012/0000',
					'iban' => 'CZ1234567890123456789012',
					'bic' => 'BANKBIC',
				],
			],
			'company' => [
				'ico' => '12345678',
				'dic' => 'SK12345678',
			],
		],
		'expiration' => [
			'recovery' => '30 minutes',
			'verification' => '1 hour',
			'registration' => '1 hour',
			'remember' => '14 days',
			'notRemember' => '30 minutes',
		],
		'passwords' => [
			'minLength' => 8,
		],
		'mails' => [ // default value is NULL - doesnt send mail
			'automatFrom' => 'info@example.sk',
		],
	];

	public function loadConfiguration()
	{
		$builder = $this->getContainerBuilder();
		$config = $this->getConfig($this->defaults);

		$builder->addDefinition($this->prefix('settings'))
				->setClass('App\Extensions\Settings\SettingsStorage')
				->addSetup('setPageInfo', [$config['pageInfo']])
				->addSetup('setPageConfig', [$config['pageConfig']])
				->addSetup('setCompanyInfo', [$config['companyInfo']])
				->addSetup('setMails', [$config['mails']])
				->addSetup('setExpiration', [$config['expiration']])
				->addSetup('setPasswords', [$config['passwords']])
				->addSetup('setModules', [$config['modules']])
				->setInject(TRUE);
	}

}
