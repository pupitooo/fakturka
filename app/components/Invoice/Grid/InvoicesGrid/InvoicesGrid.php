<?php

namespace App\Components\Invoice\Grid;

use App\Components\BaseControl;
use App\Extensions\Grido\BaseGrid;
use App\Extensions\Grido\DataSources\Doctrine;
use App\Model\Entity\Invoice;
use App\Model\Facade\InvoiceFacade;
use Grido\Components\Columns\Date;
use Grido\Grid;
use Nette\Utils\DateTime;
use Nette\Utils\Html;

class InvoicesGrid extends BaseControl
{

	/** @var integer */
	private $month;

	/** @var integer */
	private $year;

	/** @var InvoiceFacade @inject */
	public $invoiceFacade;

	/** @return Grid */
	protected function createComponentGrid()
	{
		$grid = new BaseGrid();
		$grid->setTranslator($this->translator);
		$grid->setTheme(BaseGrid::THEME_METRONIC);

		$repo = $this->em->getRepository(Invoice::getClassName());
		$qb = $repo->createQueryBuilder('i')
			->leftJoin('i.shippingAddress', 'a');
		if ($this->year && $this->month) {
			$fromDate = new DateTime('01-' . $this->month . '-' . $this->year);
			$toDate = clone $fromDate;
			$toDate->modify('last day of this month');
			$qb->whereCriteria([
				'i.dueDate >=' => $fromDate,
				'i.dueDate <=' => $toDate,
			]);
		}
		$grid->setModel(new Doctrine($qb, [
			'shippingAddress' => 'a',
			'shippingAddress.name' => 'a.name',
		]), TRUE);

		$grid->setDefaultSort([
			'id' => 'DESC',
		]);

		$grid->addColumnText('id', 'Number')
			->setCustomRender(function (Invoice $item) {
				$link = $this->presenter->link('show', ['id' => $item->id]);
				$nameHtml = Html::el($item->storno ? 'strike' : 'span')
					->setText($item->id);
				return Html::el('a')->href($link)->setHtml($nameHtml);
			})
			->setSortable()
			->setFilterText()
			->setSuggestion();
		$grid->getColumn('id')->headerPrototype->width = '7%';

		$grid->addColumnText('address', 'Address')
			->setColumn('shippingAddress.name')
			->setCustomRender(function (Invoice $item) {
				$nameHtml = Html::el($item->storno ? 'strike' : 'span')
					->setText((string)$item->shippingAddress);
				return $nameHtml;
			})
			->setSortable()
			->setFilterText()
			->setSuggestion();

		$grid->addColumnText('totalPrice', 'Total price')
			->setCustomRender(function (Invoice $item) {
				$toCurrency = $item->currency;
				$totalPrice = $item->getTotalPrice($this->exchange);
				return $this->exchange->formatTo($totalPrice, $toCurrency);
			});
		$grid->getColumn('totalPrice')->headerPrototype->width = '10%';
		$grid->getColumn('totalPrice')->cellPrototype->style = 'text-align: right';

		$grid->addColumnText('totalPriceCzk', 'Total price (CZK)')
			->setCustomRender(function (Invoice $item) {
				$toCurrency = $this->exchange->getDefault();
				$totalPrice = $item->getTotalPrice($this->exchange);
				return $this->exchange->formatTo($totalPrice, $toCurrency);
			});
		$grid->getColumn('totalPriceCzk')->headerPrototype->width = '10%';
		$grid->getColumn('totalPriceCzk')->cellPrototype->style = 'text-align: right';

		$grid->addColumnDate('dueDate', 'Due date', 'd.m.Y')
			->setSortable()
			->setFilterText()
			->setSuggestion();
		$grid->getColumn('dueDate')->headerPrototype->width = '120px';
		$grid->getColumn('dueDate')->cellPrototype->style = 'text-align: center';

		$grid->addColumnDate('paymentDate', 'Payment date', 'd.m.Y')
			->setCustomRender(function (Invoice $item) {
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
		$grid->getColumn('paymentDate')->headerPrototype->width = '120px';
		$grid->getColumn('paymentDate')->cellPrototype->style = 'text-align: center';

		$grid->addColumnText('locale', 'Language')
			->setSortable()
			->setFilterText()
			->setSuggestion();
		$grid->getColumn('locale')->headerPrototype->width = '4%';
		$grid->getColumn('locale')->cellPrototype->style = 'text-align: center';

		$grid->addColumnText('currency', 'Currency')
			->setCustomRender(function ($item) {
				return $item->currency . ($item->rate <> 1 ? ($item->rate ? ' (' . $item->rate . ')' : '') : NULL);
			})
			->setSortable()
			->setFilterSelect([
				NULL => '--- anyone ---',
				'CZK' => 'CZK',
				'EUR' => 'EUR',
			]);
		$grid->getColumn('currency')->headerPrototype->width = '7%';
		$grid->getColumn('locale')->cellPrototype->style = 'text-align: center';

		$grid->addActionHref('edit', 'Edit')
			->setIcon('fa fa-edit');

		$grid->addActionHref('clone', 'Clone')
			->setIcon('fa fa-copy');

		$col = $grid->addActionHref('delete', 'Delete')
			->setIcon('fa fa-trash-o')
			->setConfirm(function ($item) {
				$message = $this->translator->translate('Are you sure you want to delete \'%name%\'?', NULL, ['name' => (string)$item]);
				return $message;
			});
		$col->getElementPrototype()->class[] = 'red';

		$grid->setActionWidth('240px');

		return $grid;
	}

	public function setInterval($month = NULL, $year = NULL)
	{
		$this->month = $month;
		$this->year = $year;
		return $this;
	}

}

interface IInvoicesGridFactory
{

	/** @return InvoicesGrid */
	function create();
}
