<?php

namespace App\Components\Address\Grid;

use App\Components\BaseControl;
use App\Extensions\Grido\BaseGrid;
use App\Extensions\Grido\DataSources\Doctrine;
use App\Forms\Controls\SelectBased\Select2;
use App\Model\Entity\Address;
use App\Model\Entity\Invoice;
use App\Model\Entity\Order;
use App\Model\Entity\OrderState;
use App\Model\Facade\Exception\FacadeException;
use App\Model\Facade\InvoiceFacade;
use App\Model\Facade\OrderFacade;
use Grido\Grid;
use Nette\Utils\Html;

class AddressesGrid extends BaseControl
{

	/** @return Grid */
	protected function createComponentGrid()
	{
		$grid = new BaseGrid();
		$grid->setTranslator($this->translator);
		$grid->setTheme(BaseGrid::THEME_METRONIC);

		$repo = $this->em->getRepository(Address::getClassName());
		$qb = $repo->createQueryBuilder('a');
		$grid->setModel(new Doctrine($qb, []), TRUE);

		$grid->setDefaultSort([
			'dicVerified' => 'DESC',
			'foreignBusiness' => 'DESC',
			'name' => 'ASC',
		]);

		$grid->addColumnText('name', 'Name')
			->setCustomRender(function ($item) {
				$link = $this->presenter->link('edit', ['id' => $item->id]);
				return Html::el('a')->href($link)->setText($item);
			})
			->setSortable()
			->setFilterText()
			->setSuggestion();

		$grid->addColumnText('country', 'Country')
			->setSortable()
			->setFilterSelect(
				[NULL => $this->translator->translate('--- anyone ---')] +
				Address::getCountries());

		$grid->addColumnText('ico', 'cart.form.ico')
			->setSortable()
			->setFilterText()
			->setSuggestion();

		$grid->addColumnText('dic', 'cart.form.dic')
			->setCustomRender(function (Address $item) {
				$valid = Html::el('span class="fa fa-check font-green"');
				$invalid = Html::el('span class="fa fa-times font-red"');
				return ($item->dicVerified ? $valid : $invalid) . ' ' . $item->dic;
			})
			->setSortable()
			->setFilterText()
			->setSuggestion();

		$grid->addColumnBoolean('foreignBusiness', 'Foreign')
			->setSortable()
			->setFilterSelect([
				NULL => $this->translator->translate('--- anyone ---'),
				TRUE => $this->translator->translate('Yes'),
				FALSE => $this->translator->translate('No'),
			]);

		$grid->addActionHref('edit', 'Edit')
			->setIcon('fa fa-edit');

		$col = $grid->addActionHref('delete', 'Delete')
			->setIcon('fa fa-trash-o')
			->setConfirm(function ($item) {
				$message = $this->translator->translate('Are you sure you want to delete \'%name%\'?', NULL, ['name' => (string)$item]);
				return $message;
			});
		$col->getElementPrototype()->class[] = 'red';

		$grid->setActionWidth("10%");

		return $grid;
	}

}

interface IAddressesGridFactory
{

	/** @return AddressesGrid */
	function create();
}
