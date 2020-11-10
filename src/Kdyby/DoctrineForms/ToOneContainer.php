<?php

namespace Kdyby\DoctrineForms;

use Kdyby\Doctrine\EntityManager;
use Nette;

class ToOneContainer extends Nette\Forms\Container
{
	protected $entityFactory;
	protected $isFilledComponent;

	public function __construct($entityFactory)
	{
		$this->entityFactory = $entityFactory;
	}
	
	public function getEntityFactory()
	{
		return $this->entityFactory;
	}

	public function setIsFilledComponent(Nette\Forms\Controls\BaseControl $isFilledComponent)
	{
		$this->isFilledComponent = $isFilledComponent;
		return $this;
	}

	public function getIsFilledComponent()
	{
		return $this->isFilledComponent;
	}

	public function isEmpty()
	{
		return !array_filter($this->getValues('array'));
	}

	public function createEntity(\Doctrine\ORM\Mapping\ClassMetadata $relationMeta)
	{
		if (! $this->entityFactory) {
			return $relationMeta->newInstance();
		}

		$entityClass = $relationMeta->getName();
		return $this->entityFactory->call($this, new $entityClass);
	}

	public static function register($name = 'toOne')
	{
		Nette\Forms\Container::extensionMethod($name, function (Nette\Forms\Container $_this, $name, $containerFactory, $entityFactory = null, $isFilledComponentName = null, $errorMessage = null) {
			$container = new ToOneContainer($entityFactory);

			$containerFactory->call($_this, $container);

			if ($isFilledComponentName) {
				if (isset($container[$isFilledComponentName])) {
					throw new \Exception('Component ' . $isFilledComponentName . ' already exists.');
				}

				$isFilledComponent = $container->addText($isFilledComponentName)
					->setHtmlAttribute('style', 'display: none');

				if ($errorMessage) {
					$isFilledComponent->setRequired($errorMessage);
				}
				else {
					/** @var EntityManager $em */
					$em = $_this->getForm()->getEntityMapper()->getEntityManager();

					$mapping = $em->getMetadataFactory()
						->getMetadataFor(get_class($_this->getParent()->getRow()))
						->getAssociationMapping($name)['joinColumns'][0];

					// the field is not nullable
					if (isset($mapping['nullable']) && $mapping['nullable'] === false) {
						if (empty($errorMessage)) {
							throw new \Exception('The error message must be set if isFilled component is set and the field is not nullable.');
						}
					}
				}

				$container->setIsFilledComponent($isFilledComponent);
			}

			return $_this[$name] = $container;
		});
	}
}
