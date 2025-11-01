<?php

namespace ADT\DoctrineForms\Controls;

use ADT\DoctrineComponents\Entities\Entity;
use ADT\Forms\StaticContainer;
use Doctrine\Common\Collections\Collection;
use ADT\DoctrineForms\EntityFormMapper;
use ADT\DoctrineForms\IComponentMapper;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\MappingException;
use Nette\ComponentModel\Component;
use ReflectionException;

class ToOne implements IComponentMapper
{
	use CreateEntityTrait;

	/**
	 * @var EntityFormMapper
	 */
	private EntityFormMapper $mapper;

	public function __construct(EntityFormMapper $mapper)
	{
		$this->mapper = $mapper;
	}

	/**
	 * @throws ReflectionException
	 * @throws MappingException
	 */
	public function load(ClassMetadata $meta, Component $component, $entity): bool
	{
		if (!$component instanceof StaticContainer) {
			return false;
		}

		if ($callback = $this->mapper->getForm()->getComponentFormMapper($component)) {
			$callback($this->mapper, $component, $entity);
			return true;
		}

		if (!$relation = $this->getRelation($meta, $component, $entity)) {
			return false;
		}

		// we have to fill isFilled component value
		// if the field is not empty and isFilled component is set
		if (
			!$relation->isNew()
			&&
			$component->getIsFilledComponent()
		) {
			$component->getIsFilledComponent()->setDefaultValue(true);
		}

		$this->mapper->load($relation, $component);

		return true;
	}

	/**
	 * @throws ReflectionException
	 * @throws MappingException
	 */
	public function save(ClassMetadata $meta, Component $component, Entity $entity): bool
	{
		if (!$component instanceof StaticContainer) {
			return false;
		}

		if ($callback = $this->mapper->getForm()->getComponentEntityMapper($component)) {
			$callback($this->mapper, $component, $entity);
			return true;
		}

		if (!$relation = $this->getRelation($meta, $component, $entity)) {
			return false;
		}

		// we want to delete the entity
		// if the field is not empty and isFilled component value is empty or the entire container is empty
		if (
			!$relation->isNew()
			&&
			(
				$component->getIsFilledComponent() && !$component->getIsFilledComponent()->getValue()
				||
				$component->isEmpty()
			)
		) {
			$this->removeComponent($meta, $component, $entity);
			return true;
		}
		// we want to delete the entity
		// if isFilled component is set and any other container control is filled
		// we use this when someone updated the old values with the new ones
		elseif (
			!$relation->isNew()
			&&
			$component->getIsFilledComponent()
			&&
			!$component->isEmpty(excludeIsFilledComponent: true)
		) {
			$this->removeComponent($meta, $component, $entity);
			$relation = $this->getRelation($meta, $component, $entity);
		}
		// we don't want to create an entity
		// if the entire container is empty
		elseif (
			$relation->isNew()
			&&
			$component->isEmpty()
		) {
			$meta->setFieldValue($entity, $component->getName(), null);
			return true;
		}

		$this->mapper->save($relation, $component);

		return true;
	}

	/**
	 * @throws ReflectionException
	 * @throws MappingException
	 */
	private function removeComponent(ClassMetadata $meta, $component, Entity $entity): void
	{
		$relation = $this->getRelation($meta, $component, $entity);

		// we don't want to rely on orphanRemoval
		$this->mapper->getEntityManager()->remove($relation);

		// this must not be before entity removal
		// otherwise the relation is refreshed
		$meta->setFieldValue($entity, $component->getName(), null);
	}

	/**
	 * @throws ReflectionException
	 * @throws MappingException
	 */
	private function getRelation(ClassMetadata $meta, StaticContainer $component, Entity $entity): ?Entity
	{
		$field = $component->getName();

		if (!$meta->hasAssociation($field) || !$meta->isSingleValuedAssociation($field)) {
			return null;
		}

		// todo: allow access using property or method
		$relation = $meta->getFieldValue($entity, $field);
		if ($relation instanceof Collection) {
			return null;
		}

		if ($relation === NULL) {
			$relation = $this->createEntity($meta, $component, $entity);
			$meta->setFieldValue($entity, $field, $relation);
		}

		return $relation;
	}
}
