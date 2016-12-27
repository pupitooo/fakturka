<?php

namespace App\Components\Cost\Grid;

use App\Components\BaseControl;
use App\Extensions\Grido\BaseGrid;
use App\Extensions\Grido\DataSources\Doctrine;
use App\Model\Entity\Cost;
use App\Model\Entity\Invoice;
use App\Model\Facade\InvoiceFacade;
use Grido\Grid;
use Nette\Utils\DateTime;
use Nette\Utils\Html;

class CostsGrid extends BaseControl
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

		$repo = $this->em->getRepository(Cost::getClassName());
		$qb = $repo->createQueryBuilder('c');
		if ($this->year && $this->month) {
			$fromDate = new DateTime('01-' . $this->month . '-' . $this->year);
			$toDate = clone $fromDate;
			$toDate->modify('last day of this month');
			$qb->whereCriteria([
				'c.dueDate >=' => $fromDate,
				'c.dueDate <=' => $toDate,
			]);
		}
		$grid->setModel(new Doctrine($qb, []), TRUE);

		$grid->setDefaultSort([
			'dueDate' => 'DESC',
		]);

		$grid->addColumnDate('dueDate', 'Due date', 'd.m.Y')
			->setSortable()
			->setFilterText()
			->setSuggestion();
		$grid->getColumn('dueDate')->headerPrototype->width = '200px';
		$grid->getColumn('dueDate')->cellPrototype->style = 'text-align: center';

		$grid->addColumnText('name', 'Name')
			->setSortable()
			->setFilterText()
			->setSuggestion();

		$grid->addColumnText('priceWithoutVat', 'Price without Vat')
			->setCustomRender(function (Cost $item) {
				return $this->exchange->format($item->priceWithoutVat);
			})
			->setSortable()
			->setFilterText()
			->setSuggestion();
		$grid->getColumn('priceWithoutVat')->headerPrototype->width = '10%';
		$grid->getColumn('priceWithoutVat')->cellPrototype->style = 'text-align: right';

		$grid->addColumnText('vatSum', 'Vat')
			->setCustomRender(function (Cost $item) {
				return $this->exchange->format($item->vatSum);
			})
			->setSortable()
			->setFilterText()
			->setSuggestion();
		$grid->getColumn('vatSum')->headerPrototype->width = '10%';
		$grid->getColumn('vatSum')->cellPrototype->style = 'text-align: right';

		$grid->addColumnText('priceWithVat', 'Price with Vat')
			->setCustomRender(function (Cost $item) {
				return $this->exchange->format($item->priceWithVat);
			})
			->setSortable()
			->setFilterText()
			->setSuggestion();
		$grid->getColumn('priceWithVat')->headerPrototype->width = '10%';
		$grid->getColumn('priceWithVat')->cellPrototype->style = 'text-align: right';

		$grid->addColumnDate('paymentDate', 'Payment date', 'd.m.Y')
			->setCustomRender(function (Cost $item) {
				if ($item->paymentDate) {
					$paymentDate = $item->paymentDate->format('d.m.Y');
					$paymentYear = $item->paymentDate->format('Y');
					$dueYear = $item->dueDate->format('Y');
					$dateHtml = Html::el('span')
						->addAttributes([
							'class' => $dueYear === $paymentYear ? 'font-green' : 'font-red'
						])
						->setText($paymentDate);
					return $dateHtml;
				} else {
					return NULL;
				}
			})
			->setSortable()
			->setFilterText()
			->setSuggestion();
		$grid->getColumn('paymentDate')->headerPrototype->width = '200px';
		$grid->getColumn('paymentDate')->cellPrototype->style = 'text-align: center';

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

interface ICostsGridFactory
{

	/** @return CostsGrid */
	function create();
}
