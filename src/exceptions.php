<?php

namespace ADT\DoctrineForms;

use Doctrine;

interface Exception
{

}

class InvalidStateException extends \RuntimeException implements Exception
{

}

class InvalidArgumentException extends \InvalidArgumentException implements Exception
{

}

class NotImplementedException extends \LogicException implements Exception
{

}

class UnexpectedValueException extends \UnexpectedValueException implements Exception
{

}
