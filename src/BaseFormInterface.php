<?php

namespace ADT\DoctrineForms;

interface BaseFormInterface
{
	public function setOnAfterInitForm(callable $onAfterInitForm): static;
	public function setOnAfterMapToForm(callable $onAfterMapToForm): static;
	public function setOnAfterMapToEntity(callable $onAfterMapToEntity): static;
	public function initOnAfterMapToForm(Form $form): void;
	public function setEntity(object|callable|null $entity): static;
}