<?php

namespace ADT\DoctrineForms;

use Closure;

class ToOneContainerFactory
{
	private string $name;
	private ?Closure $containerFactory;
	private ?Closure $entityFactory;
	private ?string $isFilledComponentName;

	/**
	 * ToOneContainerFactory constructor.
	 * @param $name
	 * @param $containerFactory
	 * @param null $entityFactory
	 * @param null $isFilledComponentName
	 */
	public function __construct($name, $containerFactory, $entityFactory = null, $isFilledComponentName = null)
	{
		$this->name = $name;
		$this->containerFactory = $containerFactory;
		$this->entityFactory = $entityFactory;
		$this->isFilledComponentName = $isFilledComponentName;
	}

	/**
	 * @return ToOneContainer
	 */
	public function create()
	{
		return new ToOneContainer($this->name, $this->containerFactory, $this->entityFactory, $this->isFilledComponentName);
	}
}
