<?php

namespace App\AppModule\Presenters;

use App\Components\Confession\Form\IConfessionChangeFactory;
use App\Components\Cost\Form\CostChange;
use App\DateInterval;
use App\Model\Entity\Confession;
use App\Model\Facade\ConfessionFacade;
use App\Model\Facade\CostFacade;
use App\Model\Facade\InvoiceFacade;
use App\Model\Repository\ConfessionRepository;
use Nette\Utils\DateTime;

class VatPresenter extends BasePresenter
{

	const YEAR = NULL;

	/** @var Confession */
	private $confessionEntity;

	/** @var ConfessionRepository */
	private $confessionRepo;

	// <editor-fold desc="injects">

	/** @var InvoiceFacade @inject */
	public $invoiceFacade;

	/** @var CostFacade @inject */
	public $costFacade;

	/** @var ConfessionFacade @inject */
	public $confessionFacade;

	/** @var IConfessionChangeFactory @inject */
	public $iConfessionChangeFactory;

	// </editor-fold>

	protected function startup()
	{
		parent::startup();
		$this->confessionRepo = $this->em->getRepository(Confession::getClassName());
	}

	/**
	 * @secured
	 * @resource('vat')
	 * @privilege('default')
	 */
	public function actionDefault($id = NULL)
	{
		$allowedYears = array_reverse(range(2014, date('Y')));
		$initDate = new DateTime('01-03-2014');
		$nowDate = new DateTime();

		if (in_array($id, $allowedYears)) {
			// start
			if ($id >= $nowDate->format('Y')) {
				$start = clone $nowDate;
			} else {
				$start = new DateTime('31-12-' . $id);
			}
			// stop
			if ($id <= $initDate->format('Y')) {
				$stop = clone $initDate;
			} else {
				$stop = new DateTime('01-01-' . $id);
			}
		} else {
			$start = clone $nowDate;
			$stop = clone $initDate;
		}

		$moths = [];
		while ($start->format('Ym') >= $stop->format('Ym')) {
			$moths[] = new DateTime($start->format('d.m.Y'));
			$start->sub(new DateInterval('P1M'));
		}

		$this->template->months = $moths;
		$this->template->years = $allowedYears;
		$this->template->activeYear = $id;
		$this->template->today = $nowDate;
		$this->template->invoiceFacade = $this->invoiceFacade;
		$this->template->costFacade = $this->costFacade;
		$this->template->confessionFacade = $this->confessionFacade;
	}

	/**
	 * @secured
	 * @resource('vat')
	 * @privilege('xml')
	 */
	public function actionConfessionXml($month, $year)
	{
		$this->getConfessionEntity($month, $year);
		$this->template->invoices = $this->invoiceFacade->getForMonth($this->confessionEntity->accountDate);
		$this->template->confession = $this->confessionEntity;
	}

	/**
	 * @secured
	 * @resource('vat')
	 * @privilege('xml')
	 */
	public function actionSummaryReportXml($month, $year)
	{
		$this->getConfessionEntity($month, $year);
		$this->template->invoices = $this->invoiceFacade->getForMonth($this->confessionEntity->accountDate);
		$this->template->confession = $this->confessionEntity;
	}

	/**
	 * @secured
	 * @resource('vat')
	 * @privilege('xml')
	 */
	public function actionCheckReportXml($month, $year)
	{
		$this->getConfessionEntity($month, $year);
		$this->template->invoices = $this->invoiceFacade->getForMonth($this->confessionEntity->accountDate);
		$this->template->confession = $this->confessionEntity;
	}

	/**
	 * @secured
	 * @resource('vat')
	 * @privilege('edit')
	 */
	public function actionEdit($month, $year)
	{
		$this->getConfessionEntity($month, $year);
	}

	public function renderEdit()
	{
		$this->template->invoices = $this->invoiceFacade->getForMonth($this->confessionEntity->accountDate);
		$this->template->hasSummaryReport = $this->invoiceFacade->checkIfMonthHasSummaryReport($this->confessionEntity->accountDate);
		$this->template->hasCheckReport = $this->invoiceFacade->checkIfMonthHasCheckReport($this->confessionEntity->accountDate);
		$this->template->confession = $this->confessionEntity;
	}

	private function getConfessionEntity($month, $year)
	{
		$this->confessionEntity = $this->confessionRepo->findOneBy([
			'month' => $month,
			'year' => $year,
		]);
		if (!$this->confessionEntity) {
			$this->confessionEntity = new Confession($month, $year);
		}
	}

	// <editor-fold desc="forms">

	/** @return CostChange */
	public function createComponentConfessionEdit()
	{
		$control = $this->iConfessionChangeFactory->create();
		$control->setConfession($this->confessionEntity);
		$control->onAfterSave = function (Confession $saved) {
			$message = $this->translator->translate('successfullySavedHe', NULL, [
				'type' => $this->translator->translate('Confession'), 'name' => (string)$saved
			]);
			$this->flashMessage($message, 'success');
			$this->redirect('edit', ['month' => $saved->month, 'year' => $saved->year]);
		};
		return $control;
	}

	// </editor-fold>
}
