<?php

namespace ADT\DoctrineForms;

interface Entity
{
	public function getId();

	public function get($field);
}
