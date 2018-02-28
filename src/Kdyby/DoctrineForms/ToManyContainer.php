<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.txt that was distributed with this source code.
 */

namespace Kdyby\DoctrineForms;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Kdyby;
use Nette;
use Nette\Application\UI;



/**
 * @author Filip Procházka <filip@prochazka.su>
 */
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



	public function __construct($containerFactory)
	{
		parent::__construct();

		$this->containerFactory = $containerFactory;
		$this->collection = new ArrayCollection();
	}



	protected function validateParent(Nette\ComponentModel\IContainer $parent)
	{
		parent::validateParent($parent);
		$this->monitor('Nette\Application\UI\Presenter');
	}


	/**
	 * Create new container
	 *
	 * @param string|int $name
	 *
	 * @throws \Nette\InvalidArgumentException
	 * @return \Nette\Forms\Container
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


	public function bindCollection($parent, Collection $collection)
	{
		if (!is_object($parent)) {
			throw new Kdyby\DoctrineForms\InvalidArgumentException('Expected entity, but ' . gettype($parent) . ' given.');
		}

		$this->parentEntity = $parent;
		$this->collection = $collection;
	}



	/**
	 * @param string $containerClass
	 * @throws \Kdyby\DoctrineForms\InvalidArgumentException
	 * @return ToManyContainer
	 */
	public function setContainerClass($containerClass)
	{
		if (!is_subclass_of($containerClass, 'Nette\Forms\Container')) {
			throw new Kdyby\DoctrineForms\InvalidArgumentException('Expected descendant of Nette\Forms\Container, but ' . $containerClass . ' given.');
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



	protected function createComponent($name)
	{
		$class = $this->containerClass;
		$this[$name] = $container = new $class();
		Nette\Utils\Callback::invoke($this->containerFactory, $container, $this->parent);

		return $container;
	}



	/**
	 * @param \Nette\ComponentModel\Container $obj
	 */
	protected function attached($obj)
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
		Nette\Forms\Container::extensionMethod($name, function (Nette\Forms\Container $_this, $name, $containerFactory = NULL) {
			$container = new ToManyContainer($containerFactory);

			return $_this[$name] = $container;
		});
	}

}
