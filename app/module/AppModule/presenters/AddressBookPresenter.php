<?php

namespace App\AppModule\Presenters;

use App\Components\Address\Form\AddressChange;
use App\Components\Address\Form\IAddressChangeFactory;
use App\Components\Address\Grid\AddressesGrid;
use App\Components\Address\Grid\IAddressesGridFactory;
use App\Model\Entity\Address;
use Kdyby\Doctrine\EntityRepository;

class AddressBookPresenter extends BasePresenter
{

	/** @var Address */
	private $address;

	/** @var EntityRepository */
	private $addressRepo;

	// <editor-fold desc="injects">

	/** @var IAddressChangeFactory @inject */
	public $iAddressChangeFactory;

	/** @var IAddressesGridFactory @inject */
	public $iAddressesGridFactory;

	// </editor-fold>

	protected function startup()
	{
		parent::startup();
		$this->addressRepo = $this->em->getRepository(Address::getClassName());
	}

	// <editor-fold desc="actions & renderers">

	/**
	 * @secured
	 * @resource('addressBook')
	 * @privilege('default')
	 */
	public function actionDefault()
	{

	}

	/**
	 * @secured
	 * @resource('addressBook')
	 * @privilege('add')
	 */
	public function actionAdd()
	{
		$this->address = new Address();
		$this->setView('edit');
	}

	/**
	 * @secured
	 * @resource('addressBook')
	 * @privilege('edit')
	 */
	public function actionEdit($id)
	{
		$this->address = $this->addressRepo->find($id);
		if (!$this->address) {
			$message = $this->translator->translate('wasntFound', NULL, ['name' => $this->translator->translate('Address')]);
			$this->flashMessage($message, 'warning');
			$this->redirect('default');
		}
	}

	public function renderEdit()
	{
		$this->template->address = $this->address;
	}

	/**
	 * @secured
	 * @resource('addressBook')
	 * @privilege('delete')
	 */
	public function actionDelete($id)
	{
		$address = $this->addressRepo->find($id);
		if (!$address) {
			$message = $this->translator->translate('wasntFound', NULL, ['name' => $this->translator->translate('Address')]);
			$this->flashMessage($message, 'danger');
		} else {
			$this->addressRepo->delete($address);
			$message = $this->translator->translate('successfullyDeleted', NULL, ['name' => $this->translator->translate('Address')]);
			$this->flashMessage($message, 'success');
		}
		$this->redirect('default');
	}

	// </editor-fold>
	// <editor-fold desc="forms">

	/** @return AddressChange */
	public function createComponentAddressForm()
	{
		$control = $this->iAddressChangeFactory->create();
		$control->setAddress($this->address);
		$control->onAfterSave = function (Address $saved) {
			$message = $this->translator->translate('successfullySaved', NULL, [
				'type' => $this->translator->translate('Address'), 'name' => (string)$saved
			]);
			$this->flashMessage($message, 'success');
			$this->redirect('edit', $saved->id);
		};
		return $control;
	}

	// </editor-fold>
	// <editor-fold desc="grids">

	/** @return AddressesGrid */
	public function createComponentAddressesGrid()
	{
		$control = $this->iAddressesGridFactory->create();
		return $control;
	}

	// </editor-fold>

}
