<?php

namespace ADT\DoctrineForms;

use ADT\DoctrineForms\Exceptions\InvalidArgumentException;
use Doctrine\ORM\EntityManager;
use Nette;
use Nette\Application\UI;
use Nette\DI\Container;

trait EntityForm
{
	private EntityManager $entityManager;
	private ?EntityFormMapper $entityMapper = null;
	private object $entity;
	
	public function getEntityManager()
	{
		return $this->entityManager;
	}

	public function getEntityMapper(): EntityFormMapper
	{
		if ($this->entityMapper === NULL) {
			if (!$this->getEntityManager()) {
				throw new \Exception('Set entity manager first via setEntityManager() method.');
			}

			$this->entityMapper = new EntityFormMapper($this->getEntityManager());
		}

		return $this->entityMapper;
	}

	public function setEntityManager(EntityManager $entityManager)
	{
		$this->entityManager = $entityManager;
		return $this;
	}

	public function setEntity($entity): self
	{
		if (!is_object($entity)) {
			throw new InvalidArgumentException('Expected object, ' . gettype($entity) . ' given.');
		}
		
		$this->entity = $entity;
		return $this;
	}

	public function getEntity(): object
	{
		return $this->entity;
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
