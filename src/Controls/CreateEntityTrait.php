<?php

namespace ADT\DoctrineForms\Controls;

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Nette\ComponentModel\Component;

trait CreateEntityTrait
{
	protected function createEntity(ClassMetadata $meta, Component $component, $entity)
	{
		if (!$callback = $this->mapper->getForm()->getComponentEntityFactory($component)) {
			$relation = $this->mapper->getEntityManager()->getClassMetadata($meta->getAssociationTargetClass($component->getName()))->newInstance();
			if ($meta->getAssociationMapping($component->getName())['type'] === ClassMetadataInfo::ONE_TO_MANY) {
				$relation->{'set' . (new \ReflectionClass($entity))->getShortName()}($entity);
			}
			return $relation;
		}

		return $callback();
	}
}