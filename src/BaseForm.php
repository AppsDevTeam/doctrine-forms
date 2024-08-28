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

		$this->setOnBeforeInitForm(function(Form $form) {
			if ($this->entity) {
				$form->setEntity($this->entity);
			}
		});

		// we don't call setter intentionally to avoid logic in setter
		$this->onAfterInitForm[] = [$this, 'initOnAfterMapToForm'];

		$this->setOnBeforeProcessForm(function(Form $form) {
			if (!($this->entity && $this->entity->getId()) && method_exists($this, 'initEntity')) {
				$this->entity = $this->initEntity();
				$form->setEntity($this->entity);
			}

			if ($form->getEntity()) {
				$form->mapToEntity();

				$this->onAfterMapToEntity($form);
			}
		});

		$this->paramResolvers[] = function($type) {
			if (is_subclass_of($type, Entity::class)) {
				return $this->entity;
			} elseif ($type === Entity::class) {
				return $this->entity;
			} elseif ($type) {
				return null;
			}

			return false;
		};
	}

	// we need to call initOnAfterMapToForm last,
	// so we will remove initOnAfterMapToForm, add callback and add initOnAfterMapToForm again
	public function setOnAfterInitForm(callable $onAfterInitForm)
	{
		array_pop($this->onAfterInitForm);
		$this->onAfterInitForm[] = $onAfterInitForm;
		$this->onAfterInitForm[] = [$this, 'initOnAfterMapToForm'];
		return $this;
	}

	public function setOnAfterMapToForm(callable $onAfterMapToForm)
	{
		$this->onAfterMapToForm[] = $onAfterMapToForm;
		return $this;
	}

	public function setOnAfterMapToEntity(callable $onAfterMapToEntity)
	{
		$this->onAfterMapToEntity[] = $onAfterMapToEntity;
		return $this;
	}


	final public function setEntity(?Entity $entity)
	{
		$this->entity = $entity;
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
