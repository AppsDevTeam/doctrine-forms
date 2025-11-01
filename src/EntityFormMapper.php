<?php

namespace ADT\DoctrineForms;

use ADT\DoctrineComponents\Entities\Entity;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Iterator;
use Nette\Forms\Container;
use Nette\Forms\Control;
use Symfony\Component\PropertyAccess\PropertyAccessor;

class EntityFormMapper
{
	private EntityManagerInterface $em;

	/**
	 * @var IComponentMapper[]
	 */
	private array $componentMappers;
	
	private ?PropertyAccessor $accessor = null;
	private Form $entityForm;

	public function __construct(EntityManagerInterface $entityManager, Form $entityForm)
	{
		$this->em = $entityManager;
		$this->entityForm = $entityForm;

		$this->componentMappers = array(
			new Controls\TextControl($this),
			new Controls\ToOne($this),
			new Controls\ToMany($this),
		);
	}

	public function getAccessor(): ?PropertyAccessor
	{
		if ($this->accessor === NULL) {
			$this->accessor = new PropertyAccessor(TRUE);
		}

		return $this->accessor;
	}

	public function getEntityManager(): EntityManagerInterface
	{
		return $this->em;
	}

	public function load(Entity $entity, Container|Control $formElement): void
	{
		$meta = $this->getMetadata($entity);

		foreach (self::iterate($formElement) as $component) {
			foreach ($this->componentMappers as $mapper) {
				if ($mapper->load($meta, $component, $entity)) {
					break;
				}
			}
		}
	}

	public function save(Entity $entity, Container|Control $formElement): void
	{
		$meta = $this->getMetadata($entity);

		foreach (self::iterate($formElement) as $component) {
			foreach ($this->componentMappers as $mapper) {
				if ($mapper->save($meta, $component, $entity)) {
					break;
				}
			}
		}
	}

	private static function iterate(Container|Control $formElement): Iterator|array
	{
		if ($formElement instanceof Container) {
			return $formElement->getComponents();
		} else {
			return array($formElement);
		}
	}

	public function getMetadata(object $entity): ClassMetadata
	{
		return $this->em->getClassMetadata(get_class($entity));
	}
	
	public function getForm(): Form
	{
		return $this->entityForm;
	}
}
