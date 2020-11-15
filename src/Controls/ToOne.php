<?php

namespace ADT\DoctrineForms\Controls;

use ADT\DoctrineForms\ToOneContainer;
use Doctrine\Common\Collections\Collection;
use ADT\DoctrineForms\EntityFormMapper;
use ADT\DoctrineForms\IComponentMapper;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\ORMException;
use Nette\ComponentModel\Component;

class ToOne implements IComponentMapper
{
	/**
	 * @var EntityFormMapper
	 */
	private EntityFormMapper $mapper;

	public function __construct(EntityFormMapper $mapper)
	{
		$this->mapper = $mapper;
	}

	/**
	 * {@inheritdoc}
	 */
	public function load(ClassMetadata $meta, Component $component, $entity): bool
	{
		if (!$component instanceof ToOneContainer) {
			return FALSE;
		}

		if (!$relation = $this->getRelation($meta, $component, $entity)) {
			return FALSE;
		}

		// we have to fill isFilled component value
		// if the field is not empty and isFilled component is set
		if (
			$relation->getId()
			&&
			$component->getIsFilledComponent()
		) {
			$component->getIsFilledComponent()->setDefaultValue(true);
		}

		$this->mapper->load($relation, $component);

		return TRUE;
	}

	/**
	 * @param ClassMetadata $meta
	 * @param Component $component
	 * @param $entity
	 * @return bool
	 * @throws ORMException
	 */
	public function save(ClassMetadata $meta, Component $component, $entity): bool
	{
		if (!$component instanceof ToOneContainer) {
			return FALSE;
		}

		if (!$relation = $this->getRelation($meta, $component, $entity)) {
			return FALSE;
		}

		// we want to delete the entity
		// if the field is not empty and isFilled component value is empty or the entire container is empty
		if (
			$relation->getId()
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
		elseif (
			$relation->getId()
			&&
			$component->getIsFilledComponent()
			&&
			!$component->isEmpty($excludeIsFilledComponent = true)
		) {
			$this->removeComponent($meta, $component, $entity);
			$relation = $this->getRelation($meta, $component, $entity);
		}
		// we don't want to create an entity
		// if the entire container is empty
		elseif (
			!$relation->getId()
			&&
			$component->isEmpty()
		) {
			$meta->setFieldValue($entity, $component->getName(), null);
			return true;
		}

		$this->mapper->save($relation, $component);

		return TRUE;
	}

	/**
	 * @param ClassMetadata $meta
	 * @param $component
	 * @param $entity
	 * @throws ORMException
	 */
	private function removeComponent(ClassMetadata $meta, $component, $entity)
	{
		$relation = $this->getRelation($meta, $component, $entity);

		// we don't want to rely on orphanRemoval
		$this->mapper->getEntityManager()->remove($relation);

		// this must not be before entity removal
		// otherwise the relation is refreshed
		$meta->setFieldValue($entity, $component->getName(), null);
	}

	/**
	 * @param ClassMetadata $meta
	 * @param ToOneContainer $component
	 * @param $entity
	 * @return bool|mixed|object
	 */
	private function getRelation(ClassMetadata $meta, ToOneContainer $component, $entity)
	{
		$field = $component->getName();

		if (!$meta->hasAssociation($field) || !$meta->isSingleValuedAssociation($field)) {
			return FALSE;
		}

		// todo: allow access using property or method
		$relation = $meta->getFieldValue($entity, $field);
		if ($relation instanceof Collection) {
			return FALSE;
		}

		if ($relation === NULL) {
			$class = $meta->getAssociationTargetClass($field);
			$relationMeta = $this->mapper->getEntityManager()->getClassMetadata($class);

			$relation = $component->createEntity($relationMeta);
			$meta->setFieldValue($entity, $field, $relation);
		}

		return $relation;
	}
}
