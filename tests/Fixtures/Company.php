<?php

declare(strict_types=1);

namespace ADT\DoctrineForms\Tests\Fixtures;

use ADT\DoctrineComponents\Entities\Entity;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class Company implements Entity
{
	#[ORM\Id]
	#[ORM\Column]
	#[ORM\GeneratedValue]
	protected ?int $id = null;

	#[ORM\Column(nullable: true)]
	protected ?string $name = null;

	/** Nullable bool - "nevim" je jina hodnota nez "ne". */
	#[ORM\Column(nullable: true)]
	protected ?bool $isVatPayer = null;

	#[ORM\Column]
	protected bool $isActive = false;

	#[ORM\Column(nullable: true, enumType: CompanyType::class)]
	protected ?CompanyType $type = null;

	public function getId(): ?int
	{
		return $this->id;
	}

	public function isNew(): bool
	{
		return $this->id === null;
	}

	public function getName(): ?string
	{
		return $this->name;
	}

	public function setName(?string $name): static
	{
		$this->name = $name;
		return $this;
	}

	public function getIsVatPayer(): ?bool
	{
		return $this->isVatPayer;
	}

	public function setIsVatPayer(?bool $isVatPayer): static
	{
		$this->isVatPayer = $isVatPayer;
		return $this;
	}

	public function getIsActive(): bool
	{
		return $this->isActive;
	}

	public function setIsActive(bool $isActive): static
	{
		$this->isActive = $isActive;
		return $this;
	}

	public function getType(): ?CompanyType
	{
		return $this->type;
	}

	public function setType(?CompanyType $type): static
	{
		$this->type = $type;
		return $this;
	}
}
