<?php

namespace ADT\DoctrineForms\Builder;

use Doctrine\ORM\Mapping\ClassMetadata;
use ADT\DoctrineForms\UnexpectedValueException;
use Nette;
use Nette\Forms\Controls;

class ControlFactory
{
	public function create(ClassMetadata $class, array $mapping)
	{
		/** @var Controls\BaseControl|Nette\Forms\IControl $control */

		if (method_exists($this, $method = 'create' . ucFirst($mapping['type']))) {
			$control = $this->{$method}($class, $mapping);

		} else {
			$control = new Controls\TextInput();
		}

		if (!$control instanceof Nette\Forms\IControl) {
			throw new UnexpectedValueException("Form control must implement Nette\\Forms\\IControl, but " . is_object($control) ? get_class($control) : gettype($control) . ' was given');
		}

		if ($control instanceof Controls\BaseControl) {
			$control->caption = $this->defaultControlName($class, $mapping);
		}

		return $control;
	}

	protected function defaultControlName(ClassMetadata $class, array $mapping)
	{
		return 'entity.' . lcFirst($class->getReflectionClass()->getShortName()) . '.' . $mapping['fieldName'];
	}
}
