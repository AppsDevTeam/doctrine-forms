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
	 * @param string|null $errorMessage
	 */
	public function __construct(string $entityFieldName, Closure $containerFactory, ?Closure $entityFactory, ?string $isFilledComponentName, ?string $errorMessage)
	{
		$this->entityFactory = $entityFactory;

		$this->monitor(Presenter::class, function() use ($entityFieldName, $containerFactory, $isFilledComponentName, $errorMessage) {
			$containerFactory->call($this->getForm(), $this);

			/** @var EntityForm $form */
			$form = $this->getForm();

			if (!$form->getEntity()) {
				throw new Exception('Set the entity via "EntityForm::setEntity" method before using toOne or toMany method.');
			}

			$em = $form->getEntityMapper()->getEntityManager();

			$associationMapping = $em->getMetadataFactory()
				->getMetadataFor(get_class($form->getEntity()))
				->getAssociationMapping($entityFieldName);

			$classMetadata = $em->getMetadataFactory()
				->getMetadataFor($associationMapping['targetEntity']);

			$hasFieldControls = false;
			foreach ($this->getControls() as $control) {
				if ($classMetadata->hasField($control->getName())) {
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
				elseif (!in_array($associationMapping['type'], [ClassMetadata::ONE_TO_MANY, ClassMetadata::MANY_TO_MANY])) {
					$mapping = $associationMapping['joinColumns'][0];

					// the field is not nullable
					if (isset($mapping['nullable']) && $mapping['nullable'] === false) {
						if (empty($errorMessage)) {
							throw new Exception('Error message must be set if isFilled component is set and the field is not nullable.');
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
					throw new Exception('Error message must not be set unless the "isFilled" component is set.');
				}
			}
		});
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
