<?php

namespace App\Model\Entity;

use Doctrine\ORM\Mapping as ORM;
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
 * @property-read Invoice[] $localInvoices
 * @property-read Invoice[] $foreignInvoices
 * @property-read DateTime $accountDate
 */
class Confession extends BaseEntity
{

	const KH_LIMIT = 10000;

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
		return new DateTime('1.' . $this->month . '.' . $this->year);
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

	public function getPriznKH_C1zaklad()
	{
		return $this->getPriznDP3_CI1(FALSE, FALSE);
	}

}
