<?php

namespace Brosland\Datagrid;

use Closure,
	Nette\Utils\Callback;

class Column extends \Nette\Object
{
	/**
	 * @var string
	 */
	private $name;
	/**
	 * @var string
	 */
	private $label;
	/**
	 * @var Closure
	 */
	private $sortCallback = NULL, $valueCallback = NULL;


	/**
	 * @param string $name
	 * @param string $label
	 */
	public function __construct($name, $label = NULL)
	{
		$this->name = $name;
		$this->label = $label;
	}

	/**
	 * @return string
	 */
	public function getName()
	{
		return $this->name;
	}

	/**
	 * @return string
	 */
	public function getLabel()
	{
		return $this->label;
	}

	/**
	 * @return bool
	 */
	public function isSortable()
	{
		return $this->sortCallback != NULL;
	}

	/**
	 * @param Closure $callback
	 * @return self
	 */
	public function setSortable(Closure $callback)
	{
		$this->sortCallback = $callback;

		return $this;
	}

	/**
	 * @param mixed $datasource
	 * @param string $direction
	 */
	public function applySorting($datasource, $direction)
	{
		if ($this->isSortable())
		{
			Callback::invokeArgs($this->sortCallback, [$datasource, $direction]);
		}
	}

	/**
	 * @param mixin $row
	 * @return mixin
	 */
	public function getValue($row)
	{
		if ($this->valueCallback === NULL)
		{
			return is_array($row) ? $row[$this->name] : $row->{$this->name};
		}

		return Callback::invokeArgs($this->valueCallback, [$row]);
	}

	/**
	 * @param Closure $callback
	 * @return self
	 */
	public function setValueCallback(Closure $callback)
	{
		$this->valueCallback = $callback;

		return $this;
	}
}