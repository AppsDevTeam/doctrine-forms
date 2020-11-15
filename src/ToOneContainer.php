<?php

namespace ADT\DoctrineForms;

use Closure;
use Doctrine\ORM\Mapping\ClassMetadata;
use Exception;
use Nette;
use Nette\Application\UI\Presenter;

class ToOneContainer extends BaseContainer
{
	/**
	 * @var Closure|null
	 */
	protected ?Closure $entityFactory;

	/**
	 * @var Nette\Forms\Controls\BaseControl
	 */
	protected Nette\Forms\Controls\BaseControl $isFilledComponent;

	/**
	 * ToOneContainer constructor.
	 * @param string $entityFieldName
	 * @param Closure $containerFactory
	 * @param Closure|null $entityFactory
	 * @param string|null $isFilledComponentName
	 */
	public function __construct(string $entityFieldName, Closure $containerFactory, ?Closure $entityFactory, ?string $isFilledComponentName)
	{
		$this->entityFactory = $entityFactory;

		$this->monitor(Presenter::class, function() use ($entityFieldName, $containerFactory, $isFilledComponentName) {
			$containerFactory->call($this->getForm(), $this);

			if ($isFilledComponentName) {
				$this->setIsFilledComponent($this->addText($isFilledComponentName)->setHtmlAttribute('style', 'display: none'));
			}
		});
	}

	/**
	 * @param Nette\Forms\Controls\BaseControl $isFilledComponent
	 * @return $this
	 */
	public function setIsFilledComponent(Nette\Forms\Controls\BaseControl $isFilledComponent)
	{
		$this->isFilledComponent = $isFilledComponent;
		return $this;
	}

	/**
	 * @return Nette\Forms\Controls\BaseControl
	 */
	public function getIsFilledComponent()
	{
		return $this->isFilledComponent;
	}

	/**
	 * @param bool $excludeIsFilledComponent
	 * @return bool
	 */
	public function isEmpty($excludeIsFilledComponent = false)
	{
		$values = $this->getValues('array');
		if ($excludeIsFilledComponent) {
			unset($values[$this->getIsFilledComponent()->getName()]);
		}

		return !array_filter($values);
	}

	/**
	 * @param ClassMetadata $relationMeta
	 * @return mixed|object
	 */
	public function createEntity(ClassMetadata $relationMeta)
	{
		if (! $this->entityFactory) {
			return $relationMeta->newInstance();
		}

		return call_user_func($this->entityFactory);
	}

	/**
	 * @param $message
	 * @throws Exception
	 */
	public function addError($message)
	{
		if ($this->getIsFilledComponent()) {
			$this->getIsFilledComponent()->addError($message);
		}
		else {
			// we set the error message to the first control,
			// that is not hidden
			/** @var Nette\Forms\IControl $_control */
			foreach ($this->getControls() as $_control) {
				if ($_control instanceof Nette\Forms\Controls\HiddenField) {
					continue;
				}

				$_control->addError($message);
			}

			// we throw the exception
			// if we failed to set the error message
			throw new Exception('The "isFilledComponentName" parameter has to be specified if all container controls are of type "hidden".');
		}
	}
}