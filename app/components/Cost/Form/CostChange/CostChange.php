<?php

namespace App\Components\Cost\Form;

use App\Components\BaseControl;
use App\Components\BaseControlException;
use App\Forms\Controls\Custom\DatePicker;
use App\Forms\Controls\TextInputBased\MetronicTextInputBase;
use App\Forms\Form;
use App\Forms\Renderers\MetronicFormRenderer;
use App\Model\Entity\Cost;
use App\Model\Entity\Price;
use App\Model\Entity\Vat;
use App\Model\Facade\VatFacade;
use Nette\Utils\ArrayHash;
use Nette\Utils\DateTime;

class CostChange extends BaseControl
{

	/** @var Cost */
	private $cost;

	/** @var VatFacade @inject */
	public $vatFacade;

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

		$form->addDatePicker('dueDate', 'Due date')
			->setSize(DatePicker::SIZE_S)
			->setRequired();
		$form->addDatePicker('paymentDate', 'Payment date')
			->setSize(DatePicker::SIZE_S);
		$form->addDatePicker('confessionDate', 'Confession date')
			->setSize(DatePicker::SIZE_S);

		$form->addText('name', 'Name')
			->setRequired();

		$form->addText('dic', 'cart.form.dic')
			->setAttribute('class', [MetronicTextInputBase::SIZE_M]);
		$form->addText('invoiceNumber', 'Invoice')
			->setAttribute('class', [MetronicTextInputBase::SIZE_M]);

		$form->addText('withoutVat', 'Price without Vat')
			->setAttribute('class', ['mask_currency', MetronicTextInputBase::SIZE_S]);

		$form->addText('vatSum', 'Vat sum')
			->setAttribute('class', ['mask_currency', MetronicTextInputBase::SIZE_S]);

		$form->addText('withVat', 'Price with Vat')
			->setAttribute('class', ['mask_currency', MetronicTextInputBase::SIZE_S]);

		$form->addSelect2('vat', 'Vat', $this->vatFacade->getValues())
			->getControlPrototype()->class[] = MetronicTextInputBase::SIZE_XS;

		$form->addSubmit('save', 'Save');
		$form->addSubmit('saveAdd', 'Save & Add');

		$form->setDefaults($this->getDefaults());
		$form->onSuccess[] = $this->formSucceeded;
		return $form;
	}

	public function formSucceeded(Form $form, $values)
	{
		$this->load($form, $values);
		if (!$form->hasErrors()) {
			$this->save();
			$this->onAfterSave($this->cost, $form['saveAdd']->isSubmittedBy());
		}
	}

	private function load(Form $form, ArrayHash $values)
	{
		if ($values->name && ($values->withoutVat || $values->withVat)) {
			$this->cost->name = $values->name;
			$this->cost->dueDate = $values->dueDate;
			$this->cost->invoiceNumber = $values->invoiceNumber;
			$this->cost->dic = $values->dic;

			$vatRepo = $this->em->getRepository(Vat::getClassName());
			$vat = $vatRepo->find($values->vat);
			$this->cost->vat = $vat;

			if ($values->withoutVat) {
				$this->cost->priceWithoutVat = Price::strToFloat($values->withoutVat);
			} else if ($values->withVat && $values->vatSum) {
				$this->cost->priceWithoutVat = Price::strToFloat($values->withVat) - Price::strToFloat($values->vatSum);
			} else if ($values->withVat) {
				$price = new Price($this->cost->vat, $values->withVat, FALSE);
				$this->cost->priceWithoutVat = $price->withoutVat;
			} else {
				$form['withoutVat']->addError('Insert price without vat');
			}

			if ($values->withVat) {
				$this->cost->priceWithVat = Price::strToFloat($values->withVat);
			} else if ($values->withoutVat && $values->vatSum) {
				$this->cost->priceWithVat = Price::strToFloat($values->withoutVat) + Price::strToFloat($values->vatSum);
			} else if ($values->withoutVat) {
				$price = new Price($this->cost->vat, $values->withoutVat, TRUE);
				$this->cost->priceWithVat = $price->withVat;
			}

			if ($values->vatSum) {
				$this->cost->vatSum = Price::strToFloat($values->vatSum);
			} else {
				$this->cost->vatSum = $this->cost->priceWithVat - $this->cost->priceWithoutVat;
			}

			if ($values->paymentDate) {
				$this->cost->paymentDate = $values->paymentDate;
			}

			$this->cost->confessionDate = $values->confessionDate ? $values->confessionDate : $values->dueDate;
		} else {
			$form['withoutVat']->addError('Insert price without vat');
		}
		return $this;
	}

	private function save()
	{
		$costRepo = $this->em->getRepository(Cost::getClassName());
		$costRepo->save($this->cost);
		return $this;
	}

	/** @return array */
	protected function getDefaults()
	{
		$values = [
			'name' => $this->cost->name,
			'invoiceNumber' => $this->cost->invoiceNumber,
			'dic' => $this->cost->dic,
			'vat' => $this->cost->vat->id,
			'vatSum' => $this->cost->vatSum,
			'withoutVat' => $this->cost->priceWithoutVat,
			'withVat' => $this->cost->priceWithVat,
			'dueDate' => $this->cost->dueDate ? $this->cost->dueDate : new DateTime(),
			'confessionDate' => $this->cost->confessionDate ? $this->cost->confessionDate : new DateTime(),
			'paymentDate' => $this->cost->paymentDate ? $this->cost->paymentDate : new DateTime(),
		];
		return $values;
	}

	private function checkEntityExistsBeforeRender()
	{
		if (!$this->cost) {
			throw new BaseControlException('Use setCost(\App\Model\Entity\Cost) before render');
		}
	}

	// <editor-fold desc="setters & getters">

	public function setCost(Cost $cost)
	{
		$this->cost = $cost;
		return $this;
	}

	// </editor-fold>
}

interface ICostChangeFactory
{

	/** @return CostChange */
	function create();
}
