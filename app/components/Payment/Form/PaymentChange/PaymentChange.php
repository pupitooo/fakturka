<?php

namespace App\Components\Payment\Form;

use App\Components\BaseControl;
use App\Components\BaseControlException;
use App\Forms\Controls\Custom\DatePicker;
use App\Forms\Controls\TextInputBased\MetronicTextInputBase;
use App\Forms\Form;
use App\Forms\Renderers\MetronicFormRenderer;
use App\Model\Entity\Payment;
use App\Model\Entity\Price;
use App\Model\Facade\VatFacade;
use Nette\Utils\ArrayHash;
use Nette\Utils\DateTime;

class PaymentChange extends BaseControl
{

	/** @var Payment */
	private $payment;

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

		$form->addDatePicker('date', 'Date')
			->setSize(DatePicker::SIZE_S)
			->setRequired();

		$form->addText('name', 'Name')
			->setRequired();

		$form->addText('price', 'Price')
			->setAttribute('class', ['mask_currency', MetronicTextInputBase::SIZE_S]);

		$form->addText('priceEur', 'Price in €')
			->setAttribute('class', [MetronicTextInputBase::SIZE_S]);

		$form->addText('rate', 'Rate')
			->setRequired()
			->setAttribute('class', [MetronicTextInputBase::SIZE_S]);

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
			$this->onAfterSave($this->payment, $form['saveAdd']->isSubmittedBy());
		}
	}

	private function load(Form $form, ArrayHash $values)
	{
		$this->payment->name = $values->name;
		if ($values->price) {
			$this->payment->price = Price::strToFloat($values->price);
		} elseif ($values->priceEur) {
			$this->payment->price = (Price::strToFloat($values->priceEur) * Price::strToFloat($values->rate));
			$this->payment->priceEur = Price::strToFloat($values->priceEur);
		}
		$this->payment->rate = Price::strToFloat($values->rate);
		$this->payment->date = $values->date;
		return $this;
	}

	private function save()
	{
		$paymentRepo = $this->em->getRepository(Payment::getClassName());
		$paymentRepo->save($this->payment);
		return $this;
	}

	/** @return array */
	protected function getDefaults()
	{
		$values = [
			'name' => $this->payment->name,
			'price' => $this->payment->price,
			'priceEur' => $this->payment->priceEur,
			'rate' => $this->payment->rate ? $this->payment->rate : 27,
			'date' => $this->payment->date ? $this->payment->date : new DateTime(),
		];
		return $values;
	}

	private function checkEntityExistsBeforeRender()
	{
		if (!$this->payment) {
			throw new BaseControlException('Use setPayment(\App\Model\Entity\Payment) before render');
		}
	}

	// <editor-fold desc="setters & getters">

	public function setPayment(Payment $payment)
	{
		$this->payment = $payment;
		return $this;
	}

	// </editor-fold>
}

interface IPaymentChangeFactory
{

	/** @return PaymentChange */
	function create();
}
