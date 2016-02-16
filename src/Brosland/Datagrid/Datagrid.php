<?php

namespace Brosland\Datagrid;

use Brosland\Datagrid\Column,
	Nette\Application\UI\Presenter,
	Nette\Utils\Callback,
	Nette\Utils\Paginator;

class Datagrid extends \Nette\Application\UI\Control
{

	const SORT_ASC = 'ASC', SORT_DESC = 'DESC', VIEW_DEFAULT = 'default';


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
	public $sortBy = [];
	/**
	 * @var Column[]
	 */
	private $columns = [];
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
	/**
	 * @var string
	 */
	private $view = self::VIEW_DEFAULT;


	public function __construct()
	{
		parent::__construct();

		$this->paginator = new Paginator();
	}

	/**
	 * @param string $view
	 * @return self
	 */
	public function setView($view)
	{
		$this->view = $view;

		return $this;
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
	 * @return string
	 */
	protected function formatTemplatePath()
	{
		$reflection = $this->getReflection();
		$className = $reflection->getShortName();

		return dirname($reflection->getFileName()) . '/templates/'
			. $className . '/' . $this->view . '.latte';
	}

	/**
	 * @return \Nette\Application\UI\ITemplate
	 */
	protected function createTemplate()
	{
		$template = parent::createTemplate();
		$template->datagridTemplate = $defaultTemplatePath = __DIR__ . '/templates/datagrid.latte';

		$templatePath = $this->formatTemplatePath();

		if (file_exists($templatePath))
		{
			$template->setFile($templatePath);
		}
		else
		{
			$template->setFile($defaultTemplatePath);
		}

		return $template;
	}

	protected function beforeRender()
	{
		$this->paginator->setItemsPerPage($this->perPage);

		if (!$this->datasourceCallback)
		{
			throw new \Nette\InvalidStateException('Datasource callback is not defined.');
		}

		$data = Callback::invokeArgs($this->datasourceCallback, [$this->sortBy, $this->paginator]);

		$this->template->columns = $this->columns;
		$this->template->data = $data;
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