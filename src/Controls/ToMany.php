<?php

namespace ADT\DoctrineForms\Controls;

use ADT\DoctrineForms\ToOneContainer;
use Doctrine\Common\Collections\ArrayCollection;
use ADT\DoctrineForms\EntityFormMapper;
use ADT\DoctrineForms\IComponentMapper;
use ADT\DoctrineForms\ToManyContainer;
use Doctrine\ORM\Mapping\ClassMetadata;
use Nette\ComponentModel\Component;

class ToMany implements IComponentMapper
{
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
	 * @param ClassMetadata $meta
	 * @param Component $component
	 * @param $entity
	 * @return bool
	 */
	public function load(ClassMetadata $meta, Component $component, $entity): bool
	{
		if (!$component instanceof ToManyContainer) {
			return FALSE;
		}

		if (!$collection = $this->getCollection($meta, $entity, $name = $component->getName())) {
			return FALSE;
		}
		
		if ($component->getFormMapper()) {
			$component->getFormMapper()($component, $entity);
		}
		else {
			$em = $this->mapper->getEntityManager();
			$UoW = $em->getUnitOfWork();

			foreach ($collection as $key => $relation) {
				if (!$component->form->isSubmitted() || isset($component->values[$key])) {	// nemapuj, pokud byl řádek odstraněn uživatelem
					if ($UoW->getSingleIdentifierValue($relation)) {
						$this->mapper->load($relation, $component[$key]);

						// we have to fill isFilled component value
						// if isFilled component is set
						if ($component[$key]->getIsFilledComponent()) {
							$component[$key]->getIsFilledComponent()->setDefaultValue(true);
						}

						continue;
					}

					$this->mapper->load($relation, $component[ToManyContainer::NEW_PREFIX . $key]);
				}
			}
		}

		return TRUE;
	}

	/**
	 * @param ClassMetadata $meta
	 * @param Component $component
	 * @param $entity
	 * @return bool
	 */
	public function save(ClassMetadata $meta, Component $component, $entity): bool
	{
		if (!$component instanceof ToManyContainer) {
			return FALSE;
		}

		if (!$collection = $this->getCollection($meta, $entity, $component->getName())) {
			return FALSE;
		}

		if ($component->getEntityMapper()) {
			$component->getEntityMapper()($component, $entity);
		}
		else {
			$em = $this->mapper->getEntityManager();
			$class = $meta->getAssociationTargetClass($component->getName());
			$relationMeta = $em->getClassMetadata($class);

			$received = [];

			/** @var ToOneContainer $container */
			foreach ($component->getComponents(false) as $container) {
				$isNew = substr($container->getName(), 0, strlen(ToManyContainer::NEW_PREFIX)) === ToManyContainer::NEW_PREFIX;
				$name = $isNew ? substr($container->getName(), strlen(ToManyContainer::NEW_PREFIX)) : $container->getName();

				if ((!$relation = $collection->get($name))) { // entity was added from the client
					$collection[$name] = $relation = $container->createEntity($relationMeta);
				}

				$received[] = $name;

				$this->mapper->save($relation, $container);
			}

			foreach ($collection as $key => $relation) {
				if (!in_array((string) $key, $received)) {
					unset($collection[$key]);
				}
			}
		}

		return TRUE;
	}

	/**
	 * @param ClassMetadata $meta
	 * @param $entity
	 * @param $field
	 * @return bool|ArrayCollection|mixed
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
