<?php

namespace ADT\DoctrineForms;

use Nette;
use Nette\Application\UI;
use Nette\Application\UI\Presenter;

class ToManyContainer extends BaseContainer
{
	const NEW_PREFIX = '_new_';

	/**
	 * @var ToOneContainerFactory
	 */
	private ToOneContainerFactory $toOneContainerFactory;

	/**
	 * ToManyContainer constructor.
	 */
	public function __construct()
	{
		$this->monitor(Presenter::class, function() {
			$this->onAttach();
		});
	}

	protected function onAttach(): void
	{
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
	 * @return array
	 */
	private function getHttpData()
	{
		$path = explode(self::NAME_SEPARATOR, $this->lookupPath('Nette\Application\UI\Form'));
		$allData = $this->getForm()->getHttpData();
		return Nette\Utils\Arrays::get($allData, $path, NULL);
	}
}
