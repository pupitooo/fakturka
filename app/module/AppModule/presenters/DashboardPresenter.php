<?php

namespace App\AppModule\Presenters;

use App\Model\Facade\InvoiceFacade;
use Nette\Utils\DateTime;

class DashboardPresenter extends BasePresenter
{

	/** @var InvoiceFacade @inject */
	public $invoiceFacade;

	/**
	 * @secured
	 * @resource('dashboard')
	 * @privilege('default')
	 */
	public function actionDefault()
	{
		$invoiceYearSum = $this->invoiceFacade->getForYearSum(new DateTime(), FALSE);
		$this->template->invoiceYearSum = $invoiceYearSum;
		$this->template->yearLimit = InvoiceFacade::YEAR_LIMIT;
		$this->template->yearLimitRest = InvoiceFacade::YEAR_LIMIT - $invoiceYearSum;
	}

}
