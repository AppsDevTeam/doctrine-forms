<?php

namespace ADT\DoctrineForms\DI;

use Nette\Configurator;
use Nette\DI\Compiler;
use Nette\DI\CompilerExtension;

class FormsExtension extends CompilerExtension
{
	public function loadConfiguration()
	{
		$builder = $this->getContainerBuilder();

		$builder->addDefinition($this->prefix('entityFormMapper'))
			->setFactory('ADT\DoctrineForms\EntityFormMapper');
	}

	public static function register(Configurator $configurator)
	{
		$configurator->onCompile[] = function ($config, Compiler $compiler) {
			$compiler->addExtension('doctrineForms', new FormsExtension());
		};
	}
}