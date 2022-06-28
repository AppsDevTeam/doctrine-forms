<?php

namespace ADT\DoctrineForms;

use Exception;

/**
 * @property Form $form
 * @method onAfterMapToForm($form)
 * @method onAfterMapToEntity($form)
 */
abstract class BaseForm extends \ADT\Forms\BaseForm
{
	protected ?Entity $entity = null;

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

		$this->setOnBeforeInitForm(function($form) {
			if ($this->entity) {
				$form->setEntity($this->entity);
			}
		});

		// we don't call setter intentionally to avoid logic in setter
		$this->onAfterInitForm[] = [$this, 'initOnAfterMapToForm'];

		$this->setOnBeforeProcessForm(function($form) {
			if ($this->form->getEntity()) {
				$this->form->mapToEntity();

				$this->onAfterMapToEntity($form);
			}
		});

		$this->paramResolvers[] = function($type) {
			if (is_subclass_of($type, Entity::class)) {
				return $this->entity;
			} elseif ($type === Entity::class) {
				return $this->entity;
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


	final public function setEntity(Entity $entity): static
	{
		$this->entity = $entity;
		if ($this->form) {
			$this->form->setEntity($entity);
		}

		if (method_exists($this, 'initEntity') && !$entity->getId()) {
			call_user_func([$this, 'initEntity'], $entity);
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
		if ($this->form->getEntity()) {
			$form->mapToForm();

			$this->onAfterMapToForm($form);
		}
	}
}
