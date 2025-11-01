<?php

namespace ADT\DoctrineForms;

use ADT\DoctrineComponents\Entities\Entity;
use Doctrine\ORM\Mapping\ClassMetadata;
use Nette\ComponentModel\Component;

interface IComponentMapper
{
	const FIELD_NAME = 'field.name';
	const ITEMS_TITLE = 'items.title';
	const ITEMS_FILTER = 'items.filter';
	const ITEMS_ORDER = 'items.order';

	function load(ClassMetadata $meta, Component $component, Entity $entity): bool;

	function save(ClassMetadata $meta, Component $component, Entity $entity): bool;
}
