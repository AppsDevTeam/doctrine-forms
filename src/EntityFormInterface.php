<?php

namespace ADT\DoctrineForms;

use Doctrine\ORM\EntityManager;
use Nette\ComponentModel\IComponent;

interface EntityFormInterface
{
	public function getEntityManager(): EntityManager;
	public function getEntityMapper(): EntityFormMapper;
	public function setEntityManager(EntityManager $entityManager): self;
	public function setEntity(object $entity): self;
	public function getEntity(): object;
	public function mapToForm(): void;
	public function mapToEntity(): void;
	public function getComponentFormMapper(IComponent $component): ?\Closure;
	public function setComponentFormMapper(IComponent $component, \Closure $formMapper): self;
	public function getComponentEntityMapper(IComponent $component): ?\Closure;
	public function setComponentEntityMapper(IComponent $component, \Closure $entityMapper): self;
	public function getComponentEntityFactory(IComponent $component): ?\Closure;
	public function setComponentEntityFactory(IComponent $component, \Closure $entityFactory): self;
}
