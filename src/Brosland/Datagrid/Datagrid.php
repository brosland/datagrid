<?php

namespace Brosland\Datagrid;

use Brosland\Datagrid\Column,
	Nette\Application\UI\Presenter,
	Nette\Utils\Callback,
	Nette\Utils\Paginator;

class Datagrid extends \Nette\Application\UI\Control
{
	const SORT_ASC = 'ASC', SORT_DESC = 'DESC';


	/**
	 * @var int
	 */
	public $perPage = 20;
	/**
	 * @persistent
	 * @var int
	 */
	public $page = 0;
	/**
	 * @persistent
	 * @var array
	 */
	public $sortBy = array ();
	/**
	 * @var Column[]
	 */
	private $columns = array ();
	/**
	 * @var callable
	 */
	private $datasourceCallback = NULL;
	/**
	 * @var callable
	 */
	private $rowIdentifierCallback = NULL;
	/**
	 * @var Paginator
	 */
	private $paginator;


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
	public function addColumn($name, $label = NULL)
	{
		if (isset($this->columns[$name]))
		{
			throw new \Nette\InvalidArgumentException('Column with name "' . $name . '" already exists.');
		}

		return $this->columns[$name] = new Column($name, $label);
	}

	/**
	 * @param callable $callback
	 * @return self
	 */
	public function setDatasourceCallback($callback)
	{
		Callback::check($callback);
		$this->datasourceCallback = $callback;

		return $this;
	}

	/**
	 * @param string|callable $callback
	 * @return self
	 */
	public function setRowIdentifierCallback($callback)
	{
		Callback::check($callback);
		$this->rowIdentifierCallback = $callback;

		return $this;
	}

	/**
	 * @return array
	 */
	private function getData()
	{
		if (!$this->datasourceCallback)
		{
			throw new \Nette\InvalidStateException('Datasource callback is undefined.');
		}

		return Callback::invokeArgs($this->datasourceCallback, array (
				$this->sortBy, $this->paginator
		));
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
		$directions = array (self::SORT_ASC, self::SORT_DESC);

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

	protected function beforeRender()
	{
		$this->paginator->setItemsPerPage($this->perPage);

		$this->template->columns = $this->columns;
		$this->template->data = $this->getData();
		$this->template->paginator = $this->paginator;
	}

	public function render()
	{
		$this->beforeRender();

		if ($this->template->getFile() == NULL)
		{
			$this->template->setFile(__DIR__ . '/templates/datagrid.latte');
		}
		else
		{
			$this->template->datagridTemplate = __DIR__ . '/templates/datagrid.latte';
		}

		$this->template->render();
	}

	/**
	 * @param array $sortBy
	 */
	public function handleSort(array $sortBy = array ())
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