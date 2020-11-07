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
		if (!$component instanceof Nette\Forms\Container) {
			return FALSE;
		}

		if (!$relation = $this->getRelation($meta, $entity, $component->getName())) {
			return FALSE;
		}

		// we have to fill isFilled component value
		// if the field is not empty and isFilled component was generated
		if (
			$component instanceof Kdyby\DoctrineForms\ToOneContainer
			&&
			$relation->getId()
			&&
			$component->hasGeneratedIsFilledComponent()
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
		if (!$component instanceof Nette\Forms\Container) {
			return FALSE;
		}

		if (!$relation = $this->getRelation($meta, $entity, $component->getName())) {
			return FALSE;
		}

		// we want to delete the entity
		// if the field is not empty and isFilled component value is empty
		if (
			$component instanceof Kdyby\DoctrineForms\ToOneContainer
			&&
			$relation->getId()
			&&
			!$component->getIsFilledComponent()->getValue()
		) {
			$meta->setFieldValue($entity, $component->getName(), null);

			$relation = $this->getRelation($meta, $entity, $component->getName());
		}

		// we don't want to create an empty entity
		// if the field and isFilled component are both empty
		if (
			$component instanceof Kdyby\DoctrineForms\ToOneContainer
			&&
			!$relation->getId()
			&&
			!$component->getIsFilledComponent()->getValue()
		) {
			$meta->setFieldValue($entity, $component->getName(), null);
			return true;
		}

		$this->mapper->save($relation, $component);

		return TRUE;
	}



	/**
	 * @param ClassMetadata $meta
	 * @param object $entity
	 * @param string $field
	 * @return bool|object
	 */
	private function getRelation(ClassMetadata $meta, $entity, $field)
	{
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

			$relation = $relationMeta->newInstance();
			$meta->setFieldValue($entity, $field, $relation);
		}

		return $relation;
	}

}
