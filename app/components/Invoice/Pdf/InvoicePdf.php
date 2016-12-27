<?php

namespace App\Components\Invoice\Pdf;

use App\Components\BaseControl;
use App\Model\Entity\Invoice;
use Nette\Application\UI\Control;
use Nette\Application\UI\ITemplate;
use Nette\Utils\Strings;

class InvoicePdf extends BaseControl
{

	// <editor-fold desc="variables">

	/** @var Invoice */
	private $invoice;

	// </editor-fold>

	public function render()
	{
		$template = Control::getTemplate();
		$this->generate($template);
		$template->render();
	}

	private function generate(ITemplate $template)
	{
		$this->translator->setLocale($this->invoice->locale);
		$template->setTranslator($this->translator->domain('invoice'));
		$dir = dirname($this->getReflection()->getFileName());
		$template->setFile($dir . DIRECTORY_SEPARATOR . $this->templateFile . '.latte');

		$template->bankAccount = $this->settings->getCompanyInfo()->bank[Strings::lower($this->invoice->currency)];
		$template->invoice = $this->invoice;
	}

	public function exportToPdf(\mPDF $mpdf, $name = NULL, $dest = NULL)
	{
		$template = Control::getTemplate();
		$this->generate($template);
		$mpdf->WriteHTML((string)$template);

		$result = NULL;
		if (($name !== '') && ($dest !== NULL)) {
			$result = $mpdf->Output($name, $dest);
		} elseif ($dest !== NULL) {
			$result = $mpdf->Output('', $dest);
		} else {
			$result = $mpdf->Output($name, $dest);
		}
		return $result;
	}


	// <editor-fold desc="setters & getters">

	public function setInvoice(Invoice $invoice)
	{
		$this->invoice = $invoice;
		$this->invoice->setExchangeWeb($this->exchange);
		return $this;
	}

	// </editor-fold>

}

interface IInvoicePdfFactory
{

	/** @return InvoicePdf */
	function create();
}
