<?php

namespace ADT\DoctrineForms;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping\ClassMetadata;
use Nette;
use Nette\Application\UI;

class ToManyContainer extends Nette\Forms\Container
{
	const NEW_PREFIX = '_new_';

	/**
	 * @var Collection
	 */
	private $collection;

	/**
	 * @var object
	 */
	private $parentEntity;

	/**
	 * @var Nette\Utils\Callback
	 */
	private $containerFactory;

	/**
	 * @var callable
	 */
	private $entityFactory;

	/**
	 * @var string
	 */
	private $containerClass = 'Nette\Forms\Container';

	/**
	 * @var bool
	 */
	private $allowRemove = FALSE;

	/**
	 * @var bool
	 */
	private $disableAdding = FALSE;

	public function __construct($containerFactory, $entityFactory)
	{
		$this->containerFactory = $containerFactory;
		$this->entityFactory = $entityFactory;
		$this->collection = new ArrayCollection();
	}

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

	public function createEntity(ClassMetadata $relationMeta)
	{
		if (! $this->entityFactory) {
			return $relationMeta->newInstance();
		}

		return call_user_func($this->entityFactory, $relationMeta);
	}

	public function bindCollection($parent, Collection $collection)
	{
		if (!is_object($parent)) {
			throw new InvalidArgumentException('Expected entity, but ' . gettype($parent) . ' given.');
		}

		$this->parentEntity = $parent;
		$this->collection = $collection;
	}

	/**
	 * @param string $containerClass
	 * @throws \ADT\DoctrineForms\InvalidArgumentException
	 * @return ToManyContainer
	 */
	public function setContainerClass($containerClass)
	{
		if (!is_subclass_of($containerClass, 'Nette\Forms\Container')) {
			throw new InvalidArgumentException('Expected descendant of Nette\Forms\Container, but ' . $containerClass . ' given.');
		}

		$this->containerClass = $containerClass;
		return $this;
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
	 * @return \Doctrine\Common\Collections\Collection
	 */
	public function getCollection()
	{
		return $this->collection;
	}

	protected function createComponent($name): ?Nette\ComponentModel\IComponent
	{
		$class = $this->containerClass;
		$this[$name] = $container = new $class();
		$this->containerFactory->call($this->parent, $container);

		return $container;
	}

	/**
	 * @param \Nette\ComponentModel\Container $obj
	 */
	protected function attached($obj): void
	{
		parent::attached($obj);

		if (!$obj instanceof UI\Presenter) {
			return;
		}

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

	public static function register($name = 'toMany')
	{
		Nette\Forms\Container::extensionMethod($name, function (Nette\Forms\Container $_this, $name, $containerFactory = NULL, $entityFactory = NULL) {
			$container = new ToManyContainer($containerFactory, $entityFactory);

			return $_this[$name] = $container;
		});
	}
}
