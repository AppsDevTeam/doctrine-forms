<?php

namespace ADT\DoctrineForms;

use Closure;
use Nette;

abstract class BaseContainer extends Nette\Forms\Container
{
	// because there is no "addError" method in Container class
	// we have to create an IControl instance and call "addError" on it
	// the control must not be an instance of "HiddenField"
	// otherwise the error will be added to the form instead of the container
	const ERROR_CONTROL_NAME = '_containerError_';

	/**
	 * @var string
	 */
	private ?string $requiredMessage = null;

	public function setRequired(?string $message)
	{
		$this->requiredMessage = $message;
		return $this;
	}

	protected function getRequiredMessage()
	{
		return $this->requiredMessage;
	}

	protected function isRequired()
	{
		return (bool) $this->getRequiredMessage();
	}

	public static function register()
	{
		Nette\Forms\Container::extensionMethod('toOne', function (Nette\Forms\Container $_this, string $name, Closure $containerFactory, ?Closure $entityFactory = null, ?string $isFilledComponentName = null, ?string $isRequiredMessage = null) {
			return $_this[$name] = (new ToOneContainerFactory($name, $containerFactory, $entityFactory, $isFilledComponentName, $isRequiredMessage))
				->create()
				->setRequired($isRequiredMessage);
		});

		Nette\Forms\Container::extensionMethod('toMany', function (Nette\Forms\Container $_this, string $name, Closure $containerFactory, ?Closure $entityFactory = null, ?string $isFilledComponentName = null, ?string $isRequiredMessage = null) {
			return $_this[$name] = (new ToManyContainer)
				->setToOneContainerFactory(new ToOneContainerFactory($name, $containerFactory, $entityFactory, $isFilledComponentName))
				->setRequired($isRequiredMessage);
		});
	}
}
