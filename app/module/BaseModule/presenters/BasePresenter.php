<?php

namespace App\BaseModule\Presenters;

use App\ExchangeHelper;
use App\Extensions\Settings\SettingsStorage;
use App\Model\Entity\Rate;
use App\Model\Facade\InvoiceFacade;
use App\Model\Facade\UserFacade;
use h4kuna\Exchange\Exchange;
use h4kuna\Exchange\ExchangeException;
use Kdyby\Doctrine\EntityManager;
use Kdyby\Translation\Translator;
use Nette\Application\ForbiddenRequestException;
use Nette\Application\UI\Presenter;
use WebLoader\Nette\CssLoader;
use WebLoader\Nette\LoaderFactory;

/**
 * Base presenter for all application presenters.
 */
abstract class BasePresenter extends Presenter
{

	/** @persistent */
	public $locale;

	/** @persistent */
	public $backlink = '';

	// <editor-fold desc="injects">

	/** @var LoaderFactory @inject */
	public $webLoader;

	/** @var Exchange @inject */
	public $exchange;

	/** @var Translator @inject */
	public $translator;

	/** @var SettingsStorage @inject */
	public $settings;

	/** @var EntityManager @inject */
	public $em;

	/** @var UserFacade @inject */
	public $userFacade;

	/** @var InvoiceFacade @inject */
	public $invoiceFacade;

	/** @var int */
	protected $priceLevel = NULL;

	// </editor-fold>

	protected function startup()
	{
		parent::startup();
		$this->setLocale();
		$this->loadCurrencyRates();
	}

	protected function beforeRender()
	{
		$this->template->setTranslator($this->translator);
		$this->template->lang = $this->translator->getLocale(); // TODO: remove lang from latte
		$this->template->locale = $this->translator->getLocale();
		$this->template->defaultLocale = $this->translator->getDefaultLocale();
		$this->template->allowedLanguages = $this->translator->getAvailableLocales();

		$this->template->pageInfo = $this->settings->getPageInfo();
		$this->template->companyInfo = $this->settings->getCompanyInfo();
		$this->template->exchange = $this->exchange;

		$currency = $this->exchange[$this->exchange->getWeb()];
		$this->template->currency = $currency;
		$this->template->currencySymbol = $currency->getFormat()->getSymbol();
	}

	// <editor-fold desc="requirments">

	public function checkRequirements($element)
	{
		$secured = $element->getAnnotation('secured');
		$resource = $element->getAnnotation('resource');
		$privilege = $element->getAnnotation('privilege');

		if ($secured) {
			if (!$this->user->loggedIn) {
				$this->flashMessage($this->translator->translate('flash.signedInRequired'), 'warning');
				$this->redirect(':Front:Sign:in', ['backlink' => $this->storeRequest()]);
			} elseif (!$this->user->isAllowed($resource, $privilege)) {
				throw new ForbiddenRequestException;
			}
		}
	}

	// </editor-fold>
	// <editor-fold desc="currency">

	private function loadCurrencyRates()
	{
		$rateRepo = $this->em->getRepository(Rate::getClassName());
		$rates = $rateRepo->findValuePairs();

		$defaultCode = $this->exchange->getDefault()->getCode();
		foreach ($this->exchange as $code => $currency) {
			$isDefault = strtolower($code) === strtolower($defaultCode);
			$isInDb = array_key_exists($code, $rates);
			if (!$isDefault && $isInDb) {
				$rateRelated = ExchangeHelper::getRelatedRate($rates[$code], $currency);
				$this->exchange->addRate($code, $rateRelated);
			}
		}
	}

	// </editor-fold>

	private function setLocale()
	{
		if ($this->user->isLoggedIn()) {
			if ($this->user->identity->locale !== $this->locale && $this->name !== 'Front:Error') { // Locale has changed
				$overwrite = $this->getParameter('overwrite', 'no');

				if ($overwrite == 'yes' || $this->user->identity->locale === NULL) {
					$this->user->storage->setLocale($this->locale);
				}

				$this->redirect('this', ['locale' => $this->user->identity->locale]);
			}
		} else {
			$this->user->storage->setLocale($this->locale);
		}
	}

	// <editor-fold desc="handlers">

	public function handleSetCurrency($currency)
	{
		try {
			$this->exchange->setWeb($this->exchange[$currency], TRUE);
			$this->user->storage->setCurrency($this->exchange[$currency]);
		} catch (ExchangeException $e) {
			$this->flashMessage($this->translator->translate('Requested currency isn\'t supported.'), 'warning');
		}

		$this->redirect('this');
	}

	// </editor-fold>
	// <editor-fold desc="css webloader">

	/** @return CssLoader */
	protected function createComponentCssFront()
	{
		$css = $this->webLoader->createCssLoader('front')
				->setMedia('screen,projection,tv');
		return $css;
	}

	/** @return CssLoader */
	protected function createComponentCssApp()
	{
		$css = $this->webLoader->createCssLoader('app')
				->setMedia('screen,projection,tv');
		return $css;
	}

	/** @return CssLoader */
	protected function createComponentCssPrint()
	{
		$css = $this->webLoader->createCssLoader('print')
				->setMedia('print');
		return $css;
	}

	// </editor-fold>
}
