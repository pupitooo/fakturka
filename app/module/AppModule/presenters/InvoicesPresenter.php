<?php

namespace App\AppModule\Presenters;

use App\Components\Invoice\Form\IInvoiceChangeFactory;
use App\Components\Invoice\Form\IInvoiceItemsFactory;
use App\Components\Invoice\Form\InvoiceItems;
use App\Components\Invoice\Grid\IInvoicesGridFactory;
use App\Model\Entity\Invoice;
use App\Model\Facade\InvoiceFacade;
use App\Model\Repository\InvoiceRepository;
use Drahak\Restful\Utils\Strings;
use Exception;

class InvoicesPresenter extends BasePresenter
{

	const YEAR = NULL;

	/** @var Invoice */
	private $invoiceEntity;

	/** @var InvoiceRepository */
	private $invoiceRepo;

	// <editor-fold desc="injects">

	/** @var InvoiceFacade @inject */
	public $invoiceFacade;

	/** @var IInvoiceItemsFactory @inject */
	public $iInvoiceItemFactory;

	/** @var IInvoiceChangeFactory @inject */
	public $iInvoiceChangeFactory;

	/** @var IInvoicesGridFactory @inject */
	public $iInvoicesGridFactory;

	// </editor-fold>

	protected function startup()
	{
		parent::startup();
		$this->invoiceRepo = $this->em->getRepository(Invoice::getClassName());
	}

	/**
	 * @secured
	 * @resource('invoices')
	 * @privilege('default')
	 */
	public function actionDefault($month = NULL, $year = NULL)
	{
		if ($month && $year) {
			$this['invoicesGrid']->setInterval($month, $year);
		}
	}

	/**
	 * @secured
	 * @resource('invoices')
	 * @privilege('edit')
	 */
	public function actionAdd()
	{
		$newId = $this->invoiceFacade->getNewId(self::YEAR);
		$this->invoiceEntity = new Invoice($newId, $this->translator->getDefaultLocale());
		$this->setView('edit');
	}

	/**
	 * @secured
	 * @resource('invoices')
	 * @privilege('edit')
	 */
	public function actionEdit($id)
	{
		$this->invoiceEntity = $this->invoiceRepo->find($id);
		if (!$this->invoiceEntity) {
			$message = $this->translator->translate('wasntFoundShe', NULL, ['name' => $this->translator->translate('Invoice')]);
			$this->flashMessage($message, 'warning');
			$this->redirect('default');
		}
		$this->invoiceEntity->setExchangeWeb($this->exchange);
	}

	public function renderEdit()
	{
		$this->template->invoice = $this->invoiceEntity;
	}

	/**
	 * @secured
	 * @resource('invoices')
	 * @privilege('clone')
	 */
	public function actionClone($id)
	{
		$cloned = $this->invoiceRepo->find($id);
		if (!$cloned) {
			$message = $this->translator->translate('wasntFoundShe', NULL, ['name' => $this->translator->translate('Invoice')]);
			$this->flashMessage($message, 'warning');
			$this->redirect('default');
		}
		$newId = $this->invoiceFacade->getNewId(self::YEAR);
		$this->invoiceEntity = new Invoice($newId, $this->translator->getLocale());
		$this->invoiceEntity->import($cloned);
		$this->invoiceRepo->save($this->invoiceEntity);
		$this->redirect('edit', ['id' => $this->invoiceEntity->id]);
	}

	/**
	 * @secured
	 * @resource('invoices')
	 * @privilege('show')
	 */
	public function actionShow($id)
	{
		$this->invoiceEntity = $this->invoiceRepo->find($id);
		if (!$this->invoiceEntity) {
			$message = $this->translator->translate('wasntFoundShe', NULL, ['name' => $this->translator->translate('Invoice')]);
			$this->flashMessage($message, 'warning');
			$this->redirect('default');
		}
		$this->invoiceEntity->setExchangeWeb($this->exchange);
	}

	public function renderShow()
	{
		$this->template->invoice = $this->invoiceEntity;
		$this->template->bankAccount = $this->settings->getCompanyInfo()->bank[Strings::lower($this->invoiceEntity->currency)];
	}

	/**
	 * @secured
	 * @resource('invoices')
	 * @privilege('delete')
	 */
	public function actionDelete($id)
	{
		$this->invoiceEntity = $this->invoiceRepo->find($id);
		if (!$this->invoiceEntity) {
			$message = $this->translator->translate('wasntFoundShe', NULL, ['name' => $this->translator->translate('Invoice')]);
			$this->flashMessage($message, 'danger');
		} else {
			try {
				$this->invoiceRepo->delete($this->invoiceEntity);
				$message = $this->translator->translate('successfullyDeletedShe', NULL, ['name' => $this->translator->translate('Invoice')]);
				$this->flashMessage($message, 'success');
			} catch (Exception $e) {
				$message = $this->translator->translate('cannotDeleteShe', NULL, ['name' => $this->translator->translate('Invoice')]);
				$this->flashMessage($message, 'danger');
			}
		}
		$this->redirect('default');
	}

	// <editor-fold desc="forms">

	/** @return InvoiceChange */
	public function createComponentInvoiceEdit()
	{
		$control = $this->iInvoiceChangeFactory->create();
		$control->setInvoice($this->invoiceEntity);
		$control->onAfterSave = function (Invoice $saved) {
			$message = $this->translator->translate('successfullySavedShe', NULL, [
				'type' => $this->translator->translate('Invoice'), 'name' => (string)$saved
			]);
			$this->flashMessage($message, 'success');
			$this->redirect('edit', $saved->id);
		};
		return $control;
	}

	/** @return InvoiceItems */
	public function createComponentInvoiceItemsEdit()
	{
		$control = $this->iInvoiceItemFactory->create();
		$control->setInvoice($this->invoiceEntity);
		$control->onAfterSave = function (Invoice $saved) {
			$message = $this->translator->translate('successfullySavedShe', NULL, [
				'type' => $this->translator->translate('Invoice'), 'name' => (string)$saved
			]);
			$this->flashMessage($message, 'success');
			$this->redirect('edit', $saved->id);
		};
		return $control;
	}

	// </editor-fold>
	// <editor-fold desc="grids">

	/** @return InvoicesGrid */
	public function createComponentInvoicesGrid()
	{
		$control = $this->iInvoicesGridFactory->create();
		return $control;
	}

	// </editor-fold>
}
