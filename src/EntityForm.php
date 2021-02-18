<?php

namespace ADT\DoctrineForms;

use ADT\DoctrineForms\Exceptions\InvalidArgumentException;
use Nette;
use Nette\Application\UI;
use Nette\DI\Container;

trait EntityForm
{
	/** @var EntityFormMapper */
	private $entityMapper;

	/** @var object */
	private $entity;

	/** @var Container */
	protected Container $dic;

	/**
	 * @return \ADT\DoctrineForms\EntityFormMapper
	 */
	public function getEntityMapper()
	{
		if ($this->entityMapper === NULL) {
			$this->entityMapper = $this->dic->getByType('ADT\DoctrineForms\EntityFormMapper');
		}

		return $this->entityMapper;
	}

	/**
	 * @return object
	 */
	public function setEntity($entity)
	{
		if (!is_object($entity)) {
			throw new InvalidArgumentException('Expected object, ' . gettype($entity) . ' given.');
		}
		
		$this->entity = $entity;
		return $this;
	}

	/**
	 * @return object
	 */
	public function getEntity()
	{
		return $this->entity;
	}

	public function setDic(Container $dic)
	{
		$this->dic = $dic;
		return $this;
	}

	public function mapToForm()
	{
		if (!$this->entity) {
			throw new \Exception('An entity is not set.');
		}

		$this->getEntityMapper()->load($this->entity, $this);
	}

	public function mapToEntity()
	{
		$this->getEntityMapper()->save($this->entity, $this);
	}
}
