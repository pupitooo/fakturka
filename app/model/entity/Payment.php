<?php

namespace App\Model\Entity;

use Doctrine\ORM\Mapping as ORM;
use Kdyby\Doctrine\Entities\Attributes\Identifier;
use Kdyby\Doctrine\Entities\BaseEntity;
use Knp\DoctrineBehaviors\Model;
use Nette\Utils\DateTime;

/**
 * @ORM\Entity(repositoryClass="App\Model\Repository\PaymentRepository")
 *
 * @property string $name
 * @property float $price
 * @property float $priceEur
 * @property float $rate
 * @property DateTime $date
 */
class Payment extends BaseEntity
{

	use Identifier;
	use Model\Timestampable\Timestampable;

	/** @ORM\Column(type="string") */
	protected $name;

	/** @ORM\Column(type="float") */
	protected $price;

	/** @ORM\Column(type="float", nullable=true) */
	protected $priceEur;

	/** @ORM\Column(type="float", nullable=true) */
	protected $rate;

	/** @ORM\Column(type="date") */
	protected $date;

	public function __construct()
	{
		$this->date = new DateTime();
		parent::__construct();
	}

	public function __toString()
	{
		return (string)$this->name;
	}

}
