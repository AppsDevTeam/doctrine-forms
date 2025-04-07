<?php

namespace ADT\DoctrineForms\Controls;

use ADT\Forms\DynamicContainer;
use ADT\Forms\StaticContainer;
use DateTimeImmutable;
use DateTimeZone;
use Doctrine\Common\Collections\ArrayCollection;
use ADT\DoctrineForms\EntityFormMapper;
use ADT\DoctrineForms\IComponentMapper;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\MappingException;
use Exception;
use Nette\ComponentModel\Component;
use ReflectionException;
use ReflectionProperty;

class ToMany implements IComponentMapper
{
	use CreateEntityTrait;

	/**
	 * @var EntityFormMapper
	 */
	private EntityFormMapper $mapper;

	/**
	 * ToMany constructor.
	 * @param EntityFormMapper $mapper
	 */
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
		if (!$component instanceof DynamicContainer) {
			return false;
		}

		if ($callback = $this->mapper->getForm()->getComponentFormMapper($component)) {
			$callback($this->mapper, $component, $entity);
			return true;
		}

		if ($meta->hasField($component->getName()) && $meta->getFieldMapping($component->getName())['type'] === 'json') {
			$reflectionProperty = new ReflectionProperty(get_class($entity), $component->getName());
			$reflectionProperty->setAccessible(true);
			$data = $reflectionProperty->isInitialized($entity) ? $reflectionProperty->getValue($entity) : null;
			if ($data) {
				foreach ($data as $row => $values) {
					if (!$component->form->isSubmitted() || isset($component->getUntrustedValues('array')[$row])) {
						foreach ($values as $key => $value) {
							if (isset($component[$row][$key])) {
								self::setDateTimeFromArray($value);
								$component[$row][$key]->setDefaultValue($value);
							}
						}
					}
				}
			}
			return true;
		}

		if (!$collection = $this->getCollection($meta, $entity, $component->getName())) {
			return false;
		}

		$em = $this->mapper->getEntityManager();
		$UoW = $em->getUnitOfWork();

		foreach ($collection as $key => $relation) {
			// mapuj jen pri neodeslanem formulari nebo pokud nebyl radek odstranen uzivatelem
			if (!$component->form->isSubmitted() || isset($component->getUntrustedValues('array')[$key])) {
				if ($UoW->getSingleIdentifierValue($relation)) {
					$this->mapper->load($relation, $component[$key]);
					// we have to fill isFilled component value
					// if isFilled component is set
					if ($component[$key]->getIsFilledComponent()) {
						$component[$key]->getIsFilledComponent()->setDefaultValue(true);
					}

					continue;
				}

				$this->mapper->load($relation, $component[$key]);
			}
		}

		return true;
	}

	/**
	 * @param ClassMetadata $meta
	 * @param Component $component
	 * @param $entity
	 * @return bool
	 * @throws MappingException
	 */
	public function save(ClassMetadata $meta, Component $component, $entity): bool
	{
		if (!$component instanceof DynamicContainer) {
			return false;
		}

		if ($callback = $this->mapper->getForm()->getComponentEntityMapper($component)) {
			$callback($this->mapper, $component, $entity);
			return true;
		}

		if ($meta->hasField($component->getName()) && $meta->getFieldMapping($component->getName())['type'] === 'json') {
			$this->mapper->getAccessor()->setValue($entity, $component->getName(), array_values((array) $component->getValues()));
			return true;
		}

		if (!$collection = $this->getCollection($meta, $entity, $component->getName())) {
			return false;
		}

		$received = [];

		/** @var StaticContainer $container */
		foreach ($component->getComponents() as $container) {
			// entity was added from the client
			if (str_starts_with($container->getName(), DynamicContainer::NEW_PREFIX)) {
				// we don't want to create an entity
				// if adding new ones is disabled
				if (! $component->isAllowAdding()) {
					continue;
				}

				// we don't want to create an entity
				// if the entire container is empty
				if ($container->isEmpty()) {
					continue;
				}

				$collection[$container->getName()] = $relation = $this->createEntity($meta, $component, $entity);
			}
			// container does not have a _new_ prefix, and it's not in the collection
			elseif (!$relation = $collection->get($container->getName())) {
				continue;
			}

			$received[] = $container->getName();

			$this->mapper->save($relation, $container);
		}

		foreach ($collection as $key => $relation) {
			if (!in_array((string) $key, $received)) {
				unset($collection[$key]);
			}
		}

		return true;
	}

	/**
	 * @param ClassMetadata $meta
	 * @param $entity
	 * @param $field
	 * @return bool|ArrayCollection|mixed
	 */
	private function getCollection(ClassMetadata $meta, $entity, $field): mixed
	{
		if (!$meta->hasAssociation($field) || $meta->isSingleValuedAssociation($field)) {
			return false;
		}

		$collection = $meta->getFieldValue($entity, $field);
		if ($collection === null) {
			$collection = new ArrayCollection();
			$meta->setFieldValue($entity, $field, $collection);
		}

		return $collection;
	}

	private static function setDateTimeFromArray(array|string|null &$value): bool
	{
		if (!isset($value['date'], $value['timezone'], $value['timezone_type'])) {
			return false;
		}

		if (!is_string($value['date']) || !is_string($value['timezone']) || !is_int($value['timezone_type'])) {
			return false;
		}

		try {
			$value = new DateTimeImmutable($value['date'], new DateTimeZone($value['timezone']));
			return true;
		} catch (Exception) {
			return false;
		}
	}
}
