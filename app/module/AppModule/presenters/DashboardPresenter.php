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
		$sum = [];
		$clear = [];
		foreach ($years as $year) {
			$invoices[$year] = $this->invoiceFacade->getForYearSum(new DateTime('1.1.' . $year));
			$sum[$year] = $this->paymentFacade->getForYearSum(new DateTime('1.1.' . $year));
			$clear[$year] = $this->paymentFacade->getForYearSum(new DateTime('1.1.' . $year), TRUE);
			$cargo[$year] = $this->paymentFacade->getForYearCargo(new DateTime('1.1.' . $year));
		}

		$this->template->thisYear = $thisYear;
		$this->template->thisMonth = date('n');
		$this->template->years = $years;
		$this->template->minus = $minusMonths;
		$this->template->invoices = $invoices;
		$this->template->sum = $sum;
		$this->template->clear = $clear;
		$this->template->cargo = $cargo;

		$invoiceYearSum = $this->invoiceFacade->getForYearSum(new DateTime(), FALSE);
		$this->template->invoiceYearSum = $invoiceYearSum;
		$this->template->yearLimit = InvoiceFacade::YEAR_LIMIT;
		$this->template->yearLimitRest = InvoiceFacade::YEAR_LIMIT - $invoiceYearSum;
	}

}
