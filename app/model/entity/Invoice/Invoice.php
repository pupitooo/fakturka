<?php

namespace App\Model\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use h4kuna\Exchange\Currency\IProperty;
use h4kuna\Exchange\Exchange;
use Kdyby\Doctrine\Entities\BaseEntity;
use Knp\DoctrineBehaviors\Model;
use Nette\Utils\DateTime;
use Nette\Utils\Strings;

/**
 * @ORM\Entity(repositoryClass="App\Model\Repository\InvoiceRepository")
 *
 * @property ArrayCollection $items
 * @property-read int $itemsCount
 * @property string $currency
 * @property-read float $rate
 * @property string $locale
 * @property bool $foreignBusiness
 * @property bool $storno
 * @property bool $vatPayer
 * @property Address $billingAddress
 * @property Address $shippingAddress
 * @property DateTime $dueDate
 * @property-read DateTime $supplyDate
 * @property-read float $totalPrice
 * @property DateTime $paymentDate
 */
class Invoice extends BaseEntity
{

	use Model\Timestampable\Timestampable;

	/**
	 * @ORM\Id
	 * @ORM\Column(type="integer")
	 */
	protected $id;

	/** @ORM\Column(type="boolean") */
	protected $storno = FALSE;

	/** @ORM\Column(type="boolean") */
	protected $vatPayer = TRUE;

	/** @ORM\OneToMany(targetEntity="InvoiceItem", mappedBy="invoice", cascade={"all"}, orphanRemoval=true) */
	protected $items;

	/** @ORM\ManyToOne(targetEntity="Address", cascade={"persist"}) */
	protected $billingAddress;

	/** @ORM\ManyToOne(targetEntity="Address", cascade={"persist"}) */
	protected $shippingAddress;

	/** @ORM\Column(type="date") */
	protected $dueDate;

	/** @ORM\Column(type="date", nullable=true) */
	protected $paymentDate;

	/** @ORM\Column(type="string", length=8, nullable=true) */
	protected $locale;

	/** @ORM\Column(type="string", length=8, nullable=true) */
	protected $currency;

	/** @ORM\Column(type="boolean") */
	protected $foreignBusiness;

	/** @var IProperty */
	private $tmpCurrency;

	/** @ORM\Column(type="float", nullable=true) */
	private $rate;

	public function __construct($id, $locale)
	{
		$this->id = $id;
		$this->locale = $locale;
		$this->items = new ArrayCollection();
		$this->dueDate = new DateTime();
		parent::__construct();
	}

	public function import(Invoice $invoice)
	{
		$items = new ArrayCollection();
		foreach ($invoice->items as $item) {
			$cloned = clone $item;
			$cloned->invoice = $this;
			$items->add($cloned);
		}
		$this->items = $items;
		$this->shippingAddress = $invoice->shippingAddress;
		$this->billingAddress = $invoice->billingAddress;
		$this->setCurrency($invoice->currency, $this->rate);
		$this->locale = $invoice->locale;
		$this->foreignBusiness = $invoice->foreignBusiness;
		return $this;
	}

	public function setCurrency($currency, $rate = NULL)
	{
		$this->currency = $currency;
		$this->rate = $rate;
		return $this;
	}

	public function getRate()
	{
		return $this->rate;
	}

	public function getItems()
	{
		return $this->items;
	}

	public function getItemsCount()
	{
		return count($this->items);
	}

	/** @return float */
	public function getTotalPrice(Exchange $exchange = NULL, $withVat = TRUE)
	{
		if ($exchange) {
			$this->setExchangeRate($exchange);
		}
		$totalPrice = 0;
		foreach ($this->items as $item) {
			$totalPrice += $item->getTotalPrice($exchange, $withVat);
		}
		return $totalPrice;
	}

	/** @return float */
	public function getVatSum(Exchange $exchange = NULL)
	{
		$withVat = $this->getTotalPrice($exchange, TRUE);
		$withoutVat = $this->getTotalPrice($exchange, FALSE);
		return $withVat - $withoutVat;
	}

	/** @return float */
	public function getRawTotalPrice($withVat = TRUE)
	{
		$totalPrice = 0;
		foreach ($this->items as $item) {
			$totalPrice += $item->getRawTotalPrice($withVat);
		}
		return $totalPrice;
	}

	/** @return float */
	public function getRawVatSum()
	{
		$withVat = $this->getRawTotalPrice(TRUE);
		$withoutVat = $this->getRawTotalPrice(FALSE);
		return $withVat - $withoutVat;
	}

	public function setPayment()
	{
		$this->paymentDate = new DateTime();
		return $this;
	}

	public function getSupplyDate($days = 10)
	{
		return new DateTime($this->dueDate->format('d.m.Y') . ' + ' . $days . 'days');
	}

	public function hasInverseMode()
	{
		return $this->shippingAddress->foreignBusiness;
	}

	public function hasOverKhLimit()
	{
		return $this->getTotalPrice() > Confession::KH_LIMIT;
	}

	public function __toString()
	{
		return (string)$this->id;
	}

	public function setExchangeRate(Exchange $exchange)
	{
		$currencyCode = Strings::upper($this->currency);
		if ($this->currency && array_key_exists($currencyCode, $exchange)) {
			if ($this->rate) {
				$exchange->addRate($currencyCode, $this->rate);
			}
		}
	}

	public function setExchangeWeb(Exchange $exchange)
	{
		$currencyCode = Strings::upper($this->currency);
		if ($this->currency && array_key_exists($currencyCode, $exchange)) {
			$this->tmpCurrency = $exchange->getWeb();
			$exchange->setWeb($currencyCode);
		}
	}

	public function removeExchangeRate(Exchange $exchange)
	{
		if ($this->currency && array_key_exists($this->currency, $exchange)) {
			if ($this->rate) {
				$exchange->removeRate($this->currency);
			}
			if ($this->tmpCurrency) {
				$exchange->setWeb($this->tmpCurrency);
			}
		}
	}

}
