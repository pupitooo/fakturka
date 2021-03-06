<?php

namespace App\AppModule\Presenters;

use App\BaseModule\Presenters\BasePresenter as BaseBasePresenter;

abstract class BasePresenter extends BaseBasePresenter
{
	
	protected function startup()
	{
		parent::startup();
		$this->exchange->setWeb($this->exchange->getDefault()->getCode());
	}

	public function handleToggleSidebar($value)
	{
		if ($this->user->isLoggedIn()) {
			$this->em->flush($this->user->identity);
		}

		$this->redrawControl();
	}

	public function handleSignOut()
	{
		$this->user->logout();
		$this->presenter->flashMessage($this->translator->translate('flash.signOutSuccess'), 'success');
		$this->presenter->redirect(':Front:Homepage:');
	}

}
