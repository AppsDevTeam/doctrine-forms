<?php

namespace ADT\DoctrineForms;

use Doctrine\ORM\Mapping\ClassMetadata;
use Nette\ComponentModel\Component;

interface IComponentMapper
{
	const FIELD_NAME = 'field.name';
	const ITEMS_TITLE = 'items.title';
	const ITEMS_FILTER = 'items.filter';
	const ITEMS_ORDER = 'items.order';

	/**
	 * @param ClassMetadata $meta
	 * @param Component $component
	 * @param $entity
	 * @return bool
	 */
	function load(ClassMetadata $meta, Component $component, $entity): bool;

	/**
	 * @param ClassMetadata $meta
	 * @param Component $component
	 * @param $entity
	 * @return bool
	 */
	function save(ClassMetadata $meta, Component $component, $entity): bool;
}
