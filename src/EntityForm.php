<?php

namespace ADT\DoctrineForms;

use ADT\DoctrineForms\Exceptions\InvalidArgumentException;
use Nette;
use Nette\Application\UI;
use Nette\DI\Container;

/**
 * @method ToManyContainer toMany($name, $containerFactory = NULL, $entityFactory = NULL)
 * @method onSubmit(UI\Form $self)
 * @method onError(UI\Form $self)
 */

/**
 * Trait EntityForm
 * @package ADT\DoctrineForms
 * @target
 */
trait EntityForm
{
	/** @var EntityFormMapper */
	private $entityMapper;

	/** @var object */
	private $entity;

	/** @var Container */
	protected Container $dic;

	/** @var callable[] */
	public $onAfterMapToEntity = [];

	public function setOnAfterMapToEntity(callable $onAfterMapToEntity)
	{
		$this->onAfterMapToEntity[] = $onAfterMapToEntity;
	}

	/**
	 * @param EntityFormMapper $mapper
	 * @return EntityForm|UI\Form|
	 */
	public function injectEntityMapper(EntityFormMapper $mapper)
	{
		$this->entityMapper = $mapper;
		return $this;
	}

	/**
	 * @return \ADT\DoctrineForms\EntityFormMapper
	 */
	public function getEntityMapper()
	{
		if ($this->entityMapper === NULL) {
			$this->entityMapper = $this->dic->getByType('ADT\DoctrineForms\EntityFormMapper');
		}

		return $this->entityMapper;
	}

	/**
	 * @return object
	 */
	public function setEntity($entity)
	{
		if (!is_object($entity)) {
			throw new InvalidArgumentException('Expected object, ' . gettype($entity) . ' given.');
		}
		
		$this->entity = $entity;
		return $this;
	}

	/**
	 * @return object
	 */
	public function getEntity()
	{
		return $this->entity;
	}

	public function fireEvents(): void
	{
		/** @var EntityForm|UI\Form $this */

		if (!$submittedBy = $this->isSubmitted()) {
			return;
		}

		$this->validate();

		if ($submittedBy instanceof Nette\Forms\ISubmitterControl) {
			if ($this->isValid()) {
				$submittedBy->onClick($submittedBy);
			} else {
				$submittedBy->onInvalidClick($submittedBy);
			}
		}

		if ($this->onSuccess && $this->isSubmitted()->getValidationScope() === null) {
			if ($this->isValid() && $this->entity) {
				$this->mapToEntity();
				$this->onAfterMapToEntity($this->entity, $this->getValues());
			}

			foreach ($this->onSuccess as $handler) {
				if (!$this->isValid()) {
					$this->onError($this);
					break;
				}
				$params = Nette\Utils\Callback::toReflection($handler)->getParameters();
				$values = isset($params[1]) ? $this->getValues($params[1]->isArray()) : NULL;
				$handler($this, $values);
			}
		} elseif (!$this->isValid()) {
			$this->onError($this);
		}
		$this->onSubmit($this);
	}

	public function setDic(Container $dic)
	{
		$this->dic = $dic;
		return $this;
	}

	public function mapToForm()
	{
		if (!$this->entity) {
			throw new \Exception('An entity is not set.');
		}

		$this->getEntityMapper()->load($this->entity, $this);
	}

	protected function mapToEntity()
	{
		$this->getEntityMapper()->save($this->entity, $this);
	}
}
