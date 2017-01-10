<?php

namespace App\AppModule\Presenters;

use App\Components\Cost\Form\CostChange;
use App\Components\Cost\Form\ICostChangeFactory;
use App\Components\Cost\Grid\CostsGrid;
use App\Components\Cost\Grid\ICostsGridFactory;
use App\Components\Payment\Form\IPaymentChangeFactory;
use App\Components\Payment\Form\PaymentChange;
use App\Components\Payment\Grid\IPaymentsGridFactory;
use App\Components\Payment\Grid\PaymentsGrid;
use App\Model\Entity\Cost;
use App\Model\Entity\Payment;
use App\Model\Repository\CostRepository;
use App\Model\Repository\PaymentRepository;
use Exception;

class PaymentsPresenter extends BasePresenter
{

	const YEAR = NULL;

	/** @var Payment */
	private $paymentEntity;

	/** @var PaymentRepository */
	private $paymentsRepo;

	// <editor-fold desc="injects">

	/** @var IPaymentChangeFactory @inject */
	public $iPaymentChangeFactory;

	/** @var IPaymentsGridFactory @inject */
	public $iPaymentsGridFactory;

	// </editor-fold>

	protected function startup()
	{
		parent::startup();
		$this->paymentsRepo = $this->em->getRepository(Payment::getClassName());
	}

	/**
	 * @secured
	 * @resource('payments')
	 * @privilege('default')
	 */
	public function actionDefault($month = NULL, $year = NULL)
	{
		if ($month && $year) {
			$this['paymentsGrid']->setInterval($month, $year);
		}
	}

	/**
	 * @secured
	 * @resource('payments')
	 * @privilege('edit')
	 */
	public function actionAdd()
	{
		$this->paymentEntity = new Payment();
		$this->setView('edit');
	}

	/**
	 * @secured
	 * @resource('payments')
	 * @privilege('edit')
	 */
	public function actionEdit($id)
	{
		$this->paymentEntity = $this->paymentsRepo->find($id);
		if (!$this->paymentEntity) {
			$message = $this->translator->translate('wasntFoundShe', NULL, ['name' => $this->translator->translate('Payment')]);
			$this->flashMessage($message, 'warning');
			$this->redirect('default');
		}
	}

	public function renderEdit()
	{
		$this->template->payment = $this->paymentEntity;
	}

	/**
	 * @secured
	 * @resource('payments')
	 * @privilege('delete')
	 */
	public function actionDelete($id)
	{
		$this->paymentEntity = $this->paymentsRepo->find($id);
		if (!$this->paymentEntity) {
			$message = $this->translator->translate('wasntFoundShe', NULL, ['name' => $this->translator->translate('Payment')]);
			$this->flashMessage($message, 'danger');
		} else {
			try {
				$this->paymentsRepo->delete($this->paymentEntity);
				$message = $this->translator->translate('successfullyDeletedShe', NULL, ['name' => $this->translator->translate('Payment')]);
				$this->flashMessage($message, 'success');
			} catch (Exception $e) {
				$message = $this->translator->translate('cannotDeleteShe', NULL, ['name' => $this->translator->translate('Payment')]);
				$this->flashMessage($message, 'danger');
			}
		}
		$this->redirect('default');
	}

	// <editor-fold desc="forms">

	/** @return PaymentChange */
	public function createComponentPaymentEdit()
	{
		$control = $this->iPaymentChangeFactory->create();
		$control->setPayment($this->paymentEntity);
		$control->onAfterSave = function (Payment $saved, $add) {
			$message = $this->translator->translate('successfullySavedShe', NULL, [
				'type' => $this->translator->translate('Payment'), 'name' => (string)$saved
			]);
			$this->flashMessage($message, 'success');
			if ($add) {
				$this->redirect('add');
			} else {
				$this->redirect('default');
			}
		};
		return $control;
	}

	// </editor-fold>
	// <editor-fold desc="grids">

	/** @return PaymentsGrid */
	public function createComponentPaymentsGrid()
	{
		$control = $this->iPaymentsGridFactory->create();
		return $control;
	}

	// </editor-fold>
}
