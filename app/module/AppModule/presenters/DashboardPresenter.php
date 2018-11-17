<?php

namespace App\AppModule\Presenters;

use App\Model\Facade\InvoiceFacade;
use App\Model\Facade\PaymentFacade;
use Nette\Utils\DateTime;

class DashboardPresenter extends BasePresenter
{

	/** @var InvoiceFacade @inject */
	public $invoiceFacade;

	/** @var PaymentFacade @inject */
	public $paymentFacade;

	/**
	 * @secured
	 * @resource('dashboard')
	 * @privilege('default')
	 */
	public function actionDefault()
	{
		$thisYear = date('Y');
		$years = range($thisYear, 2013);
		$minusMonths = [
			2013 => 2,
			2016 => 2,
		];
		$invoices = [];
		$recievedInvoices = [];
		$sum = [];
		$clear = [];
		$extraCharge = [];
		foreach ($years as $year) {
			$invoices[$year] = $this->invoiceFacade->getForYearSum(new DateTime('1.1.' . $year));
			$recievedInvoices[$year] = $this->invoiceFacade->getForYearSumForConfession(new DateTime('1.1.' . $year));
			$sum[$year] = $this->paymentFacade->getForYearSum(new DateTime('1.1.' . $year));
			$clear[$year] = $this->paymentFacade->getForYearSum(new DateTime('1.1.' . $year), TRUE);
			$cargo[$year] = $this->paymentFacade->getForYearCargo(new DateTime('1.1.' . $year));
			$extraCharge[$year] = $this->paymentFacade->getExtraCharge(new DateTime('1.1.' . $year));
		}

		$this->template->thisYear = $thisYear;
		$this->template->thisMonth = date('n');
		$this->template->years = $years;
		$this->template->minus = $minusMonths;
		$this->template->invoices = $invoices;
		$this->template->recievedInvoices = $recievedInvoices;
		$this->template->sum = $sum;
		$this->template->clear = $clear;
		$this->template->cargo = $cargo;
		$this->template->extraCharge = $extraCharge;

		$invoiceYearSum = $this->invoiceFacade->getForYearSum(new DateTime(), FALSE);
		$yearLimit = $this->invoiceFacade->getYearLimit();
		$yearLimitRest = $yearLimit - $invoiceYearSum;
		$this->template->invoiceYearSum = $invoiceYearSum;
		$this->template->yearLimit = $yearLimit;
		$this->template->yearLimitRest = \abs($yearLimitRest);
		$this->template->yearLimitReached = $yearLimitRest < 0;
	}

}
