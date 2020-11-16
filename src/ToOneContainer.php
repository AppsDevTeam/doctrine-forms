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
	 * @param $message
	 * @throws Exception
	 */
	public function validate(?array $controls = NULL): void
	{
		if (
			$this->isRequired()
			&&
			$this->isEmpty()
		) {
			$this->addText(static::ERROR_CONTROL_NAME)
				->addError($this->getRequiredMessage());
		}
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
}
