<?php

namespace ADT\DoctrineForms\Controls;

use ADT\DoctrineComponents\Entities\Entity;
use ADT\DoctrineForms\EntityFormMapper;
use ADT\DoctrineForms\IComponentMapper;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\MappingException;
use Exception;
use Nette\ComponentModel\Component;
use Nette\Forms\Controls\BaseControl;
use Nette\Forms\Controls\ChoiceControl;
use Nette\Forms\Controls\MultiChoiceControl;
use ReflectionException;
use ReflectionProperty;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Doctrine\Common\Collections\ArrayCollection;
use UnitEnum;

class TextControl implements IComponentMapper
{
	private EntityFormMapper $mapper;
	private ?PropertyAccessor $accessor;
	private EntityManagerInterface $em;

	public function __construct(EntityFormMapper $mapper)
	{
		$this->mapper = $mapper;
		$this->em = $this->mapper->getEntityManager();
		$this->accessor = $mapper->getAccessor();
	}

	/**
	 * @throws ReflectionException
	 * @throws MappingException
	 */
	public function load(ClassMetadata $meta, Component $component, Entity $entity): bool
	{
		if (!$component instanceof BaseControl) {
			return FALSE;
		}

		if ($callback = $this->mapper->getForm()->getComponentFormMapper($component)) {
			$callback($this->mapper, $component, $entity);
			return true;
		}

		if ($meta->hasField($name = $component->getOption(self::FIELD_NAME) ?? $component->getName())) {
			$reflectionProperty = new ReflectionProperty(get_class($entity), $name);
			$reflectionProperty->setAccessible(true);
			$gettedValue = !$entity->isNew() || $reflectionProperty->isInitialized($entity) ? $reflectionProperty->getValue($entity) : null;
			if ($component->getOption('hidden') !== true) {
				$component->setDefaultValue($gettedValue instanceof UnitEnum ? $gettedValue->value : $gettedValue);
			}
			return TRUE;
		}

		if (!$meta->hasAssociation($name)) {
			return FALSE;
		}

		$this->setItems($component, $entity, $name);

		/** @var MultiChoiceControl $component */
		if ($component instanceof MultiChoiceControl) {
			if (!$collection = $this->getCollection($meta, $entity, $component->getName())) {
				return FALSE;
			}

			$UoW = $this->em->getUnitOfWork();

			$value = [];
			foreach ($collection as $relation) {
				$value[] = $UoW->getSingleIdentifierValue($relation);
			}
			$component->setDefaultValue($value);

		} else {
			$reflectionProperty = new ReflectionProperty(get_class($entity), $name);
			$reflectionProperty->setAccessible(true);
			$relation = $reflectionProperty->isInitialized($entity) ? $reflectionProperty->getValue($entity) : null;

			if ($relation) {
				$UoW = $this->em->getUnitOfWork();
				$component->setDefaultValue($UoW->getSingleIdentifierValue($relation));
			}
		}

		return TRUE;
	}

	private function relatedMetadata(Entity $entity, string $relationName): ClassMetadata
	{
		$meta = $this->em->getClassMetadata(get_class($entity));
		$targetClass = $meta->getAssociationTargetClass($relationName);
		return $this->em->getClassMetadata($targetClass);
	}

	/**
	 * @param ClassMetadata $meta
	 * @param $criteria
	 * @param $orderBy
	 * @param $nameKey
	 * @return array
	 * @throws MappingException
	 */
	private function findPairs(ClassMetadata $meta, $criteria, $orderBy, $nameKey): array
	{
		$repository = $this->em->getRepository($meta->getName());

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
	 * @param ClassMetadata $meta
	 * @param Component $component
	 * @param $entity
	 * @return bool
	 * @throws Exception
	 */
	public function save(ClassMetadata $meta, Component $component, $entity): bool
	{
		if (!$component instanceof BaseControl) {
			return false;
		}

		if ($callback = $this->mapper->getForm()->getComponentEntityMapper($component)) {
			$callback($this->mapper, $component, $entity);
			return true;
		}

		if ($meta->hasField($name = $component->getOption(self::FIELD_NAME) ?? $component->getName())) {
			$value = $component->getValue();

			if ($meta->isNullable($component->getName()) && $value === '' || $component->getOption('hidden') === true) {
				$value = NULL;
			}
			$value = $this->getEnumOrValue(get_class($entity), $name, $value);
			$this->accessor->setValue($entity, $name, $value);
			return true;
		}

		// sometimes we want to save some metadata to an entity,
		// for example, base64 of an image cropped in a browser
		if (
			!$meta->hasAssociation($name)
			&&
			property_exists($entity, $component->getName())
		) {
			$this->accessor->setValue($entity, $name, $component->getValue());
			return true;
		}

		if (!$meta->hasAssociation($name)) {
			return false;
		}

		$this->setItems($component, $entity, $name);

		$identifier = $component->getValue();

		$repository = $this->em->getRepository($this->relatedMetadata($entity, $name)->getName());

		/** @var MultiChoiceControl $component */
		if ($component instanceof MultiChoiceControl) {

			if (!$collection = $this->getCollection($meta, $entity, $component->getName())) {
				return false;
			}

			$collectionByIds = [];
			foreach ($collection as $i) {
				$collectionByIds[] = $i->getId();
			}

			$identifiers = $identifier ?: [];
			$received = [];

			foreach ($identifiers as $identifier) {
				if (empty($identifier)) continue;

				if (!in_array($identifier, $collectionByIds)) { // entity was added from the client
					$collection[] = $repository->find($identifier);
				}

				$received[] = $identifier;
			}

			foreach ($collection as $key => $relation) {
				if (!in_array($relation->getId(), $received)) {
					unset($collection[$key]);
				}
			}

		} else {
			$this->accessor->setValue($entity, $name, $identifier ? $repository->find($identifier) : null);
		}

		return TRUE;
	}

	private function getCollection(ClassMetadata $meta, Entity $entity, string $field)
	{
		if (!$meta->hasAssociation($field) || $meta->isSingleValuedAssociation($field)) {
			return false;
		}

		$collection = $meta->getFieldValue($entity, $field);
		if ($collection === NULL) {
			$collection = new ArrayCollection();
			$meta->setFieldValue($entity, $field, $collection);
		}

		return $collection;
	}

	/**
	 * @throws MappingException
	 */
	private function setItems(Component $component, Entity $entity, string $name): void
	{
		/** @var ChoiceControl|MultiChoiceControl $component */
		if (
			($component instanceof ChoiceControl || $component instanceof MultiChoiceControl)
			&&
			!count($component->getItems())
			&&
			($nameKey = $component->getOption(self::ITEMS_TITLE) ?? FALSE)
		) {
			$criteria = $component->getOption(self::ITEMS_FILTER) ?? array();
			$orderBy = $component->getOption(self::ITEMS_ORDER) ?? array();

			$related = $this->relatedMetadata($entity, $name);
			$items = $this->findPairs($related, $criteria, $orderBy, $nameKey);
			$component->setItems($items);
		}
	}

	/**
	 * @throws ReflectionException
	 */
	private function getEnumOrValue(string $class, string $property, $value)
	{
		$reflectionProperty = new ReflectionProperty($class, $property);
		$type = $reflectionProperty->getType();
		if ($type && !$type->isBuiltin()) {
			$enumType = $type->getName();
			if (is_subclass_of($enumType, UnitEnum::class)) {
				return $value ? $enumType::from($value) : null;
			} else {
				return $value;
			}
		}
		return $value;
	}
}
