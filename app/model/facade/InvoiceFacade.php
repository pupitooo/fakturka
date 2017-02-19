<?php

namespace App\Model\Facade;

use App\Model\Entity\Confession;
use App\Model\Entity\Invoice;
use App\Model\Repository\InvoiceRepository;
use h4kuna\Exchange\Exchange;
use Kdyby\Doctrine\EntityManager;
use Nette\Object;
use Nette\Utils\DateTime;

class InvoiceFacade extends Object
{

	const PREFIX = '15';

	/** @var EntityManager @inject */
	public $em;

	/** @var Exchange @inject */
	public $exchange;

	/** @var InvoiceRepository */
	private $invoiceRepo;

	public function __construct(EntityManager $em)
	{
		$this->invoiceRepo = $em->getRepository(Invoice::getClassName());
	}

	public function getNewId($year = NULL)
	{
		if (!$year) {
			$year = date('Y');
		}
		$invoices = $this->invoiceRepo->findBy([
			'dueDate >=' => DateTime::from("1.1.$year"),
			'dueDate <' => DateTime::from("1.1.$year + 1 year"),
		]);
		$newId = count($invoices) + 1;
		$formatedNewId = str_pad($newId, 4, '0', STR_PAD_LEFT);
		return $year . self::PREFIX . $formatedNewId;
	}

	public function getForMonthSum(DateTime $date, $withoutVat = TRUE)
	{
		$invoices = $this->getForMonth($date);
		$sum = 0;
		foreach ($invoices as $invoice) {
			$sum += $invoice->getTotalPrice(NULL, !$withoutVat);
		}
		return $sum;
	}

	public function getForYearSum(DateTime $date, $withoutVat = TRUE)
	{
		$invoices = $this->getForYear($date);
		$sum = 0;
		foreach ($invoices as $invoice) {
			$sum += $invoice->getTotalPrice(NULL, !$withoutVat);
		}
		return $sum;
	}

	public function getForYearSumForConfession(DateTime $date, $withoutVat = TRUE)
	{
		$invoices = $this->getForYear($date, TRUE);
		$sum = 0;
		foreach ($invoices as $invoice) {
			$rate = Confession::getConfessionRate($invoice->currency, $date);
			$sum += $invoice->getTotalCountedPrice($rate, !$withoutVat);
		}
		return $sum;
	}

	public function getForMonthVatSum(DateTime $date)
	{
		$invoices = $this->getForMonth($date);
		$sum = 0;
		foreach ($invoices as $invoice) {
			$sum += $invoice->vatSum;
		}
		return $sum;
	}

	public function getForYearVatSum(DateTime $date)
	{
		$invoices = $this->getForYear($date);
		$sum = 0;
		foreach ($invoices as $invoice) {
			$sum += $invoice->vatSum;
		}
		return $sum;
	}

	public function getForMonth(DateTime $date)
	{
		$fromDate = clone $date;
		$fromDate->modify('first day of this month');
		$toDate = clone $date;
		$toDate->modify('last day of this month');

		$invoices = $this->invoiceRepo->findBy([
			'dueDate >=' => $fromDate,
			'dueDate <=' => $toDate,
		]);
		return $invoices;
	}

	public function getForYear(DateTime $date, $onlyPaidInThisYear = FALSE)
	{
		$year = $date->format('Y');

		$fromDate = new DateTime('1.1.' . $year);
		$toDate = new DateTime('31.12.' . $year);

		if ($onlyPaidInThisYear) {
			$conditions = [
				'paymentDate >=' => $fromDate,
				'paymentDate <=' => $toDate,
			];
		} else {
			$conditions = [
				'dueDate >=' => $fromDate,
				'dueDate <=' => $toDate,
			];
		}
		$invoices = $this->invoiceRepo->findBy($conditions);
		return $invoices;
	}

	public function checkIfMonthHasSummaryReport(DateTime $date)
	{
		// zda je nějaká faktura vystavena s povinností odvést DPH
		$invoices = $this->getForMonth($date);
		foreach ($invoices as $invoice) {
			if ($invoice->hasInverseMode()) {
				return TRUE;
			}
		}
		return FALSE;
	}

	public function checkIfMonthHasCheckReport(DateTime $date)
	{
		// zda je nějaká faktura vystavena s DPH
		$invoices = $this->getForMonth($date);
		foreach ($invoices as $invoice) {
			if (!$invoice->hasInverseMode()) {
				return TRUE;
			}
		}
		return FALSE;
	}

	public function getForMonthCount(DateTime $date)
	{
		return count($this->getForMonth($date));
	}

	/**
	 * Sleva na poplatníka = 24840
	 * Daň 15% musí být 24840 => 165600 = X
	 * X - 16800 (základ z nájmu) = 148800 = 40% ze příjmů, ze kterých nic neplatím
	 * 372000
	 */
	public function getYearLimit()
	{
		$rentBase = Confession::YEAR_RENT * (1 - Confession::RENT_COSTS_PERCENTAGE);
		$x = Confession::TAXPAYER_DISCOUNT / Confession::TAX_PERCENTAGE;
		$ceilingX = round($x / 100) * 100;
		$baseWithoutCosts = $ceilingX - $rentBase;
		$yearLimit = $baseWithoutCosts / (1 - Confession::COSTS_PERCENTAGE);

		return $yearLimit;
	}

}
