<?php

namespace App\AppModule\Presenters;

use App\Components\Currency\Form\IRateFactory;
use App\Components\Currency\Form\Rate;
use App\Components\User\Form\IUserBasicFactory;
use App\Components\User\Form\UserBasic;

class SettingsPresenter extends BasePresenter
{

	// <editor-fold desc="injects">

	/** @var IRateFactory @inject */
	public $iRateFormFactory;

	/** @var IUserBasicFactory @inject */
	public $iUserBasicFactory;

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
			$this->redirect('this');
		};
		return $control;
	}

	/** @return UserBasic */
	public function createComponentChangePassword()
	{
		$control = $this->iUserBasicFactory->create();
		$control->setUser($this->user->identity, TRUE);
		$control->onAfterSave = function () {
			$message = $this->translator->translate('User was successfully saved.');
			$this->flashMessage($message, 'success');
			$this->redirect('this');
		};
		return $control;
	}

	// </editor-fold>
}
