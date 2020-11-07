<?php

namespace Kdyby\DoctrineForms;

use Kdyby\Doctrine\EntityManager;
use Nette;

class ToOneContainer extends Nette\Forms\Container
{
	protected $isFilledComponent;

	protected $hasGeneratedIsFilledComponent = false;

	public function setIsFilledComponent(Nette\Forms\Controls\BaseControl $isFilledComponent)
	{
		$this->isFilledComponent = $isFilledComponent;
		return $this;
	}

	public function getIsFilledComponent()
	{
		return $this->isFilledComponent;
	}

	public function setHasGeneratedIsFilledComponent()
	{
		$this->hasGeneratedIsFilledComponent = true;
		return $this;
	}
	
	public function hasGeneratedIsFilledComponent()
	{
		return (bool) $this->hasGeneratedIsFilledComponent;
	}

	public static function register($name = 'toOne')
	{
		Nette\Forms\Container::extensionMethod($name, function (Nette\Forms\Container $_this, $name, $containerFactory, $isFilledComponentName, $errorMessage = null) {
			$container = new ToOneContainer;

			$containerFactory->call($_this, $container);

			/** @var EntityManager $em */
			$em = $_this->getParent()->em;

			$mapping = $em->getMetadataFactory()
				->getMetadataFor(get_class($_this->getParent()->getRow()))
				->getAssociationMapping($name)['joinColumns'][0];
			
			if (!isset($container[$isFilledComponentName])) {
				$container->addText($isFilledComponentName)
					->setHtmlAttribute('style', 'display: none');

				// the fiels is not nullable
				if (isset($mapping['nullable']) && $mapping['nullable'] === false) {
					if (empty($errorMessage)) {
						throw new \Exception('The error message must be set if isFilled component is not an existing component and the field is not nullable.');
					}

					$container[$isFilledComponentName]->setRequired($errorMessage);
				}
				else {
					if ($errorMessage) {
						throw new \Exception('The error message must not be set if the field is nullable.');
					}
				}
				
				$container->setHasGeneratedIsFilledComponent(true);
			}
			else {
				if ($errorMessage) {
					throw new \Exception('The error message must not be set if isFilled component is an existing component.');
				}

				// throw exception in case the field is not nullable and the [isFilled] component is not required
				if (
					isset($mapping['nullable']) && $mapping['nullable'] === false
					&&
					!$container[$isFilledComponentName]->isRequired()
				) {
					throw new \Exception('ToOneContainer::isFilled component has to be set as required if the field is not nullable.');
				}				
			}

			$container->setIsFilledComponent($container[$isFilledComponentName]);

			return $_this[$name] = $container;
		});
	}
}
