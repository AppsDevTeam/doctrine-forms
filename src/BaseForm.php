<?php

namespace ADT\DoctrineForms;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\UnitOfWork;
use Doctrine\Persistence\Mapping\MappingException;
use Exception;

/**
 * @method onAfterMapToForm($form)
 * @method onAfterMapToEntity($form)
 */
abstract class BaseForm extends \ADT\Forms\BaseForm
{
	abstract protected function getEntityManager(): EntityManagerInterface;

	/** @var null|callable|object */
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
				if (method_exists($this, 'initEntity')) {
					$entity = $this->initEntity();
					if (is_callable($this->entity)) {
						($this->entity)($entity);
					}
					$this->entity = $entity;
					if ($this->entity) {
						$this->checkEntity($this->entity);
						$form->setEntity($this->entity);
					}
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
			$this->onAfterMapToForm($form);
		}
	}

	/**
	 * @throws Exception
	 */
	final public function setEntity(object|callable|null $entity): static
	{
		if ($entity && !is_callable($entity)) {
			$this->checkEntity($entity);

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

	private function checkEntity(object $entity)
	{
		try {
			$this->getEntityManager()->getClassMetadata($entity::class);
		} catch (MappingException) {
			throw new Exception(sprintf('Class %s is not a valid Doctrine entity.', $entity::class));
		}
	}

	final protected function getEntity()
	{
		return $this->entity && !is_callable($this->entity) ? $this->entity : null;
	}
}
