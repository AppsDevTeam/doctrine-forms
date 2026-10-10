<?php

declare(strict_types=1);

namespace ADT\DoctrineForms\Tests\Fixtures;

enum CompanyType: string
{
	case Billing = 'billing';
	case Branch = 'branch';
}
