<?php

namespace App\Model\Entity;

use Doctrine\ORM\Mapping as ORM;
use Kdyby\Doctrine\Entities\Attributes\Identifier;
use Kdyby\Doctrine\Entities\BaseEntity;
use Knp\DoctrineBehaviors\Model;
use Nette\Utils\DateTime;

/**
 * @ORM\Entity(repositoryClass="App\Model\Repository\CostRepository")
 *
 * @property string $name
 * @property float $priceWithoutVat
 * @property float $priceWithVat
 * @property Vat $vat
 * @property float $vatSum
 * @property DateTime $dueDate
 * @property DateTime $confessionDate
 * @property DateTime $paymentDate
 */
class Cost extends BaseEntity
{

	use Identifier;
	use Model\Timestampable\Timestampable;

	/** @ORM\Column(type="string") */
	protected $name;

	/** @ORM\Column(type="float") */
	protected $priceWithoutVat;

	/** @ORM\Column(type="float") */
	protected $priceWithVat;

	/** @ORM\Column(type="float") */
	protected $vatSum;

	/** @ORM\Column(type="float") */
	private $vat;

	/** @ORM\Column(type="date") */
	protected $dueDate;

	/** @ORM\Column(type="date") */
	protected $confessionDate;

	/** @ORM\Column(type="date", nullable=true) */
	protected $paymentDate;

	public function __construct()
	{
		$this->dueDate = new DateTime();
		parent::__construct();
	}

	public function setVat(Vat $vat)
	{
		$this->vat = $vat->value;
	}

	public function getVat()
	{
		return new Vat(NULL, $this->vat ? $this->vat : 0);
	}

	public function __toString()
	{
		return (string)$this->name;
	}

}
