<?php

namespace App\FrontModule\Presenters;

use App\BaseModule\Presenters\BasePresenter as BaseBasePresenter;
use App\Components\Auth\ISignInFactory;
use App\Helpers;

abstract class BasePresenter extends BaseBasePresenter
{

	const PAGE_INFO_TITLE = 'title';
	const PAGE_INFO_KEYWORDS = 'keywords';
	const PAGE_INFO_DESCRIPTION = 'description';

	/** @var ISignInFactory @inject */
	public $iSignInFactory;

	/** @var string */
	protected $currentBacklink;

	protected function startup()
	{
		parent::startup();
		$this->currentBacklink = $this->storeRequest();
	}

	protected function beforeRender()
	{
		parent::beforeRender();
		$this->template->backlink = $this->currentBacklink;

		$this->template->pageKeywords = $this->settings->pageInfo->keywords;
		$this->template->pageDescription = $this->settings->pageInfo->description;

		$this->loadTemplateApplets();
	}

	public function changePageInfo($type, $content)
	{
		if ($content) {
			switch ($type) {
				case self::PAGE_INFO_TITLE:
					$this->template->pageTitle = $content;
					break;
				case self::PAGE_INFO_DESCRIPTION:
					$this->template->pageDescription = Helpers::concatStrings(' | ', $content, $this->template->pageDescription);
					break;
				case self::PAGE_INFO_KEYWORDS:
					$this->template->pageKeywords = Helpers::concatStrings(', ', $content, $this->template->pageKeywords);
					break;
			}
		}
	}

	public function handleSignOut()
	{
		$this->user->logout();
		$this->flashMessage($this->translator->translate('flash.signOutSuccess'), 'success');
		$this->redirect('this');
	}

	protected function loadTemplateApplets()
	{
		if ($this->settings->modules->googleAnalytics->enabled) {
			$this->template->googleAnalyticsCode = $this->settings->modules->googleAnalytics->code;
		}
		if ($this->settings->modules->googleSiteVerification->enabled) {
			$this->template->googleSiteVerification = $this->settings->modules->googleSiteVerification->code;
		}
		if ($this->settings->modules->smartSupp->enabled) {
			$this->template->smartSuppKey = $this->settings->modules->smartSupp->key;
		}
		if ($this->settings->modules->smartLook->enabled) {
			$this->template->smartLookKey = $this->settings->modules->smartLook->key;
		}
		if ($this->settings->modules->facebookApplet->enabled) {
			$this->template->facebookAppletId = $this->settings->modules->facebookApplet->id;
		}
	}

	// <editor-fold desc="forms">

	// </editor-fold>
}
