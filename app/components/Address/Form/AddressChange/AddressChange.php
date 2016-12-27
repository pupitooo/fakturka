<?php

namespace App\Components\Address\Form;

use App\Components\BaseControl;
use App\Components\BaseControlException;
use App\Forms\Controls\TextInputBased\MetronicTextInputBase;
use App\Forms\Form;
use App\Forms\Renderers\MetronicFormRenderer;
use App\Model\Entity\Address;
use Nette\Utils\ArrayHash;

class AddressChange extends BaseControl
{

	/** @var Address */
	private $address;

	// <editor-fold desc="events">

	/** @var array */
	public $onAfterSave = [];

	// </editor-fold>

	/** @return Form */
	protected function createComponentForm()
	{
		$this->checkEntityExistsBeforeRender();

		$form = new Form();
		$form->setTranslator($this->translator);
		$form->setRenderer(new MetronicFormRenderer());

		$form->addGroup();
		$form->addText('name', 'cart.form.name', NULL, 100)
			->setRequired()
			->getControlPrototype()->class[] = MetronicTextInputBase::SIZE_L;
		$form->addText('street', 'cart.form.street', NULL, 100)
			->setRequired()
			->getControlPrototype()->class[] = MetronicTextInputBase::SIZE_L;
		$form->addText('city', 'cart.form.city', NULL, 100)
			->setRequired()
			->getControlPrototype()->class[] = MetronicTextInputBase::SIZE_L;
		$form->addText('zipcode', 'cart.form.zipcode', NULL, 10)
			->getControlPrototype()->class[] = MetronicTextInputBase::SIZE_S;
		$form->addSelect2('country', 'cart.form.country', Address::getCountries())
			->setRequired()
			->getControlPrototype()->class[] = MetronicTextInputBase::SIZE_L;

		$form->addGroup('Company Info');
		$form->addText('ico', 'cart.form.ico', NULL, 30)
			->getControlPrototype()->class[] = MetronicTextInputBase::SIZE_S;
		$form->addText('dic', 'cart.form.dic', NULL, 30)
			->getControlPrototype()->class[] = MetronicTextInputBase::SIZE_S;
		$form->addCheckSwitch('dicVerify', 'Verified', 'Yes', 'No');
		$form->addCheckSwitch('foreignBusiness', 'Foreign business', 'Yes', 'No');

		$form->addGroup('Contact');
		$form->addText('phone', 'cart.form.phone', NULL, 20)
			->getControlPrototype()->class[] = MetronicTextInputBase::SIZE_S;
		$form->addText('mail', 'cart.form.mail', NULL, 255)
			->getControlPrototype()->class[] = MetronicTextInputBase::SIZE_L;

		$form->addGroup();
		$form->addSubmit('save', 'Save');

		$form->setDefaults($this->getDefaults());
		$form->onSuccess[] = $this->formSucceeded;
		return $form;
	}

	public function formSucceeded(Form $form, $values)
	{
		$this->load($form, $values);
		if (!$form->hasErrors()) {
			$this->save();
			$this->onAfterSave($this->address);
		}
	}

	private function load(Form $form, ArrayHash $values)
	{
		$this->address->phone = $values->phone;
		$this->address->mail = $values->mail;
		$this->address->name = $values->name;
		$this->address->street = $values->street;
		$this->address->city = $values->city;
		$this->address->zipcode = $values->zipcode;
		$this->address->country = $values->country;
		$this->address->ico = $values->ico;
		$this->address->dic = $values->dic;
		$this->address->dicVerified = $values->dicVerify;
		$this->address->foreignBusiness = $values->foreignBusiness;

		return $this;
	}

	private function save()
	{
		$addressRepo = $this->em->getRepository(Address::getClassName());
		$addressRepo->save($this->address);
		return $this;
	}

	/** @return array */
	protected function getDefaults()
	{
		$values['name'] = $this->address->name;
		$values['street'] = $this->address->street;
		$values['city'] = $this->address->city;
		$values['country'] = $this->address->country;
		$values['zipcode'] = $this->address->zipcode;
		$values['mail'] = $this->address->mail;
		$values['phone'] = $this->address->phone;
		$values['ico'] = $this->address->ico;
		$values['dic'] = $this->address->dic;
		$values['dicVerify'] = $this->address->dicVerified;
		$values['foreignBusiness'] = $this->address->foreignBusiness;
		return $values;
	}

	private function checkEntityExistsBeforeRender()
	{
		if (!$this->address) {
			throw new BaseControlException('Use setAddress(\App\Model\Entity\Address) before render');
		}
	}

	// <editor-fold desc="setters & getters">

	public function setAddress(Address $address)
	{
		$this->address = $address;
		return $this;
	}

	// </editor-fold>
}

interface IAddressChangeFactory
{

	/** @return AddressChange */
	function create();
}
