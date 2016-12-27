<?php

namespace App\AppModule\Presenters;

use App\Components\Currency\Form\IRateFactory;
use App\Components\Currency\Form\Rate;

class SettingsPresenter extends BasePresenter
{

	// <editor-fold desc="injects">

	/** @var IRateFactory @inject */
	public $iRateFormFactory;

	// </editor-fold>

	/**
	 * @secured
	 * @resource('settings')
	 * @privilege('default')
	 */
	public function actionDefault()
	{
		
	}

	// <editor-fold desc="forms">

	/** @return Rate */
	public function createComponentRateForm()
	{
		$control = $this->iRateFormFactory->create();
		$control->onAfterSave = function () {
			$message = $this->translator->translate('Rates was successfully saved.');
			$this->flashMessage($message, 'success');
			$this->redirect('default');
		};
		return $control;
	}

	// </editor-fold>
}
