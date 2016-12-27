<?php

namespace App\Model\Facade;

use App\Model\Entity\Cost;
use App\Model\Repository\CostRepository;
use Kdyby\Doctrine\EntityManager;
use Nette\Object;
use Nette\Utils\DateTime;

class CostFacade extends Object
{

	/** @var EntityManager @inject */
	public $em;

	/** @var CostRepository */
	private $costRepo;

	public function __construct(EntityManager $em)
	{
		$this->costRepo = $em->getRepository(Cost::getClassName());
	}

	public function getForMonth(DateTime $date)
	{
		$fromDate = clone $date;
		$fromDate->modify('first day of this month');
		$toDate = clone $date;
		$toDate->modify('last day of this month');

		$costs = $this->costRepo->findBy([
			'dueDate >=' => $fromDate,
			'dueDate <=' => $toDate,
		]);
		return $costs;
	}

	public function getForMonthSum(DateTime $date, $withoutVat= TRUE)
	{
		$costs = $this->getForMonth($date);
		$sum = 0;
		foreach ($costs as $cost) {
			$sum += $withoutVat ? $cost->priceWithoutVat : $cost->priceWithVat;
		}
		return $sum;
	}

	public function getForMonthVatSum(DateTime $date)
	{
		$costs = $this->getForMonth($date);
		$sum = 0;
		foreach ($costs as $cost) {
			$sum += $cost->vatSum;
		}
		return $sum;
	}

	public function getForMonthCount(DateTime $date)
	{
		return count($this->getForMonth($date));
	}

}
