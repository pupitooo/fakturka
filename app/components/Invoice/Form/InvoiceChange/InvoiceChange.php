<?php

namespace App\Components\Invoice\Form;

use App\Components\BaseControl;
use App\Components\BaseControlException;
use App\Forms\Controls\TextInputBased\MetronicTextInputBase;
use App\Forms\Form;
use App\Forms\Renderers\MetronicFormRenderer;
use App\Model\Entity\Address;
use App\Model\Entity\Invoice;
use App\Model\Entity\Price;
use App\Model\Facade\InvoiceFacade;
use Nette\Utils\ArrayHash;
use Nette\Utils\DateTime;
use Nette\Utils\Html;
use Nette\Utils\Strings;

class InvoiceChange extends BaseControl
{

	const CSOB_CURRENCY = 'http://www.cnb.cz/miranda2/m2/cs/financni_trhy/devizovy_trh/kurzy_devizoveho_trhu/vybrane.html?mena=EUR';

	/** @var InvoiceFacade @inject */
	public $invoiceFacade;

	/** @var Invoice */
	private $invoice;

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

		$addressRepo = $this->em->getRepository(Address::getClassName());
		$addresses = $addressRepo->findPairs('name', [
			'dicVerified' => 'DESC',
			'foreignBusiness' => 'DESC',
			'name' => 'ASC',
		]);

		$form->addCheckSwitch('vatPayer', 'VAT payer', 'Yes', 'No');
//		$form->addCheckSwitch('storno', 'Storno', 'Yes', 'No');

		$form->addSelect2('address', 'Address', $addresses)
			->setRequired()
			->getControlPrototype()->class[] = MetronicTextInputBase::SIZE_XL;

		$form->addDatePicker('dueDate', 'Due date');
		$form->addDatePicker('paymentDate', 'Payment date');

		$form->addSelect2('locale', 'Language', [
			'cs' => 'CZ',
			'en' => 'EN',
		])
			->setRequired()
			->getControlPrototype()->class[] = MetronicTextInputBase::SIZE_S;
		$form->addSelect2('currency', 'Currency', [
			'CZK' => 'CZK',
			'EUR' => 'EUR',
		])
			->setRequired()
			->getControlPrototype()->class[] = MetronicTextInputBase::SIZE_S;

		$eur = $this->exchange['EUR'];
		$czk = $this->exchange['CZK'];
		$czkSymbol = $czk->getFormat()->symbol;
		$eurSymbol = $eur->getFormat()->symbol;

		$from = (new DateTime('- 7 days'))->format('d.m.Y');
		$to = (new DateTime())->format('d.m.Y');
		$csobLink = Html::el('a')->setHtml('CNB:')
			->href(self::CSOB_CURRENCY . '&od=' . $from . '&do=' . $to . '')
			->addAttributes(['target' => '_blank']);

		$rating = sprintf(' 1%s = %.3f %s', $eurSymbol, $eur->getHome(), $czkSymbol);
		$ratingSpan = Html::el('span')->setText($rating);
		$description = Html::el('span')->add($csobLink)->add($ratingSpan);

		$form->addText('rate', $this->translator->translate('%code% rate', NULL, ['code' => 'EUR']))
			->setOption('description', $description)
			->setAttribute('placeholder', $eur->getHome())
			->getControlPrototype()->class[] = MetronicTextInputBase::SIZE_S;

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
			$this->onAfterSave($this->invoice);
		}
	}

	private function load(Form $form, ArrayHash $values)
	{
		$addressRepo = $this->em->getRepository(Address::getClassName());
		$address = $addressRepo->find($values->address);
		$myAddress = $addressRepo->find(1);
		$this->invoice->shippingAddress = $address;
		$this->invoice->billingAddress = $myAddress;
		$this->invoice->foreignBusiness = $address->foreignBusiness;
		$this->invoice->locale = $values->locale;
		$this->invoice->dueDate = new DateTime($values->dueDate);
		if (isset($values->storno)) {
			$this->invoice->storno = $values->storno;
		}
		if (isset($values->vatPayer)) {
			$this->invoice->vatPayer = $values->vatPayer;
		}
		if ($values->paymentDate) {
			$this->invoice->paymentDate = new DateTime($values->paymentDate);
		}
		switch ($values->currency) {
			case 'EUR':
				$rate = $values->rate ? Price::strToFloat($values->rate) : $this->exchange['EUR']->getHome();
				break;
			default:
			case 'CZK':
				$values->currency = 'CZK';
				$rate = 1;
				break;
		}
		$this->invoice->setCurrency(Strings::upper($values->currency), $rate);
		foreach ($this->invoice->items as $item) {
			$item->changeRate($rate);
		}
		return $this;
	}

	private function save()
	{
		$invoiceRepo = $this->em->getRepository(Invoice::getClassName());
		$invoiceRepo->save($this->invoice);
		return $this;
	}

	/** @return array */
	protected function getDefaults()
	{
		$values = [
			'address' => $this->invoice->shippingAddress ? $this->invoice->shippingAddress->id : NULL,
			'dueDate' => $this->invoice->dueDate ? $this->invoice->dueDate : new DateTime(),
			'paymentDate' => $this->invoice->paymentDate,
			'locale' => $this->invoice->locale,
			'currency' => $this->invoice->currency ? Strings::upper($this->invoice->currency) : NULL,
			'rate' => $this->invoice->rate,
			'storno' => $this->invoice->storno,
			'vatPayer' => $this->invoice->vatPayer,
		];
		return $values;
	}

	private function checkEntityExistsBeforeRender()
	{
		if (!$this->invoice) {
			throw new BaseControlException('Use setInvoice(\App\Model\Entity\Invoice) before render');
		}
	}

	// <editor-fold desc="setters & getters">

	public function setInvoice(Invoice $invoice)
	{
		$this->invoice = $invoice;
		return $this;
	}

	// </editor-fold>
}

interface IInvoiceChangeFactory
{

	/** @return InvoiceChange */
	function create();
}
