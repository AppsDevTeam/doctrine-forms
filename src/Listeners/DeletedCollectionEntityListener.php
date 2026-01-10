<?php

namespace ADT\DoctrineForms\Listeners;

use ADT\DoctrineComponents\BaseListener;
use ADT\DoctrineComponents\Entities\Entity;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Events;

class DeletedCollectionEntityListener extends BaseListener
{
	private array $deletedEntities = [];

	public function getSubscribedEvents(): array
	{
		return [
			Events::onFlush,
		];
	}
	
	public function addDeletedEntity(Entity $entity)
	{
		$this->deletedEntities[$entity::class][] = $entity;
	}

	public function onFlushCallback(OnFlushEventArgs $args): void
	{
		$em = $args->getObjectManager();

		foreach ($this->deletedEntities as $entityClass => $entities) {
			$em->createQueryBuilder()
				->delete($entityClass, 'e')
				->andWhere('e.id IN (:id)')
				->setParameter('id', $entities)
				->getQuery()
				->execute();
		}
		
		$this->deletedEntities = [];
	}
}