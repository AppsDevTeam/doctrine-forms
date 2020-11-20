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
		return $this[$name] = $container = $this->toOneContainerFactory->create();
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
	 * @param null $name
	 * @return Nette\ComponentModel\IComponent|Nette\Forms\Controls\BaseControl
	 */
	public function createOne($name = NULL)
	{
		if ($name === NULL) {
			$names = array_map(function($key) {
				return substr($key, strlen(ToManyContainer::NEW_PREFIX)); // TODO statickou funkci
			}, array_keys(iterator_to_array($this->getComponents())));
			$name = $names ? max($names) + 1 : 0;
		}

		return $this[ToManyContainer::NEW_PREFIX . $name];
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
	 * @return array
	 */
	private function getHttpData()
	{
		$path = explode(self::NAME_SEPARATOR, $this->lookupPath('Nette\Application\UI\Form'));
		$allData = $this->getForm()->getHttpData();
		return Nette\Utils\Arrays::get($allData, $path, NULL);
	}
}
