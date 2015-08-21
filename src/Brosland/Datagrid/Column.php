<?php

namespace Brosland\Datagrid;

use Nette\Utils\Callback;

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
	 * @var bool
	 */
	private $sortable = FALSE;
	/**
	 * @var callable
	 */
	private $valueCallback = NULL;


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
		return $this->sortable;
	}

	/**
	 * @param bool $sortable
	 * @return self
	 */
	public function setSortable($sortable = TRUE)
	{
		$this->sortable = $sortable;

		return $this;
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
	 * @param callable $callback
	 * @return self
	 */
	public function setValueCallback($callback)
	{
		Callback::check($callback);
		$this->valueCallback = $callback;

		return $this;
	}
}