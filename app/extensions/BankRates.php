<?php

namespace h4kuna\Exchange\Driver;

use App\Model\Entity\BankRate;
use DateTime;
use GuzzleHttp\Exception\RequestException;
use h4kuna\Exchange\Currency\Property;
use Kdyby\Doctrine\DBALException;
use Kdyby\Doctrine\EntityManager;

class BankRates extends Cnb\Day
{

	/** @var  EntityManager */
	private $em;

	private $connectionFail = FALSE;

	public function __construct(EntityManager $em)
	{
		$this->em = $em;
	}

	protected function loadFromSource(DateTime $date = NULL)
	{
		try {
			$currencies = parent::loadFromSource($date);
			return $currencies;
		} catch (RequestException $e) {
			$this->connectionFail = TRUE;
			return $this->loadOldRates();
		}
	}

	protected function createProperty($row)
	{
		if ($this->connectionFail) {
			return $row;
		} else {
			$property = parent::createProperty($row);
			$this->saveBankRate($property);
			return $property;
		}
	}

	private function saveBankRate(Property $property)
	{
		try {
			$rateRepo = $this->em->getRepository(BankRate::getClassName());
			$rate = $rateRepo->find($property->getCode());
			if ($rate) {
				$rate->setValue($property->getForeing());
			} else {
				$rate = new BankRate($property->getCode(), $property->getForeing());
			}
			$rateRepo->save($rate);
		} catch (DBALException $e) {
		}
	}

	private function loadOldRates()
	{
		$rateRepo = $this->em->getRepository(BankRate::getClassName());
		$currencies = [];
		foreach ($rateRepo->findAll() as $rate) {
			$currencies[] = new Property(1, $rate->getCode(), $rate->getValue());
		}
		return $currencies;
	}

}
