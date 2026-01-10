<?php

namespace ADT\DoctrineForms;

use ADT\DoctrineComponents\Entities\Entity;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\UnitOfWork;
use Exception;

/**
 * @method onAfterMapToForm($form, Entity $entity)
 * @method onAfterMapToEntity($form)
 */
abstract class BaseForm extends \ADT\Forms\BaseForm implements BaseFormInterface
{
	abstract protected function getEntityManager(): EntityManagerInterface;

	/** @var null|callable|Entity */
	private $entity = null;

	/**
	 * @internal
	 * @var callable[]
	 */
	public array $onAfterMapToForm = [];

	/**
	 * @internal
	 * @var callable[]
	 */
	public array $onAfterMapToEntity = [];

	public function __construct()
	{
		parent::__construct();

		$this->setOnBeforeInitForm(function(Form $form) {
			if ($this->getEntity()) {
				$form->setEntity($this->entity);
			}
		});

		// we don't call setter intentionally to avoid logic in setter
		$this->onAfterInitForm[] = [$this, 'initOnAfterMapToForm'];

		$this->setOnBeforeProcessForm(function(Form $form) {
			if (!$this->getEntity()) {
				if (is_callable($this->entity)) {
					$this->entity = $this->invokeHandler($this->entity, $form->getValues());
				} elseif (method_exists($this, 'createEntity')) {
					$this->entity = $this->invokeHandler([$this, 'createEntity'], $form->getValues());
				}

				if ($this->entity) {
					if (method_exists($this, 'initEntity')) {
						$this->invokeHandler([$this, 'initEntity'], $form->getValues());
					}
					$form->setEntity($this->entity);
					$this->getEntityManager()->persist($this->entity);
				}
			}

			if ($this->form->getEntity()) {
				$form->mapToEntity();
				$this->onAfterMapToEntity($form);
			}
		});

		$this->paramResolvers[] = function(string $type) {
			if ($this->getEntity() && $this->getEntity() instanceof $type) {
				return $this->getEntity();
			} elseif ($type) {
				return null;
			}

			return false;
		};
	}

	// we need to call initOnAfterMapToForm last,
	// so we will remove initOnAfterMapToForm, add callback and add initOnAfterMapToForm again
	public function setOnAfterInitForm(callable $onAfterInitForm): static
	{
		array_pop($this->onAfterInitForm);
		$this->onAfterInitForm[] = $onAfterInitForm;
		$this->onAfterInitForm[] = [$this, 'initOnAfterMapToForm'];
		return $this;
	}

	public function setOnAfterMapToForm(callable $onAfterMapToForm): static
	{
		$this->onAfterMapToForm[] = $onAfterMapToForm;
		return $this;
	}

	public function setOnAfterMapToEntity(callable $onAfterMapToEntity): static
	{
		$this->onAfterMapToEntity[] = $onAfterMapToEntity;
		return $this;
	}

	/**
	 * @throws Exception
	 * @internal
	 */
	public function initOnAfterMapToForm(Form $form): void
	{
		if ($this->getEntity()) {
			$form->mapToForm();
			$this->onAfterMapToForm($form, $this->getEntity());
		}
	}

	/**
	 * @throws Exception
	 */
	final public function setEntity(Entity|callable|null $entity): static
	{
		if ($entity && !is_callable($entity)) {
			if ($this->getEntityManager()->getUnitOfWork()->getEntityState($entity) === UnitOfWork::STATE_NEW) {
				throw new Exception('Pass the new entity as a callback.');
			}
		}

		$this->entity = $entity;

		return $this;
	}

	protected function createComponentForm()
	{
		return new Form();
	}

	final protected function getEntity(): callable|Entity|null
	{
		return $this->entity && !is_callable($this->entity) ? $this->entity : null;
	}
}
