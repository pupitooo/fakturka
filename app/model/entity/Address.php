<?php

namespace App\Model\Entity;

use App\Helpers;
use Doctrine\ORM\Mapping as ORM;
use Kdyby\Doctrine\Entities\Attributes\Identifier;
use Kdyby\Doctrine\Entities\BaseEntity;
use Nette\Utils\Html;
use Nette\Utils\Strings;

/**
 * @ORM\Entity
 *
 * @property string $name
 * @property string $street
 * @property string $city
 * @property string $zipcode
 * @property string $country
 * @property string $countryFormat
 * @property string $mail
 * @property string $phone
 * @property string $ico
 * @property string $icoVat
 * @property string $dic
 * @property string $dicVerified
 * @property string $note
 * @property bool $foreignBusiness
 * @property-read string $firstName
 * @property-read string $surname
 * @property-read string $streetOnly
 * @property-read string $streetNumber
 */
class Address extends BaseEntity
{

	use Identifier;

	/** @ORM\Column(type="string", length=256) */
	protected $name;

	/** @ORM\Column(type="string", length=100) */
	protected $street;

	/** @ORM\Column(type="string", length=100) */
	protected $city;

	/** @ORM\Column(type="string", length=10) */
	protected $zipcode;

	/** @ORM\Column(type="string", length=100) */
	protected $country;

	/** @ORM\Column(type="string", length=30, nullable=true) */
	protected $mail;

	/** @ORM\Column(type="string", length=30, nullable=true) */
	protected $phone;

	/** @ORM\Column(type="string", length=50, nullable=true) */
	protected $ico;

	/** @ORM\Column(type="string", length=50, nullable=true) */
	protected $dic;

	/** @ORM\Column(type="boolean") */
	protected $dicVerified = FALSE;

	/** @ORM\Column(type="string", length=512, nullable=true) */
	protected $note;

	/** @ORM\Column(type="boolean") */
	protected $foreignBusiness = FALSE;

	public function import(Address $address, $force = FALSE)
	{
		if ($force || $address->name) {
			$this->name = $address->name;
		}
		if ($force || $address->street) {
			$this->street = $address->street;
		}
		if ($force || $address->city) {
			$this->city = $address->city;
		}
		if ($force || $address->zipcode) {
			$this->zipcode = $address->zipcode;
		}
		if ($force || $address->country) {
			$this->country = $address->country;
		}
		if ($force || $address->phone) {
			$this->phone = $address->phone;
		}
		if ($force || $address->mail) {
			$this->mail = $address->mail;
		}
		if ($force || $address->ico) {
			$this->ico = $address->ico;
		}
		if ($force || $address->dic) {
			$this->dic = $address->dic;
		}
		if ($force || $address->dicVerified) {
			$this->dicVerified = $address->dicVerified;
		}
		if ($force || $address->note) {
			$this->note = $address->note;
		}
		if ($force || $address->foreignBusiness) {
			$this->foreignBusiness = $address->foreignBusiness;
		}
	}

	public function getFirstName()
	{
		$splitted = $this->splitName();
		if (array_key_exists(0, $splitted)) {
			return $splitted[0];
		}
		return NULL;
	}

	public function getSurname()
	{
		$splitted = $this->splitName();
		if (array_key_exists(1, $splitted)) {
			return $splitted[1];
		}
		return NULL;
	}

	public function getStreetOnly()
	{
		$splitted = $this->splitStreet();
		if (array_key_exists(0, $splitted)) {
			return $splitted[0];
		}
		return NULL;
	}

	public function getStreetNumber()
	{
		$splitted = $this->splitStreet();
		if (array_key_exists(1, $splitted)) {
			return $splitted[1];
		}
		return NULL;
	}

	private function splitName()
	{
		return Strings::split($this->name, '/\s/', PREG_SPLIT_NO_EMPTY);
	}

