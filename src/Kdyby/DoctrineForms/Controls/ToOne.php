<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.txt that was distributed with this source code.
 */

namespace Kdyby\DoctrineForms\Controls;

use Doctrine\Common\Collections\Collection;
use Kdyby;
use Kdyby\DoctrineForms\EntityFormMapper;
use Kdyby\DoctrineForms\IComponentMapper;
use Doctrine\ORM\Mapping\ClassMetadata;
use Nette;
use Nette\ComponentModel\Component;



/**
 * @author Filip Procházka <filip@prochazka.su>
 */
class ToOne implements IComponentMapper
{
	use \Nette\SmartObject;

	/**
	 * @var EntityFormMapper
	 */
	private $mapper;



	public function __construct(EntityFormMapper $mapper)
	{
		$this->mapper = $mapper;
	}



	/**
	 * {@inheritdoc}
	 */
	public function load(ClassMetadata $meta, Component $component, $entity)
	{
		if (!$component instanceof Kdyby\DoctrineForms\ToOneContainer) {
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
	 * {@inheritdoc}
	 */
	public function save(ClassMetadata $meta, Component $component, $entity)
	{
		if (!$component instanceof Kdyby\DoctrineForms\ToOneContainer) {
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
			$relation = $this->removeComponent($meta, $component, $entity);
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
			$relation = $this->removeComponent($meta, $component, $entity);
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



	private function removeComponent(ClassMetadata $meta, $component, $entity)
	{
		$relation = $this->getRelation($meta, $component, $entity);

		// we don't want to rely on orphanRemoval
		$this->mapper->getEntityManager()->remove($relation);

		// this must not be before entity removal
		// otherwise the relation is refreshed
		$meta->setFieldValue($entity, $component->getName(), null);

		return $this->getRelation($meta, $component, $entity);
	}



	/**
	 * @param ClassMetadata $meta
	 * @param object $entity
	 * @param string $field
	 * @return bool|object
	 */
	private function getRelation(ClassMetadata $meta, Kdyby\DoctrineForms\ToOneContainer $component, $entity)
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
		else {
			$component->getEntityFactory()->call($component, $relation);
		}

		return $relation;
	}

}
