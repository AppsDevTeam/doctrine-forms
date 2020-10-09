<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.txt that was distributed with this source code.
 */

namespace Kdyby\DoctrineForms\Controls;

use Doctrine\ORM\EntityManager;
use Kdyby;
use Kdyby\DoctrineForms\EntityFormMapper;
use Kdyby\DoctrineForms\IComponentMapper;
use Doctrine\ORM\Mapping\ClassMetadata;
use Nette;
use Nette\ComponentModel\Component;
use Nette\Forms\Controls\BaseControl;
use Nette\Forms\Controls\ChoiceControl;
use Nette\Forms\Controls\MultiChoiceControl;
use Symfony\Component\PropertyAccess\Exception\UninitializedPropertyException;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Doctrine\Common\Collections\ArrayCollection;



/**
 * @author Filip Procházka <filip@prochazka.su>
 */
class TextControl implements IComponentMapper
{
	use \Nette\SmartObject;
	
	/**
	 * @var EntityFormMapper
	 */
	private $mapper;

	/**
	 * @var PropertyAccessor
	 */
	private $accessor;

	/**
	 * @var EntityManager
	 */
	private $em;



	public function __construct(EntityFormMapper $mapper)
	{
		$this->mapper = $mapper;
		$this->em = $this->mapper->getEntityManager();
		$this->accessor = $mapper->getAccessor();
	}



	/**
	 * {@inheritdoc}
	 */
	public function load(ClassMetadata $meta, Component $component, $entity, $force = FALSE)
	{
		$valueSetter = $force ? 'setValue' : 'setDefaultValue';

		if (!$component instanceof BaseControl) {
			return FALSE;
		}

		if ($meta->hasField($name = $component->getOption(self::FIELD_NAME, $component->getName()))) {
			try {
				$gettedValue = $this->accessor->getValue($entity, $name);
			} catch (UninitializedPropertyException $e) {
				$gettedValue = NULL;
			}

			$component->$valueSetter($gettedValue);
			return TRUE;
		}

		if (!$meta->hasAssociation($name)) {
			return FALSE;
		}

		/** @var ChoiceControl|MultiChoiceControl $component */
		if (
			($component instanceof ChoiceControl || $component instanceof MultiChoiceControl)
			&&
			!count($component->getItems())
			&&
			($nameKey = $component->getOption(self::ITEMS_TITLE, FALSE))
		) {
			$criteria = $component->getOption(self::ITEMS_FILTER, array());
			$orderBy = $component->getOption(self::ITEMS_ORDER, array());

			$related = $this->relatedMetadata($entity, $name);
			$items = $this->findPairs($related, $criteria, $orderBy, $nameKey);
			$component->setItems($items);
		}

		/** @var MultiChoiceControl $component */
		if ($component instanceof MultiChoiceControl) {
			if (!$collection = $this->getCollection($meta, $entity, $component->getName())) {
				return FALSE;
			}

			$UoW = $this->em->getUnitOfWork();

			$value = [];
			foreach ($collection as $key => $relation) {
				$value[] = $UoW->getSingleIdentifierValue($relation);
			}
			$component->$valueSetter($value);

		} else {
			try {
				$relation = $this->accessor->getValue($entity, $name);
			} catch (UninitializedPropertyException $e) {
				$relation = NULL;
			}

			if ($relation) {
				$UoW = $this->em->getUnitOfWork();
				$component->$valueSetter($UoW->getSingleIdentifierValue($relation));
			}
		}

		return TRUE;
	}



	/**
	 * @param string|object $entity
	 * @param string $relationName
	 * @return ClassMetadata|Kdyby\Doctrine\Mapping\ClassMetadata
	 */
	private function relatedMetadata($entity, $relationName)
	{
		$meta = $this->em->getClassMetadata(is_object($entity) ? get_class($entity) : $entity);
		$targetClass = $meta->getAssociationTargetClass($relationName);
		return $this->em->getClassMetadata($targetClass);
	}