	private function splitStreet()
	{
		$splitted = array_map('strrev', Strings::split(strrev($this->street), '/\s/', PREG_SPLIT_NO_EMPTY));
		if (isset($splitted[0]) && preg_match('/^\d+/', $splitted[0])) {
			$number = $splitted[0];
			unset($splitted[0]);
		} else {
			$number = NULL;
		}
		$street = implode(' ', array_reverse($splitted));
		return [$street, $number];
	}

	public function __toString()
	{
		return (string)$this->name;
	}

	public function getCityFormat()
	{
		$separator = preg_match('/^\d+\s*\d+$/', $this->zipcode) ? ' ' : ($this->zipcode ? ', ' : NULL);
		return Helpers::concatStrings($separator, $this->zipcode, $this->city);
	}

	public function getCountryFormat()
	{
		return $this->getCountry(TRUE);
	}

	public function getCountry($formated = FALSE)
	{
		if ($formated) {
			$countries = self::getCountries();
			if (array_key_exists($this->country, $countries)) {
				return $countries[$this->country];
			}
		}
		return $this->country;
	}

	public function getIco($formated = TRUE)
	{
		if ($formated) {
			return $this->formatNumber($this->ico);
		}
		return $this->ico;
	}

	public function getDic($formated = TRUE)
	{
		if ($formated) {
			$hasPrefix = preg_match('/^([A-z]+)\s*(\w+)$/', $this->dic, $match);
			if ($hasPrefix) {
				return $match[1] . ' ' . preg_replace('/\s/', '', $match[2]);
			}
		}
		return $this->dic;
	}

	public function getDicCode()
	{
		if (preg_match('/^([A-z]+)\s*(\w+)$/', $this->dic, $match)) {
			return $match[1];
		}
		return NULL;
	}

	public function getDicNumber()
	{
		if (preg_match('/^([A-z]+)\s*(\w+)$/', $this->dic, $match)) {
			return preg_replace('/\s/', '', $match[2]);
		}
		return NULL;
	}

	public function format()
	{
		$lineSeparator = Html::el('br');
		$name = $this->name;
		$street = $this->street;
		$city = $this->getCityFormat();
		$country = $this->getCountryFormat();
		$address = Helpers::concatStrings($lineSeparator, $name, $street, $city, $country);
		return $address;
	}

	public function verifyDic()
	{
		$client = new \SoapClient("http://ec.europa.eu/taxation_customs/vies/checkVatService.wsdl");
		if ($client) {
			$params = array('countryCode' => $this->getDicCode(), 'vatNumber' => $this->getDicNumber());
			try {
				$r = $client->checkVat($params);
				return $r->valid;
			} catch (\SoapFault $e) {
				return NULL;
			}
		} else {
			RETURN NULL;
		}
	}

	public static function formatNumber($value)
	{
		$number = preg_replace('/\s+/', '', $value);
		$numberLen = strlen($number);
		if ($numberLen > 6) {
			return substr($number, 0, 3) . ' ' . substr($number, 3, $numberLen - 6) . ' ' . substr($number, $numberLen - 3);
		}
		return $number;
	}

	public static function getCountries()
	{
		$countries = [
			'CZ' => 'Česká republika',
			'SK' => 'Slovenská republika',
			'IE' => 'Ireland',
			'GB' => 'United Kingdom',
			'AT' => 'Austria',
			'BE' => 'Belgium',
			'BG' => 'Bulgaria',
			'HR' => 'Croatia',
			'DK' => 'Denmark',
			'EE' => 'Estonia',
			'FI' => 'Finland',
			'FR' => 'France',
			'DE' => 'Germany',
			'HU' => 'Hungary',
			'IT' => 'Italy',
			'LV' => 'Latvia',
			'LT' => 'Lithuania',
			'LU' => 'Luxembourg',
			'NL' => 'Netherlands',
			'PL' => 'Poland',
			'PT' => 'Portugal',
			'RO' => 'Romania',
			'SI' => 'Slovenia',
			'ES' => 'Spain',
			'SE' => 'Sweden',
		];
		return $countries;
	}

}
