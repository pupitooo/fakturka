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
 * @property-read DateTime $accountDate
 */
class Confession extends BaseEntity
{

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

	public function __construct($month, $year)
	{
		$this->month = $month;
		$this->year = $year;
		$this->sendDate = new DateTime();
		parent::__construct();
	}

	public function getAccountDate()
	{
		return new DateTime($this->year . '-' . $this->month . '-01');
	}

	public function __toString()
	{
		return $this->getAccountDate()->format('m/Y');
	}

}