	/**
	 * @param ClassMetadata $meta
	 * @param array $criteria
	 * @param array $orderBy
	 * @param string|callable $nameKey
	 * @return array
	 */
	private function findPairs(ClassMetadata $meta, $criteria, $orderBy, $nameKey)
	{
		$repository = $this->em->getRepository($meta->getName());

		if ($repository instanceof Kdyby\Doctrine\EntityDao && !is_callable($nameKey)) {
			return $repository->findPairs($criteria, $nameKey, $orderBy);
		}

		$items = array();
		$idKey = $meta->getSingleIdentifierFieldName();
		foreach ($repository->findBy($criteria, $orderBy) as $entity) {
			$items[$this->accessor->getValue($entity, $idKey)] = is_callable($nameKey)
				? $nameKey($entity)
				: $this->accessor->getValue($entity, $nameKey);
		}

		return $items;
	}



	/**
	 * {@inheritdoc}
	 */
	public function save(ClassMetadata $meta, Component $component, $entity)
	{
		if (!$component instanceof BaseControl) {
			return FALSE;
		}

		if ($meta->hasField($name = $component->getOption(self::FIELD_NAME, $component->getName()))) {
			$value = $component->getValue();
			if (is_object($value) && $value instanceof \DateTimeImmutable) {
				$value = new \DateTime($value->format('Y-m-d H:i:s'));
			}
			elseif ($meta->isNullable($component->getName()) && $value === '') {
				$value = NULL;
			}
			$this->accessor->setValue($entity, $name, $value);
			return TRUE;
		}

		if (!$meta->hasAssociation($name)) {
			return FALSE;
		}

		/** @var ChoiceControl|MultiChoiceControl $component */
		if (
			($component instanceof ChoiceControl || $component instanceof MultiChoiceControl)
			&&
			!count($component->getItems())
			&&
			($nameKey = $component->getOption(self::ITEMS_TITLE, FALSE))
		) {
			$criteria = $component->getOption(self::ITEMS_FILTER, array());
			$orderBy = $component->getOption(self::ITEMS_ORDER, array());

			$related = $this->relatedMetadata($entity, $name);
			$items = $this->findPairs($related, $criteria, $orderBy, $nameKey);
			$component->setItems($items);
		}

		$identifier = $component->getValue();

		$repository = $this->em->getRepository($this->relatedMetadata($entity, $name)->getName());

		/** @var MultiChoiceControl $component */
		if ($component instanceof MultiChoiceControl) {

			if (!$collection = $this->getCollection($meta, $entity, $component->getName())) {
				return FALSE;
			}

			$collectionByIds = [];
			foreach ($collection as $i) {
				$collectionByIds[] = $i->getId();
			}

			$identifiers = $identifier ? $identifier : [];
			$received = [];

			foreach ($identifiers as $identifier) {
				if (empty($identifier)) continue;

				if (!in_array($identifier, $collectionByIds)) { // entity was added from the client
					$collection[] = $relation = $repository->find($identifier);
				}

				$received[] = $identifier;
			}

			foreach ($collection as $key => $relation) {
				if (!in_array($relation->getId(), $received)) {
					unset($collection[$key]);
				}
			}

		} else {
			if ($identifier && ($relation = $repository->find($identifier))) {
				$meta->setFieldValue($entity, $name, $relation);
			} else {
				$meta->setFieldValue($entity, $name, NULL);
			}
		}

		return TRUE;
	}




	/**
	 * @param ClassMetadata $meta
	 * @param object $entity
	 * @param string $field
	 * @return Collection
	 */
	private function getCollection(ClassMetadata $meta, $entity, $field)
	{
		if (!$meta->hasAssociation($field) || $meta->isSingleValuedAssociation($field)) {
			return FALSE;
		}

		$collection = $meta->getFieldValue($entity, $field);
		if ($collection === NULL) {
			$collection = new ArrayCollection();
			$meta->setFieldValue($entity, $field, $collection);
		}

		return $collection;
	}

}
