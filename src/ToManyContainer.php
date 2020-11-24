<?php

namespace ADT\DoctrineForms;

use Nette;
use Nette\Application\UI;
use Nette\Application\UI\Presenter;
use Closure;

class ToManyContainer extends BaseContainer
{
	const NEW_PREFIX = '_new_';

	/**
	 * @var ToOneContainerFactory
	 */
	private ToOneContainerFactory $toOneContainerFactory;

	/**
	 * @var Closure|null
	 */
	private ?Closure $formMapper = null;

	/**
	 * @var Closure|null
	 */
	private ?Closure $entityMapper = null;

	/**
	 * @var bool
	 */
	private bool $allowAdding = true;

	/**
	 * @var ToOneContainer|null 
	 */
	private ?ToOneContainer $template = null;

	/**
	 * ToManyContainer constructor.
	 */
	public function __construct()
	{
		$this->monitor(Presenter::class, function() {
			/** @var UI\Form|EntityForm $form */
			$form = $this->getForm();

			if (!$form->isSubmitted()) {
				return;
			}

			if ($this->getHttpData()) {
				foreach (array_keys($this->getHttpData()) as $id) {
					$this->getComponent($id); // eager initialize
				}
			}
		});
	}

	/**
	 * @param array|null $controls
	 */
	public function validate(?array $controls = NULL): void
	{
		if (
			$this->isRequired()
			&&
			!iterator_count($this->getComponents())
		) {
			$this->addText(static::ERROR_CONTROL_NAME)
				->addError($this->getRequiredMessage());
		}
	}

	/**
	 * @param string $name
	 * @return Nette\ComponentModel\IComponent|null
	 */
	protected function createComponent($name): ?Nette\ComponentModel\IComponent
	{
		return $this[$name] = $this->toOneContainerFactory->create();
	}

	/**
	 * @param $toOneContainerFactory
	 * @return $this
	 */
	public function setToOneContainerFactory($toOneContainerFactory)
	{
		$this->toOneContainerFactory = $toOneContainerFactory;
		return $this;
	}

	/**
	 * @return ToOneContainer
	 */
	public function getTemplate()
	{
		if (!$this->template) {
			$this->template = $this[static::NEW_PREFIX];
		}
		return $this->template;
	}

	/**
	 * @return Closure
	 */
	public function getFormMapper()
	{
		return $this->formMapper;
	}

	/**
	 * @param \Closure $onAfterMapToForm
	 * @return $this
	 */
	public function setFormMapper(\Closure $formMapper)
	{
		$this->formMapper = $formMapper;
		return $this;
	}

	/**
	 * @return Closure|null
	 */
	public function getEntityMapper()
	{
		return $this->entityMapper;
	}

	/**
	 * @param Closure $entityMapper
	 * @return $this
	 */
	public function setEntityMapper(\Closure $entityMapper)
	{
		$this->entityMapper = $entityMapper;
		return $this;
	}

	/**
	 * @return bool
	 */
	public function isAllowAdding()
	{
		return $this->allowAdding;
	}

	/**
	 * @param bool $allowAdding
	 * @return $this
	 */
	public function setAllowAdding(bool $allowAdding)
	{
		$this->allowAdding = $allowAdding;
		return $this;
	}

	/**
	 * @return array
	 */
	private function getHttpData()
	{
		$path = explode(self::NAME_SEPARATOR, $this->lookupPath('Nette\Application\UI\Form'));
		$allData = $this->getForm()->getHttpData();
		return Nette\Utils\Arrays::get($allData, $path, NULL);
	}

	/**
	 * @param bool $deep
	 * @param string|null $filterType
	 * @return \Iterator
	 */
	final public function getContainers(): \Iterator
	{
		$template = $this->getTemplate();
		return new \CallbackFilterIterator(parent::getComponents(false, ToOneContainer::class), function ($item) use ($template) {
			return $item !== $template;
		});
	}
}
