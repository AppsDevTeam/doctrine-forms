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
	 * @var array
	 */
	private array $options = [];

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

	/**
	 * @param $key
	 * @param $value
	 * @return $this
	 */
	public function setOption($key, $value)
	{
		if ($value === null) {
			unset($this->options[$key]);
		} else {
			$this->options[$key] = $value;
		}
		return $this;
	}

	/**
	 * @param $key
	 * @param null $default
	 * @return mixed|null
	 */
	public function getOption($key, $default = null)
	{
		return $this->options[$key] ?? $default;
	}

	public function getOptions(): array
	{
		return $this->options;
	}

	public function addError($message, bool $translate = true): void
	{
		$this->addText(static::ERROR_CONTROL_NAME)
			->addError($message, $translate);
	}

	public static function register()
	{
		Nette\Forms\Container::extensionMethod('toOne', function (Nette\Forms\Container $_this, string $name, Closure $containerFactory, ?Closure $entityFactory = null, ?string $isFilledComponentName = null, ?string $isRequiredMessage = null) {
			return $_this[$name] = (new ToOneContainerFactory($name, $containerFactory, $entityFactory, $isFilledComponentName))
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
