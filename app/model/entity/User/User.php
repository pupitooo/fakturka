<?php

namespace App\Model\Entity;

use App\Model\Entity\Newsletter\Subscriber;
use App\Model\Entity\Traits\IUserSocials;
use App\Model\Entity\Traits\UserGroups;
use App\Model\Entity\Traits\UserPassword;
use App\Model\Entity\Traits\UserRoles;
use App\Model\Entity\Traits\UserSocials;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Kdyby\Doctrine\Entities\Attributes\Identifier;
use Kdyby\Doctrine\Entities\BaseEntity;
use Nette\Security\IIdentity;

/**
 * @ORM\Entity(repositoryClass="App\Model\Repository\UserRepository")
 *
 * @property string $mail
 * @property string $locale
 * @property string $currency
 * @property Address $billingAddress
 * @property Address $shippingAddress
 * @method User setMail(string $mail)
 * @method User setLocale(string $locale)
 * @method User setCurrency(string $code)
 */
class User extends BaseEntity implements IIdentity, IUserSocials
{

	use Identifier;
	use UserRoles;
	use UserPassword;
	use UserSocials;

	/** @ORM\Column(type="string", nullable=false, unique=true) */
	protected $mail;

	/** @ORM\Column(type="string", length=8, nullable=true) */
	protected $locale;

	/** @ORM\Column(type="string", length=8, nullable=true) */
	protected $currency;

	/** @ORM\OneToOne(targetEntity="Address") */
	protected $shippingAddress;

	/** @ORM\OneToOne(targetEntity="Address") */
	protected $billingAddress;

	public function __construct($mail = NULL)
	{
		$this->roles = new ArrayCollection;

		if ($mail) {
			$this->mail = $mail;
		}

		parent::__construct();
	}

	public function __toString()
	{
		return (string) $this->mail;
	}

	public function toArray()
	{
		return [
			'id' => $this->id,
			'mail' => $this->mail,
			'role' => $this->roles->toArray(),
		];
	}

	public function isNew()
	{
		return $this->id === NULL;
	}

	public function import(User $user)
	{
		return $this;
	}
	
	public function getShippingAddress($realShipping = FALSE)
	{
		if ($realShipping) {
			if ($this->shippingAddress && $this->shippingAddress->isComplete()) {
				return $this->shippingAddress;
			} else {
				return $this->billingAddress;
			}
		} else {
			return $this->shippingAddress;
		}
	}

}
