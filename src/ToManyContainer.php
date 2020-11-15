<?php

namespace ADT\DoctrineForms;

use ADT\DoctrineForms\Exceptions\InvalidArgumentException;
use Closure;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping\ClassMetadata;
use Nette;
use Nette\Application\UI;
use Nette\Application\UI\Presenter;

class ToManyContainer extends BaseContainer
{
	const NEW_PREFIX = '_new_';

	/**
	 * @var bool
	 */
	private bool $allowRemove = FALSE;

	/**
	 * @var bool
	 */
	private bool $disableAdding = FALSE;

	/**
	 * @var ToOneContainerFactory
	 */
	private ToOneContainerFactory $toOneContainerFactory;

	/**
	 * ToManyContainer constructor.
	 * @param $containerFactory
	 * @param $entityFactory
	 */
	public function __construct()
	{
		$this->monitor(Presenter::class, function() {
			$this->onAttach();
		});
	}

	public function setToOneContainerFactory($toOneContainerFactory)
	{
		$this->toOneContainerFactory = $toOneContainerFactory;
		return $this;
	}

	/**
	 * @param Nette\ComponentModel\IContainer $parent
	 */
	protected function validateParent(Nette\ComponentModel\IContainer $parent): void
	{
		parent::validateParent($parent);
		$this->monitor('Nette\Application\UI\Presenter');
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
	 * @param $parent
	 * @param Collection $collection
	 */
	public function bindCollection($parent, Collection $collection)
	{
		if (!is_object($parent)) {
			throw new InvalidArgumentException('Expected entity, but ' . gettype($parent) . ' given.');
		}
	}

	/**
	 * @param boolean $allowRemove
	 * @return ToManyContainer
	 */
	public function setAllowRemove($allowRemove = TRUE)
	{
		$this->allowRemove = (bool) $allowRemove;
		return $this;
	}

	/**
	 * @return boolean
	 */
	public function isAllowedRemove()
	{
		return $this->allowRemove;
	}

	/**
	 * @param boolean $disableAdding
	 * @return ToManyContainer
	 */
	public function setDisableAdding($disableAdding = TRUE)
	{
		$this->disableAdding = (bool) $disableAdding;
		return $this;
	}

	/**
	 * @return boolean
	 */
	public function isDisabledAdding()
	{
		return $this->disableAdding;
	}

	/**
	 * @return Collection
	 */
	public function getCollection()
	{
		return $this->collection;
	}

	/**
	 * @param string $name
	 * @return Nette\ComponentModel\IComponent|null
	 */
	protected function createComponent($name): ?Nette\ComponentModel\IComponent
	{
		$this[$name] = $container = $this->toOneContainerFactory->create();

		$this->containerFactory->call($this->getForm(), $container);

		return $container;
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
	 * @return array
	 */
	private function getHttpData()
	{
		$path = explode(self::NAME_SEPARATOR, $this->lookupPath('Nette\Application\UI\Form'));
		$allData = $this->getForm()->getHttpData();
		return Nette\Utils\Arrays::get($allData, $path, NULL);
	}
}
