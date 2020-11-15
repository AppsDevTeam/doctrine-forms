<?php

namespace ADT\DoctrineForms;

class ToOneContainerFactory
{
	protected $name;
	protected $containerFactory;
	protected $entityFactory;
	protected $isFilledComponentName;
	protected $errorMessage;

	/**
	 * ToOneContainerFactory constructor.
	 * @param $name
	 * @param $containerFactory
	 * @param null $entityFactory
	 * @param null $isFilledComponentName
	 * @param null $errorMessage
	 */
	public function __construct($name, $containerFactory, $entityFactory = null, $isFilledComponentName = null, $errorMessage = null)
	{
		$this->name = $name;
		$this->containerFactory = $containerFactory;
		$this->entityFactory = $entityFactory;
		$this->isFilledComponentName = $isFilledComponentName;
		$this->errorMessage = $errorMessage;
	}

	/**
	 * @return ToOneContainer
	 * @throws \Doctrine\Persistence\Mapping\MappingException
	 * @throws \ReflectionException
	 */
	public function create()
	{
		return new ToOneContainer($this->name, $this->containerFactory, $this->entityFactory, $this->isFilledComponentName, $this->errorMessage);
	}
}
