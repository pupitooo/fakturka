<?php

namespace App\AppModule\Presenters;

use App\Components\Cost\Form\CostChange;
use App\Components\Cost\Form\ICostChangeFactory;
use App\Components\Cost\Grid\CostsGrid;
use App\Components\Cost\Grid\ICostsGridFactory;
use App\Model\Entity\Cost;
use App\Model\Repository\CostRepository;
use Exception;

class CostsPresenter extends BasePresenter
{

	const YEAR = NULL;

	/** @var Cost */
	private $costEntity;

	/** @var CostRepository */
	private $costRepo;

	// <editor-fold desc="injects">

	/** @var ICostChangeFactory @inject */
	public $iCostChangeFactory;

	/** @var ICostsGridFactory @inject */
	public $iCostsGridFactory;

	// </editor-fold>

	protected function startup()
	{
		parent::startup();
		$this->costRepo = $this->em->getRepository(Cost::getClassName());
	}

	/**
	 * @secured
	 * @resource('costs')
	 * @privilege('default')
	 */
	public function actionDefault($month = NULL, $year = NULL)
	{
		if ($month && $year) {
			$this['costsGrid']->setInterval($month, $year);
		}
	}

	/**
	 * @secured
	 * @resource('costs')
	 * @privilege('edit')
	 */
	public function actionAdd()
	{
		$this->costEntity = new Cost();
		$this->setView('edit');
	}

	/**
	 * @secured
	 * @resource('costs')
	 * @privilege('edit')
	 */
	public function actionEdit($id)
	{
		$this->costEntity = $this->costRepo->find($id);
		if (!$this->costEntity) {
			$message = $this->translator->translate('wasntFoundHe', NULL, ['name' => $this->translator->translate('Cost')]);
			$this->flashMessage($message, 'warning');
			$this->redirect('default');
		}
	}

	public function renderEdit()
	{
		$this->template->cost = $this->costEntity;
	}

	/**
	 * @secured
	 * @resource('costs')
	 * @privilege('delete')
	 */
	public function actionDelete($id)
	{
		$this->costEntity = $this->costRepo->find($id);
		if (!$this->costEntity) {
			$message = $this->translator->translate('wasntFoundHe', NULL, ['name' => $this->translator->translate('Cost')]);
			$this->flashMessage($message, 'danger');
		} else {
			try {
				$this->costRepo->delete($this->costEntity);
				$message = $this->translator->translate('successfullyDeletedHe', NULL, ['name' => $this->translator->translate('Cost')]);
				$this->flashMessage($message, 'success');
			} catch (Exception $e) {
				$message = $this->translator->translate('cannotDeleteHe', NULL, ['name' => $this->translator->translate('Cost')]);
				$this->flashMessage($message, 'danger');
			}
		}
		$this->redirect('default');
	}

	// <editor-fold desc="forms">

	/** @return CostChange */
	public function createComponentCostEdit()
	{
		$control = $this->iCostChangeFactory->create();
		$control->setCost($this->costEntity);
		$control->onAfterSave = function (Cost $saved, $add) {
			$message = $this->translator->translate('successfullySavedHe', NULL, [
				'type' => $this->translator->translate('Cost'), 'name' => (string)$saved
			]);
			$this->flashMessage($message, 'success');
			if ($add) {
				$this->redirect('add');
			} else {
				$this->redirect('edit', $saved->id);
			}
		};
		return $control;
	}

	// </editor-fold>
	// <editor-fold desc="grids">

	/** @return CostsGrid */
	public function createComponentCostsGrid()
	{
		$control = $this->iCostsGridFactory->create();
		return $control;
	}

	// </editor-fold>
}
