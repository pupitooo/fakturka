<?php

namespace App\Model\Entity;

use Doctrine\ORM\Mapping as ORM;
use h4kuna\Exchange\Exchange;
use Kdyby\Doctrine\Entities\BaseEntity;
use Knp\DoctrineBehaviors\Model;
use Nette\Utils\DateTime;

/**
 * @ORM\Entity(repositoryClass="App\Model\Repository\ConfessionRepository")
 *
 * @property int $month
 * @property int $year
 * @property string $username
 * @property string $password
 * @property DateTime $sendDate
 * @property Invoice[] $invoices
 * @property Cost[] $costs
 * @property-read Invoice[] $localInvoices
 * @property-read Invoice[] $foreignInvoices
 * @property-read DateTime $accountDate
 */
class Confession extends BaseEntity
{

	const KH_LIMIT = 10000;

	const YEAR_RENT = 24000;
	const RENT_COSTS_PERCENTAGE = 0.3;
	const TAXPAYER_DISCOUNT = 24840;
	const TAX_PERCENTAGE = 0.15;
	const COSTS_PERCENTAGE = 0.6;

	use Model\Timestampable\Timestampable;

	/** @ORM\Id @ORM\Column(type="smallint") */
	protected $month;

	/** @ORM\Id @ORM\Column(type="smallint") */
	protected $year;

	/** @ORM\Column(type="date") */
	protected $sendDate;

	/** @ORM\Column(type="string", length=50) */
	protected $username;

	/** @ORM\Column(type="string", length=50) */
	protected $password;

	/** @var array */
	protected $invoices = [];

	/** @var array */
	protected $costs = [];

	/** @var float */
	protected $costsSum = 0;

	/** @var float */
	protected $costsVat = 0;

	public function __construct($month, $year)
	{
		$this->month = (int)$month;
		$this->year = (int)$year;
		$this->sendDate = new DateTime();
		parent::__construct();
	}

	public function getAccountDate()
	{
		return new DateTime('1.' . ($this->month ? $this->month : '1') . '.' . $this->year);
	}

	public function __toString()
	{
		return $this->getAccountDate()->format('m/Y');
	}

	public function getLocalInvoices()
	{
		$invoices = [];
		foreach ($this->invoices as $invoice) {
			if (!$invoice->hasInverseMode()) {
				$invoices[] = $invoice;
			}
		}
		return $invoices;
	}

	public function getForeignInvoices($byDph = TRUE)
	{
		$invoices = [];
		foreach ($this->invoices as $invoice) {
			if ($invoice->hasInverseMode()) {
				if ($byDph) {
					$invoices[$invoice->shippingAddress->dic][] = $invoice;
				} else {
					$invoices[] = $invoice;
				}
			}
		}
		return $invoices;
	}

	public static function getConfessionRate($currency, $year, Exchange $exchange = NULL)
	{
		$rates = [
			'EUR' => [
				2017 => 26.29, // http://www.kurzy.cz/kurzy-men/jednotny-kurz/2017/
				2016 => 27.04, // http://www.kurzy.cz/kurzy-men/jednotny-kurz/2016/
				2015 => 27.27, // http://www.kurzy.cz/kurzy-men/jednotny-kurz/2015/
				2014 => 27.55, // http://www.kurzy.cz/kurzy-men/jednotny-kurz/2014/
				2013 => 26.03, // http://www.kurzy.cz/kurzy-men/jednotny-kurz/2013/
			]
		];

		if (array_key_exists($currency, $rates)) {
			if ($year instanceof DateTime) {
				$year = $year->format('Y');
			}
			if (array_key_exists($year, $rates[$currency])) {
				$rate = $rates[$currency][$year];
			} else {
				$rate = $exchange ? $exchange[$currency]->getHome() : current($rates[$currency]);
			}
		} else {
			$rate = $exchange ? $exchange->getDefault()->getHome() : 1;
		}

		return $rate;
	}

	public function getPriznDP3_CI1($vat = TRUE, $rounded = TRUE)
	{
		$sum = 0;
		foreach ($this->invoices as $invoice) {
			if (!$invoice->hasInverseMode()) {
				if ($vat) {
					$sum += $invoice->getVatSum();
				} else {
					$sum += $invoice->getTotalPrice(NULL, FALSE);
				}
			}
		}
		return $rounded ? round($sum) : $sum;
	}

	public function getPriznDP3_CI1zaklad()
	{
		return $this->getPriznDP3_CI1(FALSE);
	}

