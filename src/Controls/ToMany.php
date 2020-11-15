<?php

namespace ADT\DoctrineForms\Controls;

use Doctrine\Common\Collections\ArrayCollection;
use ADT\DoctrineForms\EntityFormMapper;
use ADT\DoctrineForms\IComponentMapper;
use ADT\DoctrineForms\ToManyContainer;
use Doctrine\ORM\Mapping\ClassMetadata;
use Nette;
use Nette\ComponentModel\Component;

class ToMany implements IComponentMapper
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
		if (!$component instanceof ToManyContainer) {
			return FALSE;
		}

		if (!$collection = $this->getCollection($meta, $entity, $name = $component->getName())) {
			return FALSE;
		}

		$em = $this->mapper->getEntityManager();
		$UoW = $em->getUnitOfWork();

		$component->bindCollection($entity, $collection);
		foreach ($collection as $key => $relation) {
			if (!$component->form->isSubmitted() || isset($component->values[$key])) {	// nemapuj, pokud byl řádek odstraněn uživatelem
				if ($UoW->getSingleIdentifierValue($relation)) {
					$this->mapper->load($relation, $component[$key]);
					continue;
				}

				$this->mapper->load($relation, $component[ToManyContainer::NEW_PREFIX . $key]);
			}
		}

		return TRUE;
	}

	/**
	 * {@inheritdoc}
	 */
	public function save(ClassMetadata $meta, Component $component, $entity): bool
	{
		if (!$component instanceof ToManyContainer) {
			return FALSE;
		}

		if (!$collection = $this->getCollection($meta, $entity, $component->getName())) {
			return FALSE;
		}

		$em = $this->mapper->getEntityManager();
		$class = $meta->getAssociationTargetClass($component->getName());
		$relationMeta = $em->getClassMetadata($class);

		$received = [];

		/** @var Nette\Forms\Container $container */
		foreach ($component->getComponents(FALSE, 'Nette\Forms\Container') as $container) {
			$isNew = substr($container->getName(), 0, strlen(ToManyContainer::NEW_PREFIX)) === ToManyContainer::NEW_PREFIX;
			$name = $isNew ? substr($container->getName(), strlen(ToManyContainer::NEW_PREFIX)) : $container->getName();

			if ((!$relation = $collection->get($name))) { // entity was added from the client
				$collection[$name] = $relation = $component->createEntity($relationMeta);
			}

			$received[] = $name;

			$this->mapper->save($relation, $container);
		}

		foreach ($collection as $key => $relation) {
			if ($component->isAllowedRemove() && !in_array($key, $received)) {
				unset($collection[$key]);
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
