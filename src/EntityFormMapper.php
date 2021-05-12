<?php

namespace ADT\DoctrineForms;

use ADT\DoctrineForms\Exceptions\InvalidArgumentException;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use Iterator;
use Nette\ComponentModel\IComponent;
use Nette\Forms\Container;
use Nette\Forms\Controls\BaseControl;
use Nette\Forms\IControl;
use Symfony\Component\PropertyAccess\PropertyAccessor;

class EntityFormMapper
{
	private EntityManager $em;

	/**
	 * @var IComponentMapper[]
	 */
	private array $componentMappers;
	
	private ?PropertyAccessor $accessor = null;
	private Form $entityForm;

	public function __construct(EntityManager $entityManager, Form $entityForm)
	{
		$this->em = $entityManager;
		$this->entityForm = $entityForm;

		$this->componentMappers = array(
			new Controls\TextControl($this),
			new Controls\ToOne($this),
			new Controls\ToMany($this),
		);
	}

	/**
	 * @return PropertyAccessor
	 */
	public function getAccessor()
	{
		if ($this->accessor === NULL) {
			$this->accessor = new PropertyAccessor(TRUE);
		}

		return $this->accessor;
	}

	/**
	 * @return EntityManager
	 */
	public function getEntityManager()
	{
		return $this->em;
	}

	/**
	 * @param object $entity
	 * @param IComponent $formElement
	 */
	public function load($entity, $formElement)
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

	/**
	 * @param object $entity
	 * @param BaseControl|Container $formElement
	 */
	public function save($entity, $formElement)
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

	/**
	 * @param $formElement
	 * @return Iterator|IControl[]
	 */
	private static function iterate($formElement)
	{
		if ($formElement instanceof Container) {
			return $formElement->getComponents();

		} elseif ($formElement instanceof IControl) {
			return array($formElement);

		} else {
			throw new InvalidArgumentException('Expected Nette\Forms\Container or Nette\Forms\IControl, but ' . get_class($formElement) . ' given');
		}
	}

	public function getMetadata(Entity $entity): ClassMetadata
	{
		return $this->em->getClassMetadata(get_class($entity));
	}
	
	public function getForm(): Form
	{
		return $this->entityForm;
	}
}
