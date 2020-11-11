<?php

namespace Kdyby\DoctrineForms;

use Kdyby\Doctrine\EntityManager;
use Nette;

class ToOneContainer extends Nette\Forms\Container
{
	protected $entityFactory;
	protected Nette\Forms\Controls\BaseControl $isFilledComponent;

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

	public function isEmpty($excludeIsFilledComponent = false)
	{
		$values = $this->getValues('array');
		if ($excludeIsFilledComponent) {
			unset($values[$this->getIsFilledComponent()->getName()]);
		}

		return !array_filter($values);
	}

	public function createEntity(\Doctrine\ORM\Mapping\ClassMetadata $relationMeta)
	{
		if (! $this->entityFactory) {
			return $relationMeta->newInstance();
		}

		return $this->entityFactory->call($relationMeta);
	}

	public static function register($name = 'toOne')
	{
		Nette\Forms\Container::extensionMethod($name, function (Nette\Forms\Container $_this, $name, $containerFactory, $entityFactory = null, $isFilledComponentName = null, $errorMessage = null) {
			$container = new ToOneContainer($entityFactory);

			$containerFactory->call($_this, $container);

			/** @var EntityManager $em */
			$em = $_this->getForm()->getEntityMapper()->getEntityManager();

			$targetEntity = $em->getMetadataFactory()
				->getMetadataFor(get_class($_this->getParent()->getRow()))
				->getAssociationMapping($name)['targetEntity'];

			$mapping = $em->getMetadataFactory()
				->getMetadataFor($targetEntity);

			$hasFieldControls = false;
			foreach ($container->getControls() as $control) {
				if ($mapping->hasField($control->getName())) {
					$hasFieldControls = true;
				}
			}

			if ($isFilledComponentName) {
				if ($hasFieldControls) {
					throw new \Exception('Do not specify an "isFilled" component if any container control is an entity field.');
				}

				if (isset($container[$isFilledComponentName])) {
					throw new \Exception('Component ' . $isFilledComponentName . ' already exists.');
				}

				$isFilledComponent = $container->addText($isFilledComponentName)
					->setHtmlAttribute('style', 'display: none');

				if ($errorMessage) {
					$isFilledComponent->setRequired($errorMessage);
				}
				else {
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
			else {
				if (!$hasFieldControls) {
					throw new \Exception('You have to specify an "isFilled" component if none of the containr controls is an entity field.');
				}

				if ($errorMessage) {
					throw new \Exception('The error message must not be set unless the "isFilled" component is set.');
				}
			}

			return $_this[$name] = $container;
		});
	}
}
