<?php

namespace Brosland\Datagrid;

use Brosland\Datagrid\Column,
	Closure,
	Nette\Application\UI\Presenter,
	Nette\Utils\Callback,
	Nette\Utils\Paginator;

class Datagrid extends \Nette\Application\UI\Control
{
	const SORT_ASC = 'ASC', SORT_DESC = 'DESC';


	/**
	 * @persistent
	 * @var array
	 */
	public $sortBy = [];
	/**
	 * @persistent
	 * @var int
	 */
	public $page = 0;
	/**
	 * @var Closure[]
	 */
	public $filter = [];
	/**
	 * @var int
	 */
	public $perPage = 20;
	/**
	 * @var Paginator
	 */
	private $paginator;
	/**
	 * @var Column[]
	 */
	private $columns = [];
	/**
	 * @var Closure
	 */
	private $datasourceCallback = NULL, $rowIdentifierCallback = NULL;


	public function __construct()
	{
		parent::__construct();

		$this->paginator = new Paginator();
	}

	/**
	 * @param string $name
	 * @param string $label
	 * @return Column
	 * @throws \Nette\InvalidArgumentException
	 */
	public function addColumn($name, $label = NULL, $insertBefore = NULL)
	{
		if (isset($this->columns[$name]))
		{
			throw new \Nette\InvalidArgumentException("Column with name $name already exists.");
		}

		$column = new Column($name, $label);

		if ($insertBefore == NULL)
		{
			$this->columns[$name] = $column;
		}
		else
		{
			$index = array_search($insertBefore, array_keys($this->columns));

			if ($index === FALSE)
			{
				throw new \Nette\InvalidArgumentException("Column with name $insertBefore doesn't exist.");
			}

			$this->columns = array_slice($this->columns, 0, $index, TRUE) +
				[$name => $column] + array_slice($this->columns, $index, NULL, TRUE);
		}

		return $column;
	}

	/**
	 * @param Closure $callback
	 * @return self
	 */
	public function setDatasourceCallback(Closure $callback)
	{
		$this->datasourceCallback = $callback;

		return $this;
	}

	/**
	 * @param Closure $callback
	 * @return self
	 */
	public function setRowIdentifierCallback(Closure $callback)
	{
		$this->rowIdentifierCallback = $callback;

		return $this;
	}

	/**
	 * @param mixin $row
	 * @param \Latte\Runtime\CachingIterator $iterator
	 * @return mixin
	 */
	public function getRowIdentifier($row, \Latte\Runtime\CachingIterator $iterator)
	{
		if ($this->rowIdentifierCallback === NULL)
		{
			return $iterator->key();
		}

		return Callback::invokeArgs($this->rowIdentifierCallback, array ($row));
	}

	/**
	 * @param string $column
	 * @return string
	 */
	public function getNextSortType($column)
	{
		return isset($this->sortBy[$column]) && $this->sortBy[$column] == self::SORT_ASC ?
			self::SORT_DESC : self::SORT_ASC;
	}

	/**
	 * @param \Nette\ComponentModel\IComponent $component
	 */
	protected function attached($component)
	{
		parent::attached($component);

		if (!$component instanceof Presenter)
		{
			return;
		}

		$this->configure($component);

		// sorting validation
		$directions = [self::SORT_ASC, self::SORT_DESC];

		foreach ($this->sortBy as $column => $direction)
		{
			if (!isset($this->columns[$column]) ||
				!$this->columns[$column]->isSortable() ||
				!in_array($direction, $directions))
			{
				unset($this->sortBy[$column]);
				continue;
			}
		}

		$this->paginator->setPage($this->page);
	}

	/**
	 * @param Presenter $presenter
	 */
	protected function configure(Presenter $presenter)
	{
		
	}

	/**
	 * @param array $sortBy
	 * @param Paginator $paginator
	 * @return mixed
	 */
	protected function loadData(array $sortBy, Paginator $paginator)
	{
		if (!$this->datasourceCallback)
		{
			throw new \Nette\InvalidStateException('Datasource callback is not defined.');
		}

		$datasource = Callback::invokeArgs($this->datasourceCallback, [$paginator]);

		foreach ($this->filter as $modifier)
		{
			$modifier($datasource);
		}

		foreach ($sortBy as $column => $direction)
		{
			$this->columns[$column]->applySorting($datasource, $direction);
		}

		return $datasource;
	}

	/**
	 * @return \Nette\Application\UI\ITemplate
	 */
	protected function createTemplate()
	{
		$template = parent::createTemplate();
		$template->datagridTemplate = $defaultPath = __DIR__ . '/templates/datagrid.latte';
		$template->setFile($defaultPath);

		return $template;
	}

	protected function beforeRender()
	{
		$this->paginator->setItemsPerPage($this->perPage);

		$this->template->columns = $this->columns;
		$this->template->data = $this->loadData($this->sortBy, $this->paginator);
		$this->template->paginator = $this->paginator;
	}

	public function render()
	{
		$this->beforeRender();

		$this->template->render();
	}

	/**
	 * @param array $sortBy
	 */
	public function handleSort(array $sortBy = [])
	{
		$this->redrawControl();
	}

	/**
	 * @param int $page
	 */
	public function handlePaginate($page)
	{
		$this->redrawControl();
	}
}