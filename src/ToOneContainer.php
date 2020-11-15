<?php

namespace ADT\DoctrineForms;

use Closure;
use Doctrine\ORM\Mapping\ClassMetadata;
use Exception;
use Nette;
use Nette\Application\UI\Presenter;

class ToOneContainer extends BaseContainer
{
	protected ?Closure $entityFactory;
	protected Nette\Forms\Controls\BaseControl $isFilledComponent;

	/**
	 * ToOneContainer constructor.
	 * @param $containerFactory
	 * @param null $entityFactory
	 * @param null $isFilledComponentName
	 * @param null $errorMessage
	 * @throws \Doctrine\Persistence\Mapping\MappingException
	 * @throws \ReflectionException
	 */
	public function __construct($entityFieldName, $containerFactory, $entityFactory = null, $isFilledComponentName = null, $errorMessage = null)
	{
		$this->entityFactory = $entityFactory;

		$this->monitor(Presenter::class, function() {
			$containerFactory->call($this->getForm(), $this);

			/** @var EntityForm $form */
			$form = $this->getForm();

			if (!$form->getEntity()) {
				throw new Exception('Set the entity via "EntityForm::setEntity" method before using toOne or toMany method.');
			}

			$em = $form->getEntityMapper()->getEntityManager();

			$targetEntity = $em->getMetadataFactory()
				->getMetadataFor(get_class($form->getEntity()))
				->getAssociationMapping($entityFieldName)['targetEntity'];

			$mapping = $em->getMetadataFactory()
				->getMetadataFor($targetEntity);

			$hasFieldControls = false;
			foreach ($this->getControls() as $control) {
				if ($mapping->hasField($control->getName())) {
					$hasFieldControls = true;
				}
			}

			if ($isFilledComponentName) {
				if ($hasFieldControls) {
					throw new Exception('Do not specify an "isFilled" component if any container control is an entity field.');
				}

				if (isset($container[$isFilledComponentName])) {
					throw new Exception('Component ' . $isFilledComponentName . ' already exists.');
				}

				$isFilledComponent = $this->addText($isFilledComponentName)
					->setHtmlAttribute('style', 'display: none');

				if ($errorMessage) {
					$isFilledComponent->setRequired($errorMessage);
				}
				else {
					$mapping = $em->getMetadataFactory()
						->getMetadataFor(get_class($form->getEntity()))
						->getAssociationMapping($entityFieldName)['joinColumns'][0];

					// the field is not nullable
					if (isset($mapping['nullable']) && $mapping['nullable'] === false) {
						if (empty($errorMessage)) {
							throw new Exception('The error message must be set if isFilled component is set and the field is not nullable.');
						}
					}
				}

				$this->setIsFilledComponent($isFilledComponent);
			}
			else {
				if (!$hasFieldControls) {
					throw new Exception('You have to specify an "isFilled" component if none of the containr controls is an entity field.');
				}

				if ($errorMessage) {
					throw new Exception('The error message must not be set unless the "isFilled" component is set.');
				}
			}
		});
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

	public function createEntity(ClassMetadata $relationMeta)
	{
		if (! $this->entityFactory) {
			return $relationMeta->newInstance();
		}

		return call_user_func($this->entityFactory);
	}
}
