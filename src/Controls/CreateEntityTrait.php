<?php

namespace ADT\DoctrineForms\Controls;

use ADT\DoctrineComponents\Entities\Entity;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\MappingException;
use Nette\ComponentModel\Component;
use ReflectionClass;
use ReflectionException;

trait CreateEntityTrait
{
	/**
	 * @throws ReflectionException
	 * @throws MappingException
	 */
	protected function createEntity(ClassMetadata $meta, Component $component, $entity): Entity
	{
		if (!$callback = $this->mapper->getForm()->getComponentEntityFactory($component)) {
			$relation = $this->mapper->getEntityManager()->getClassMetadata($meta->getAssociationTargetClass($component->getName()))->newInstance();
			if ($meta->getAssociationMapping($component->getName())['type'] === ClassMetadata::ONE_TO_MANY) {
				$relation->{'set' . (new ReflectionClass($entity))->getShortName()}($entity);
			}
			return $relation;
		}

		return $callback();
	}
}
