<?php

declare(strict_types=1);

namespace ADT\DoctrineForms\Tests;

use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\Driver\AttributeDriver;

/**
 * EntityManager nad SQLite v pameti s entitami z tests/Fixtures.
 *
 * Mapovani formulare potrebuje jen metadata entit, do databaze nesaha - pripojeni je tu
 * proto, ze se bez nej EntityManager vytvorit neda, a SQLite v pameti nic nepotrebuje.
 */
final class EntityManagerFactory
{
	public static function create(): EntityManagerInterface
	{
		$config = new Configuration();
		$config->setMetadataDriverImpl(new AttributeDriver([__DIR__ . '/Fixtures'], true));

		// Doctrine 3.4+ na PHP 8.4 umi lazy objekty nativne, starsi verze potrebuji proxy.
		if (PHP_VERSION_ID >= 80400 && method_exists($config, 'enableNativeLazyObjects')) {
			$config->enableNativeLazyObjects(true);
		} else {
			$config->setProxyDir(sys_get_temp_dir() . '/doctrine-forms-tests');
			$config->setProxyNamespace('ADT\DoctrineForms\Tests\Proxies');
		}

		return new EntityManager(
			DriverManager::getConnection(['driver' => 'pdo_sqlite', 'memory' => true], $config),
			$config,
		);
	}
}
