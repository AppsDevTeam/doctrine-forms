<?php

namespace ADT\DoctrineForms;

use Closure;
use Exception;

/**
 * @method onAfterMapToForm($form)
 * @method onAfterMapToEntity($form)
 */
abstract class BaseForm extends \ADT\Forms\BaseForm
{
	protected Entity|Closure|null $entity = null;

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
			if (is_callable($this->entity)) {
				$form->setEntity(($this->entity)());
			} elseif ($this->entity) {
				$form->setEntity($this->entity);
			}
		});

		// we don't call setter intentionally to avoid logic in setter
		$this->onAfterInitForm[] = [$this, 'initOnAfterMapToForm'];

		$this->setOnBeforeProcessForm(function(Form $form) {
			if (
				method_exists($this, 'initEntity')
				&&
				!$this->entity
			) {
				$this->entity = $this->initEntity();
				$form->setEntity($this->entity);
			} elseif (is_callable($this->entity)) {
				$form->setEntity(($this->entity)());
			}

			if ($form->getEntity()) {
				$form->mapToEntity();

				$this->onAfterMapToEntity($form);
			}
		});

		$this->paramResolvers[] = function(string $type, object|array|null $values, string $methodName) {
			if ((is_subclass_of($type, Entity::class) || $type === Entity::class) && ($this->entity->getId() || $methodName === 'processForm')) {
				return $this->entity;
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

	final public function setEntity(Entity|callable|null $entity): static
	{
		if (is_callable($entity)) {
			$this->entity = $entity;
		} elseif ($entity instanceof Entity) {
			$this->entity = $entity;
		} elseif ($this->getEntityClass()) {
			$this->entity = new ($this->getEntityClass());
		}

		return $this;
	}

	protected function createComponentForm(): Form
	{
		return new Form();
	}

	/**
	 * @throws Exception
	 * @internal
	 */
	public function initOnAfterMapToForm(Form $form): void
	{
		if ($form->getEntity()) {
			$form->mapToForm();

			$this->onAfterMapToForm($form);
		}
	}

	abstract public function getEntityClass(): ?string;
}
