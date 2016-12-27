<?php

namespace App\Components\Invoice\Form;

use App\Components\BaseControl;
use App\ExchangeHelper;
use App\Forms\Controls\TextInputBased\MetronicTextInputBase;
use App\Forms\Form;
use App\Forms\Renderers\MetronicHorizontalFormRenderer;
use App\Model\Entity\Discount;
use App\Model\Entity\Group;
use App\Model\Entity\GroupDiscount;
use App\Model\Entity\Invoice;
use App\Model\Entity\InvoiceItem;
use App\Model\Entity\Price;
use App\Model\Entity\Stock;
use App\Model\Entity\Vat;
use App\Model\Facade\InvoiceFacade;
use App\Model\Facade\VatFacade;
use Nette\Utils\ArrayHash;

class InvoiceItems extends BaseControl
{

	const MAX_COUNT = 9;

	// <editor-fold desc="variables">

	/** @var InvoiceFacade @inject */
	public $invoiceFacade;

	/** @var VatFacade @inject */
	public $vatFacade;

	/** @var Invoice */
	private $invoice;

	/** @var ExchangeHelper @inject */
	public $exchangeHelper;

	/** @var bool */
	private $defaultWithVat = FALSE;

	// </editor-fold>
	// <editor-fold desc="events">

	/** @var array */
	public $onAfterSave = [];

	// </editor-fold>

	/** @return Form */
	protected function createComponentForm()
	{
		$this->checkEntityExistsBeforeRender();

		$form = new Form();
		$form->setTranslator($this->translator)
			->setRenderer(new MetronicHorizontalFormRenderer(1, 11));
		$form->getElementPrototype()->class('form-horizontal');

		$names = $form->addContainer('name');
		$withoutVat = $form->addContainer('withoutVat');
		$withVat = $form->addContainer('withVat');
		$count = $this->invoice->items->count() >= self::MAX_COUNT ? $this->invoice->items->count() + 1 : self::MAX_COUNT;
		for ($i = 0; $i < $count; $i++) {
			$names->addText($i, $this->translator->translate('Item #%count%', $i + 1));
			$withoutVat->addText($i, 'Without Vat')
				->setAttribute('placeholder', $this->translator->translate('Without Vat'))
				->setAttribute('class', ['mask_currency', MetronicTextInputBase::SIZE_S]);
			$withVat->addText($i, 'With Vat')
				->setAttribute('placeholder', $this->translator->translate('With Vat'))
				->setAttribute('class', ['mask_currency', MetronicTextInputBase::SIZE_S]);
		}

		$col = $form->addSelect2('vat', 'Vat', $this->vatFacade->getValues());
		$col->getControlPrototype()->class[] = MetronicTextInputBase::SIZE_XS;

		$form->addSubmit('save', 'Save');

		$form->setDefaults($this->getDefaults());
		$form->onSuccess[] = $this->formSucceeded;
		return $form;
	}

	public function formSucceeded(Form $form, $values)
	{
		$this->load($values);
		if (!$form->hasErrors()) {
			$this->save();
			$this->onAfterSave($this->invoice);
		}
	}

	private function load(ArrayHash $values)
	{
		$vatRepo = $this->em->getRepository(Vat::getClassName());
		$vat = $vatRepo->find($values->vat);

		$names = $values->name;
		$this->invoice->items->clear();
		foreach ($names as $id => $name) {
			$withVat = Price::strToFloat($values->withVat[$id]);
			$withoutVat = Price::strToFloat($values->withoutVat[$id]);
			if ($name && (!!$withVat || !!$withoutVat)) {
				$value = !!$withVat ? $withVat : $withoutVat;
				$this->invoice->setExchangeRate($this->exchange);
				$changed = $this->exchange->change($value, $this->invoice->currency, $this->exchange->getDefault()->getCode(), 2);
				$this->invoice->removeExchangeRate($this->exchange);
				$price = new Price($vat, $changed, !$withVat);

				$item = new InvoiceItem();
				$item->invoice = $this->invoice;
				$item->name = $name;
				$item->quantity = 1;
				$item->price = $price;
				$item->setRawPrice($value, !!$withVat);
				$this->invoice->items->add($item);
			}
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
		$values = [];
		foreach ($this->invoice->items as $i => $item) {
			/** @var InvoiceItem $item */
			$values['name'][$i] = $item->name;
			$price = new Price($item->vat, $item->rawPrice, !$item->rawWithVat);
			$values['withVat'][$i] = $item->rawWithVat ? $item->rawPrice : $price->withVat;
			$values['withoutVat'][$i] = $item->rawWithVat ? $price->withoutVat : $item->rawPrice;
		}
		$vatRepo = $this->em->getRepository(Vat::getClassName());
		$noVat = $vatRepo->find(Vat::NONE);
		$highVat = $vatRepo->find(Vat::HIGH);
		$values['vat'] = $this->invoice->foreignBusiness || !$this->invoice->vatPayer ? $noVat->id : $highVat->id;
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

interface IInvoiceItemsFactory
{

	/** @return InvoiceItems */
	function create();
}
