<?php

namespace App\Model\Facade;

use App\Model\Entity\Confession;
use App\Model\Repository\ConfessionRepository;
use Kdyby\Doctrine\EntityManager;
use Nette\Object;
use Nette\Utils\DateTime;

class ConfessionFacade extends Object
{

	/** @var EntityManager @inject */
	public $em;

	/** @var ConfessionRepository */
	private $confessionRepo;

	public function __construct(EntityManager $em)
	{
		$this->confessionRepo = $em->getRepository(Confession::getClassName());
	}

	public function getConfessionByDate(DateTime $date)
	{
		return $this->confessionRepo->findOneBy([
			'month' => $date->format('m'),
			'year' => $date->format('Y'),
		]);
	}

}
