<?php

namespace App\Components\Confession\Form;

use App\Components\BaseControl;
use App\Components\BaseControlException;
use App\Forms\Controls\Custom\DatePicker;
use App\Forms\Controls\TextInputBased\MetronicTextInputBase;
use App\Forms\Form;
use App\Forms\Renderers\MetronicFormRenderer;
use App\Model\Entity\Confession;
use App\Model\Entity\Cost;
use App\Model\Entity\Price;
use App\Model\Entity\Vat;
use App\Model\Facade\ConfessionFacade;
use Nette\Utils\ArrayHash;

class ConfessionChange extends BaseControl
{

	/** @var Confession */
	private $confession;

	/** @var ConfessionFacade @inject */
	public $confessionFacade;

	// <editor-fold desc="events">

	/** @var array */
	public $onAfterSave = [];

	// </editor-fold>

	/** @return Form */
	protected function createComponentForm()
	{
		$this->checkEntityExistsBeforeRender();

		$form = new Form();
		$form->setTranslator($this->translator);
		$form->setRenderer(new MetronicFormRenderer());

		$form->addDatePicker('sendDate', 'Send date')
			->setRequired()
			->setSize(DatePicker::SIZE_S);

		$form->addText('username', 'Username');
		$form->addText('password', 'Password');

		$form->addSubmit('save', 'Save');;

		$form->setDefaults($this->getDefaults());
		$form->onSuccess[] = $this->formSucceeded;
		return $form;
	}

	public function formSucceeded(Form $form, $values)
	{
		$this->load($form, $values);
		if (!$form->hasErrors()) {
			$this->save();
			$this->onAfterSave($this->confession);
		}
	}

	private function load(Form $form, ArrayHash $values)
	{
		$this->confession->sendDate = $values->sendDate;
		$this->confession->username = $values->username;
		$this->confession->password = $values->password;
		return $this;
	}

	private function save()
	{
		$costRepo = $this->em->getRepository(Cost::getClassName());
		$costRepo->save($this->confession);
		return $this;
	}

	/** @return array */
	protected function getDefaults()
	{
		$values = [
			'sendDate' => $this->confession->sendDate,
			'username' => $this->confession->username,
			'password' => $this->confession->password,
		];
		return $values;
	}

	private function checkEntityExistsBeforeRender()
	{
		if (!$this->confession) {
			throw new BaseControlException('Use setConfession(\App\Model\Entity\Confession) before render');
		}
	}

	// <editor-fold desc="setters & getters">

	public function setConfession(Confession $confession)
	{
		$this->confession = $confession;
		return $this;
	}

	// </editor-fold>
}

interface IConfessionChangeFactory
{

	/** @return ConfessionChange */
	function create();
}
