<?php

namespace App\Extensions\UserStorage;

use App\Model\Entity\User;
use h4kuna\Exchange\Currency\IProperty;
use Kdyby\Doctrine\EntityManager;
use Nette\Object;
use Nette\Security\IIdentity;
use Nette\Security\IUserStorage;

class UserStorageStrategy extends Object implements IUserStorage
{

	/** @var IUserStorage */
	private $userStorage;

	/** @var GuestStorage */
	private $guestStorage;

	/** @var EntityManager */
	private $em;

	public function __construct(EntityManager $em)
	{
		$this->em = $em;
	}

	public function getIdentity()
	{
		if ($this->isAuthenticated()) {
			return $this->userStorage->getIdentity();
		} else {
			return $this->guestStorage->getIdentity();
		}
	}

	public function setIdentity(IIdentity $identity = NULL)
	{
		$this->userStorage->setIdentity($identity);
	}

	public function getLogoutReason()
	{
		return $this->userStorage->getLogoutReason();
	}

	public function isAuthenticated()
	{
		return $this->userStorage->isAuthenticated();
	}

	public function setAuthenticated($state)
	{
		$this->userStorage->setAuthenticated($state);
	}

	public function setExpiration($time, $flags = 0)
	{
		$this->userStorage->setExpiration($time, $flags);
	}

	public function setUser(IUserStorage $storage)
	{
		$this->userStorage = $storage;
		return $this;
	}

	public function setGuest(IUserStorage $storage)
	{
		$this->guestStorage = $storage;
		return $this;
	}

	private function saveUser(User $user)
	{
		$this->em->persist($user);
		$this->em->flush();
		return $user;
	}

	/**
	 * @param string $locale
	 * @return UserStorageStrategy
	 */
	public function setLocale($locale)
	{
		if ($this->isAuthenticated()) {
			$this->userStorage->identity->locale = $locale;
			$this->em->persist($this->userStorage->identity)
				->flush();
		} else {
			$this->guestStorage->identity->locale = $locale;
		}

		return $this;
	}

	/**
	 * @param IProperty $currency
	 * @return UserStorageStrategy
	 */
	public function setCurrency($currency)
	{
		if ($this->isAuthenticated()) {
			$this->userStorage->identity->currency = $currency->getCode();
			$this->em->persist($this->userStorage->identity)
				->flush();
		} else {
			$this->guestStorage->identity->currency = $currency->getCode();
		}

		return $this;
	}

	public function fromGuestToUser()
	{
		$user = $this->userStorage->identity;
		$guest = $this->guestStorage->identity;

		$user->import($guest);

		$this->saveUser($user);

		return $this;
	}

}
