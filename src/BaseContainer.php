<?php

namespace ADT\DoctrineForms;

use Closure;
use Nette;

abstract class BaseContainer extends Nette\Forms\Container
{
	public static function register()
	{
		Nette\Forms\Container::extensionMethod('toOne', function (Nette\Forms\Container $_this, string $name, Closure $containerFactory, ?Closure $entityFactory, ?string $isFilledComponentName, ?string $isRequiredMessage) {
			$container = (new ToOneContainerFactory($name, $containerFactory, $entityFactory, $isFilledComponentName))->create();

			if ($isRequiredMessage) {
				$_this->onValidate[] = function () use ($container, $isRequiredMessage) {
					$container->addError($isRequiredMessage);
				};
			}

			return $_this[$name] = $container;
		});

		Nette\Forms\Container::extensionMethod('toMany', function (Nette\Forms\Container $_this, string $name, Closure $containerFactory, ?Closure $entityFactory, ?string $isFilledComponentName, ?string $isRequiredMessage) {
			$container = (new ToManyContainer)
				->setToOneContainerFactory(new ToOneContainerFactory($name, $containerFactory, $entityFactory, $isFilledComponentName))
				->setRequired($isRequiredMessage);

			return $_this[$name] = $container;
		});
	}
}
