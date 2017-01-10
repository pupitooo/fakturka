<?php

namespace App\Model\Facade;

use App\Model\Entity\Payment;
use App\Model\Repository\PaymentRepository;
use Kdyby\Doctrine\EntityManager;
use Nette\Object;
use Nette\Utils\DateTime;

class PaymentFacade extends Object
{

	const CARGOS = [
		2013 => 3638,
		2014 => 3646,
		2015 => 3740,
		2016 => 3766,
		2017 => 3796,
	];

	/** @var EntityManager @inject */
	public $em;

	/** @var PaymentRepository */
	private $paymentRepo;

	public function __construct(EntityManager $em)
	{
		$this->paymentRepo = $em->getRepository(Payment::getClassName());
	}

	public function getForMonth(DateTime $date)
	{
		$fromDate = clone $date;
		$fromDate->modify('first day of this month');
		$toDate = clone $date;
		$toDate->modify('last day of this month');

		$payments = $this->paymentRepo->findBy([
			'accountDate >=' => $fromDate,
			'accountDate <=' => $toDate,
		]);
		return $payments;
	}

	public function getForYear(DateTime $date)
	{
		$year = $date->format('Y');

		$fromDate = new DateTime('1.1.' . $year);
		$toDate = new DateTime('31.12.' . $year);

		$payments = $this->paymentRepo->findBy([
			'accountDate >=' => $fromDate,
			'accountDate <=' => $toDate,
		]);
		return $payments;
	}

	public function getForMonthSum(DateTime $date)
	{
		$payments = $this->getForMonth($date);
		$sum = 0;
		foreach ($payments as $payment) {
			$sum += $payment->price;
		}
		return $sum;
	}

	public function getForYearSum(DateTime $date, $excludeCargo = FALSE)
	{
		$payments = $this->getForYear($date);
		$sum = 0;
		foreach ($payments as $payment) {
			$sum += $payment->price;
		}
		if ($excludeCargo) {
			$sum -= $this->getForYearCargo($date);
		}
		return $sum;
	}

	public function getForYearCargo(DateTime $date)
	{
		$year = $date->format('Y');
		$months = $year == 2013 ? 10 : ($year === date('Y') ? date('n') - 1 : 12);
		$cargoValue = self::CARGOS[$year] * $months;
		return $cargoValue;
	}

	public function getForMonthCount(DateTime $date)
	{
		return count($this->getForMonth($date));
	}

	public function getForYearCount(DateTime $date)
	{
		return count($this->getForYear($date));
	}

}
