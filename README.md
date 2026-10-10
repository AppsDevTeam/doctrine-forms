Doctrine Forms
======

## Testy

```bash
composer install
composer test
```

Testy jsou v `tests/` (PHPUnit). Mapování potřebuje jen metadata entit, takže běží nad
EntityManagerem se SQLite v paměti (`tests/EntityManagerFactory.php`) a testovacími
entitami v `tests/Fixtures` — žádná databáze ani projekt k tomu nejsou potřeba.
Vyžaduje rozšíření `pdo_sqlite`.
