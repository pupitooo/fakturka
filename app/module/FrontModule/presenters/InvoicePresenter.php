<?php

namespace App\FrontModule\Presenters;

use App\Components\Invoice\Pdf\IInvoicePdfFactory;
use App\Components\Invoice\Pdf\InvoicePdf;
use App\Model\Entity\Invoice;
use App\Model\Repository\InvoiceRepository;

class InvoicePresenter extends BasePresenter
{

	/** @var Invoice */
	private $invoiceEntity;

	/** @var InvoiceRepository */
	private $invoiceRepo;

	// <editor-fold desc="injects">

	/** @var IInvoicePdfFactory @inject */
	public $iInvoicePdfFactory;

	// </editor-fold>

	protected function startup()
	{
		parent::startup();
		$this->invoiceRepo = $this->em->getRepository(Invoice::getClassName());
	}

	public function actionPdf($id)
	{
		$this->invoiceEntity = $this->invoiceRepo->find($id);
		if (!$this->invoiceEntity) {
			$message = $this->translator->translate('wasntFoundShe', NULL, ['name' => $this->translator->translate('Invoice')]);
			$this->flashMessage($message, 'warning');
			$this->redirect('default');
		}

		$mpdf = new \mPDF('utf-8');
		$this['invoicePdf']->exportToPdf($mpdf);
		$this->terminate();
	}

	// <editor-fold desc="forms">

	/** @return InvoicePdf */
	public function createComponentInvoicePdf()
	{
		$control = $this->iInvoicePdfFactory->create();
		$control->setInvoice($this->invoiceEntity);
		return $control;
	}

	// </editor-fold>
}