	public function getPriznDP3_CII21()
	{
		$sum = 0;
		foreach ($this->invoices as $invoice) {
			if ($invoice->hasInverseMode()) {
				$sum += $invoice->totalPrice;
			}
		}
		return round($sum);
	}

	public function getPriznDP3_CIV40()
	{
		return round($this->costsVat);
	}

	public function getPriznDP3_CIV40zaklad()
	{
		return round($this->costsSum);
	}

	public function getPriznDP3_CVI62()
	{
		return $this->getPriznDP3_CI1();
	}

	public function getPriznDP3_CVI63()
	{
		return $this->getPriznDP3_CIV40();
	}

	public function getPriznDP3_CVI64()
	{
		$diff = $this->getPriznDP3_CVI62() - $this->getPriznDP3_CVI63();
		return $diff > 0 ? round($diff) : 0;
	}

	public function getPriznDP3_CVI65()
	{
		$diff = $this->getPriznDP3_CVI63() - $this->getPriznDP3_CVI62();
		return $diff > 0 ? round($diff) : 0;
	}

	public function getPriznKH_A5($vat = TRUE)
	{
		$sum = 0;
		foreach ($this->invoices as $invoice) {
			if (!$invoice->hasInverseMode() && !$invoice->hasOverKhLimit()) {
				if ($vat) {
					$sum += $invoice->getVatSum();
				} else {
					$sum += $invoice->getTotalPrice(NULL, FALSE);
				}
			}
		}
		return $sum;
	}

	public function getPriznKH_A5zaklad()
	{
		return $this->getPriznKH_A5(FALSE);
	}

	public function getPriznKH_B3($vat = TRUE)
	{
		$sum = 0;
		foreach ($this->costs as $cost) {
			if (!$cost->hasOverKhLimit()) {
				if ($vat) {
					$sum += $cost->vatSum;
				} else {
					$sum += $cost->priceWithoutVat;
				}
			}
		}
		return $sum;
	}

	public function getPriznKH_B3zaklad()
	{
		return $this->getPriznKH_B3(FALSE);
	}

	public function getPriznKH_C1zaklad()
	{
		return $this->getPriznDP3_CI1(FALSE, FALSE);
	}

	public function getPriznKH_C1r40()
	{
		$sum = 0;
		foreach ($this->costs as $cost) {
			$sum += $cost->priceWithoutVat;
		}
		return $sum;
	}

	public function getPriznFDP5_kc_prij9()
	{
		return round(self::YEAR_RENT);
	}

	public function getPriznFDP5_kc_rozdil9()
	{
		return round(self::YEAR_RENT - $this->getPriznFDP5_kc_vyd9());
	}

	public function getPriznFDP5_kc_vyd9()
	{
		return round(self::YEAR_RENT * self::RENT_COSTS_PERCENTAGE);
	}

	public function getPriznFDP5_kc_prij7()
	{
		$sum = 0;
		foreach ($this->invoices as $invoice) {
			$rate = self::getConfessionRate($invoice->currency, $this->year);
			$sum += $invoice->getTotalCountedPrice($rate, FALSE);
		}
		return round($sum);
	}

	public function getPriznFDP5_kc_vyd7()
	{
		return round($this->getPriznFDP5_kc_prij7() * self::COSTS_PERCENTAGE);
	}

	public function getPriznFDP5_kc_hosp_rozd()
	{
		return round($this->getPriznFDP5_kc_prij7() - $this->getPriznFDP5_kc_vyd7());
	}

	public function getPriznFDP5_pr_sazba()
	{
		return self::COSTS_PERCENTAGE * 100;
	}

	public function getPriznFDP5_kc_zakldan()
	{
		return round($this->getPriznFDP5_kc_hosp_rozd() + $this->getPriznFDP5_kc_rozdil9());
	}

	public function getPriznFDP5_kc_zdzaokr()
	{
		return floor($this->getPriznFDP5_kc_zakldan() / 100) * 100;
	}

	public function getPriznFDP5_da_dan16()
	{
		return round($this->getPriznFDP5_kc_zdzaokr() * self::TAX_PERCENTAGE);
	}

	public function getPriznFDP5_uhrn_slevy35ba()
	{
		return self::TAXPAYER_DISCOUNT;
	}

	public function getPriznFDP5_kc_zbyvpred()
	{
		return round($this->getPriznFDP5_da_dan16() - $this->getPriznFDP5_uhrn_slevy35ba());
	}

}
