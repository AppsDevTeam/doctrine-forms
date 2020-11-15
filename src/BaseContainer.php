<?php

namespace ADT\DoctrineForms;

use Nette;

abstract class BaseContainer extends Nette\Forms\Container
{
	public static function register()
	{
		Nette\Forms\Container::extensionMethod('toOne', function (Nette\Forms\Container $_this, $name, $containerFactory, $entityFactory = null, $isFilledComponentName = null, $errorMessage = null) {
			return $_this[$name] = (new ToOneContainerFactory($name, $containerFactory, $entityFactory, $isFilledComponentName, $errorMessage))->create();
		});

		Nette\Forms\Container::extensionMethod('toMany', function (Nette\Forms\Container $_this, $name, $containerFactory, $entityFactory = null, $isFilledComponentName = null, $errorMessage = null) {
			return $_this[$name] = (new ToManyContainer)
				->setToOneContainerFactory(new ToOneContainerFactory($name, $containerFactory, $entityFactory, $isFilledComponentName, $errorMessage));
		});
	}
}
