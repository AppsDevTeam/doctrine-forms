<?php

declare(strict_types=1);

namespace ADT\DoctrineForms\Tests\Controls;

use ADT\DoctrineForms\Form;
use ADT\DoctrineForms\Tests\EntityManagerFactory;
use ADT\DoctrineForms\Tests\Fixtures\Company;
use ADT\DoctrineForms\Tests\Fixtures\CompanyType;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Mapovani skalarnich sloupcu entity na prvky formulare a zpet (Controls\TextControl).
 */
final class TextControlTest extends TestCase
{
	/**
	 * Vyber Ne/Ano nad bool sloupcem. ChoiceControl hleda klic pres `(string) $value`
	 * a `(string) false` je prazdny retezec - bez prevodu by `false` skoncilo vyjimkou
	 * "out of allowed set" misto vybrane polozky 0.
	 *
	 * @return iterable<string, array{?bool, ?int}>
	 */
	public static function boolToChoice(): iterable
	{
		yield 'false je Ne' => [false, 0];
		yield 'true je Ano' => [true, 1];
		// "nevim" se od "ne" musi dat odlisit, jinak by se nevyplnene predvyplnilo na Ne
		yield 'null zustane na promptu' => [null, null];
	}

	#[DataProvider('boolToChoice')]
	public function testBoolIsLoadedIntoSelect(?bool $isVatPayer, ?int $expected): void
	{
		$form = $this->createForm((new Company())->setIsVatPayer($isVatPayer));
		$form->addSelect('isVatPayer', null, [0 => 'Ne', 1 => 'Ano'])->setPrompt('---');

		$form->mapToForm();

		self::assertSame($expected, $form['isVatPayer']->getValue());
	}

	#[DataProvider('boolToChoice')]
	public function testBoolIsLoadedIntoRadioList(?bool $isVatPayer, ?int $expected): void
	{
		$form = $this->createForm((new Company())->setIsVatPayer($isVatPayer));
		$form->addRadioList('isVatPayer', null, [0 => 'Ne', 1 => 'Ano']);

		$form->mapToForm();

		self::assertSame($expected, $form['isVatPayer']->getValue());
	}

	/**
	 * Vyber vraci klic 0/1, do entity patri bool.
	 *
	 * @return iterable<string, array{?int, ?bool}>
	 */
	public static function choiceToBool(): iterable
	{
		yield 'Ne je false' => [0, false];
		yield 'Ano je true' => [1, true];
		yield 'prompt je null' => [null, null];
	}

	#[DataProvider('choiceToBool')]
	public function testSelectIsSavedAsBool(?int $choice, ?bool $expected): void
	{
		$company = (new Company())->setIsVatPayer(true);
		$form = $this->createForm($company);
		$form->addSelect('isVatPayer', null, [0 => 'Ne', 1 => 'Ano'])->setPrompt('---')->setValue($choice);

		$form->mapToEntity();

		self::assertSame($expected, $company->getIsVatPayer());
	}

	/** Zaskrtavatko bere i vraci bool samo - prevod pro vyber se ho nesmi dotknout. */
	public function testCheckboxStillMapsBool(): void
	{
		$company = (new Company())->setIsActive(true);
		$form = $this->createForm($company);
		$form->addCheckbox('isActive');

		$form->mapToForm();
		self::assertTrue($form['isActive']->getValue());

		$form['isActive']->setValue(false);
		$form->mapToEntity();
		self::assertFalse($company->getIsActive());
	}

	public function testTextIsMappedUnchanged(): void
	{
		$company = (new Company())->setName('ACME');
		$form = $this->createForm($company);
		$form->addText('name');

		$form->mapToForm();
		self::assertSame('ACME', $form['name']->getValue());

		$form['name']->setValue('Jina');
		$form->mapToEntity();
		self::assertSame('Jina', $company->getName());
	}

	/** Prazdny text v nullable sloupci se uklada jako null, ne jako prazdny retezec. */
	public function testEmptyTextInNullableColumnIsSavedAsNull(): void
	{
		$company = (new Company())->setName('ACME');
		$form = $this->createForm($company);
		$form->addText('name')->setValue('');

		$form->mapToEntity();

		self::assertNull($company->getName());
	}

	public function testEnumIsMappedThroughItsValue(): void
	{
		$company = (new Company())->setType(CompanyType::Branch);
		$form = $this->createForm($company);
		$form->addSelect('type', null, ['billing' => 'Fakturacni', 'branch' => 'Pobocka']);

		$form->mapToForm();
		self::assertSame('branch', $form['type']->getValue());

		$form['type']->setValue('billing');
		$form->mapToEntity();
		self::assertSame(CompanyType::Billing, $company->getType());
	}

	private function createForm(Company $company): Form
	{
		$form = new Form();
		$form->setEntityManager(EntityManagerFactory::create());
		$form->setEntity($company);

		return $form;
	}
}
