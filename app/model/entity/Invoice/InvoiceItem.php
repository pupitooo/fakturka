<?php

namespace App\Model\Entity;

use Doctrine\ORM\Mapping as ORM;
use h4kuna\Exchange\Exchange;
use Kdyby\Doctrine\Entities\Attributes\Identifier;
use Kdyby\Doctrine\Entities\BaseEntity;

/**
 * @ORM\Entity
 *
 * @property Invoice $invoice
 * @property string $name
 * @property Price $price
 * @property Vat $vat
 * @property int $quantity
 * @property-read float $rawPrice
 * @property-read bool $rawWithVat
 */
class InvoiceItem extends BaseEntity
{

	use Identifier;

	/** @ORM\ManyToOne(targetEntity="Invoice", inversedBy="items") */
	protected $invoice;

	/** @ORM\Column(type="string", nullable=true) */
	protected $name;

	/** @ORM\Column(type="float", nullable=true) */
	private $price;

	/** @ORM\Column(type="float", nullable=true) */
	private $vat;

	/** @ORM\Column(type="float", nullable=true) */
	protected $rawPrice;

	/** @ORM\Column(type="boolean") */
	protected $rawWithVat;

	/** @ORM\Column(type="integer") */
	protected $quantity;

	public function setPrice(Price $price)
	{
		$this->price = $price->withoutVat;
		$this->vat = $price->vat->value;
		return $this;
	}

	public function setRawPrice($rawValue, $rawWithVat)
	{
		$this->rawPrice = $rawValue;
		$this->rawWithVat = $rawWithVat;
		return $this;
	}

	public function getRawPrice($format = FALSE)
	{
		if ($format) {
			$vat = $this->getVat();
			return new Price($vat, $this->rawPrice, !$this->rawWithVat);
		} else {
			return $this->rawPrice;
		}
	}

	/** @return Price */
	public function getPrice()
	{
		$vat = $this->getVat();
		return new Price($vat, $this->price);
	}

	/** @return Vat */
	public function getVat()
	{
		return new Vat(NULL, $this->vat ? $this->vat : 0);
	}

	public function getTotalPrice(Exchange $exchange = NULL, $withVat = TRUE)
	{
		$price = $this->getPrice();
		$priceValue = $withVat ? $price->withVat : $price->withoutVat;
		$exchangedValue = $exchange ? $exchange->change($priceValue, NULL, NULL, Price::PRECISION) : $priceValue;
		return $exchangedValue * $this->quantity;
	}

	public function getRawTotalPrice($withVat = TRUE)
	{
		$price = $this->getRawPrice(TRUE);
		$priceValue = $withVat ? $price->withVat : $price->withoutVat;
		return $priceValue * $this->quantity;
	}

	public function changeRate($newRate)
	{
		$price = new Price($this->getVat(), $this->rawPrice * $newRate, !$this->rawWithVat);
		$this->setPrice($price);
		return $this;
	}

	function __toString()
	{
		return $this->name;
	}

}
