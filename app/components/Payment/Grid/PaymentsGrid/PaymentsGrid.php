<?php

namespace App\Components\Payment\Grid;

use App\Components\BaseControl;
use App\Extensions\Grido\BaseGrid;
use App\Extensions\Grido\DataSources\Doctrine;
use App\Model\Entity\Cost;
use App\Model\Entity\Invoice;
use App\Model\Entity\Payment;
use App\Model\Facade\InvoiceFacade;
use Grido\Grid;
use Nette\Utils\DateTime;
use Nette\Utils\Html;

class PaymentsGrid extends BaseControl
{

	/** @var integer */
	private $month;

	/** @var integer */
	private $year;

	/** @return Grid */
	protected function createComponentGrid()
	{
		$grid = new BaseGrid();
		$grid->setTranslator($this->translator);
		$grid->setTheme(BaseGrid::THEME_METRONIC);

		$repo = $this->em->getRepository(Payment::getClassName());
		$qb = $repo->createQueryBuilder('p');
		if ($this->year && $this->month) {
			$fromDate = new DateTime('01-' . $this->month . '-' . $this->year);
			$toDate = clone $fromDate;
			$toDate->modify('last day of this month');
			$qb->whereCriteria([
				'p.date >=' => $fromDate,
				'p.date <=' => $toDate,
			]);
		}
		$grid->setModel(new Doctrine($qb, []), TRUE);

		$grid->setDefaultSort([
			'date' => 'DESC',
		]);

		$grid->addColumnDate('date', 'Date', 'd.m.Y')
			->setSortable()
			->setFilterText()
			->setSuggestion();
		$grid->getColumn('date')->headerPrototype->width = '130px';
		$grid->getColumn('date')->cellPrototype->style = 'text-align: center';

		$grid->addColumnText('name', 'Name')
			->setSortable()
			->setFilterText()
			->setSuggestion();

		$grid->addColumnNumber('price', 'Price')
			->setCustomRender(function (Payment $item) {
				return $this->exchange->format($item->price);
			})
			->setSortable()
			->setFilterNumber();

		$grid->addColumnNumber('priceEur', 'Price in €')
			->setCustomRender(function (Payment $item) {
				return $this->exchange->format($item->priceEur, 'EUR', 'EUR');
			})
			->setSortable()
			->setFilterNumber();

		$grid->addActionHref('edit', 'Edit')
			->setIcon('fa fa-edit');

		$col = $grid->addActionHref('delete', 'Delete')
			->setIcon('fa fa-trash-o')
			->setConfirm(function ($item) {
				$message = $this->translator->translate('Are you sure you want to delete \'%name%\'?', NULL, ['name' => (string)$item]);
				return $message;
			});
		$col->getElementPrototype()->class[] = 'red';

		$grid->setActionWidth('200px');

		return $grid;
	}

	public function setInterval($month = NULL, $year = NULL)
	{
		$this->month = $month;
		$this->year = $year;
		return $this;
	}

}

interface IPaymentsGridFactory
{

	/** @return PaymentsGrid */
	function create();
}
