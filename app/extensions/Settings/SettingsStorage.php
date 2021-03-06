<?php

namespace App\Extensions\Settings;

use Nette\Object;
use Nette\Security\User;
use Nette\Utils\ArrayHash;

class SettingsStorage extends Object
{

	/** @var User */
	private $user;

	/** @var ArrayHash */
	private $expiration;

	/** @var ArrayHash */
	private $passwords;

	/** @var ArrayHash */
	private $design;

	/** @var ArrayHash */
	private $pageConfig;

	/** @var ArrayHash */
	private $pageInfo;

	/** @var ArrayHash */
	private $companyInfo;
	
	/** @var ArrayHash */
	private $mails;

	/** @var ArrayHash */
	private $modules;

	public function __construct(User $user)
	{
		$this->user = $user;
	}

	/**
	 * @param array
	 * @return SettingsStorage
	 */
	public function setExpiration($values)
	{
		$this->expiration = ArrayHash::from($values);
		return $this;
	}

	/**
	 * @return ArrayHash
	 */
	public function getExpiration()
	{
		return $this->expiration;
	}

	/**
	 * @param array
	 * @return SettingsStorage
	 */
	public function setPasswords($values)
	{
		$this->passwords = ArrayHash::from($values);
		return $this;
	}

	/**
	 * @return ArrayHash
	 */
	public function getPasswords()
	{
		return $this->passwords;
	}

	/**
	 * @param array
	 * @return SettingsStorage
	 */
	public function setDesign($values)
	{
		$this->design = ArrayHash::from($values);
		return $this;
	}

	/**
	 * @return ArrayHash
	 */
	public function getDesign()
	{
		return $this->design;
	}

	/**
	 * @param array
	 * @return SettingsStorage
	 */
	public function setPageConfig($values)
	{
		$this->pageConfig = ArrayHash::from($values);
		return $this;
	}

	/**
	 * @return ArrayHash
	 */
	public function getPageConfig()
	{
		return $this->pageConfig;
	}

	/**
	 * @param array $values
	 * @return SettingsStorage
	 */
	public function setPageInfo(array $values)
	{
		$this->pageInfo = ArrayHash::from($values);
		return $this;
	}

	/**
	 * @return ArrayHash
	 */
	public function getPageInfo()
	{
		return $this->pageInfo;
	}

	/**
	 * @param array $values
	 * @return SettingsStorage
	 */
	public function setCompanyInfo(array $values)
	{
		$this->companyInfo = ArrayHash::from($values);
		return $this;
	}

	/**
	 * @return ArrayHash
	 */
	public function getCompanyInfo()
	{
		return $this->companyInfo;
	}

	/**
	 * @param array $values
	 * @return SettingsStorage
	 */
	public function setMails(array $values)
	{
		$this->mails = ArrayHash::from($values);
		return $this;
	}

	/**
	 * @return ArrayHash
	 */
	public function getMails()
	{
		return $this->mails;
	}

	/**
	 * @param array
	 * @return SettingsStorage
	 */
	public function setModules(array $values)
	{
		$this->modules = ArrayHash::from($values);
		return $this;
	}

	/**
	 * @return ArrayHash
	 */
	public function getModules()
	{
		return $this->modules;
	}

}
