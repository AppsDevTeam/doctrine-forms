<?php

namespace ADT\DoctrineForms;

use ADT\DoctrineComponents\Entities\Entity;

interface BaseFormInterface
{
	public function setOnAfterInitForm(callable $onAfterInitForm): static;
	public function setOnAfterMapToForm(callable $onAfterMapToForm): static;
	public function setOnAfterMapToEntity(callable $onAfterMapToEntity): static;
	public function initOnAfterMapToForm(Form $form): void;
	public function setEntity(Entity|callable|null $entity): static;
}