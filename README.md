# bbs-v2

Minimal MVC rewrite scaffold for a BBS.

## Structure

- `public/index.php`: front controller
- `app/Controllers`: HTTP layer
- `app/Services`: use-case layer
- `app/Models`: domain model
- `app/Repositories`: repository contracts
- `app/Infrastructure/Persistence`: storage implementation (SQLite)
- `app/Views`: PHP view templates
- `storage/data`: runtime data (SQLite file)

## Requirements

- PHP 8.1+
- PDO SQLite extension

## Run (local)

```bash
cd bbs-v2
php -S localhost:8000 -t public
```

Open:

- `http://localhost:8000/`
- `http://localhost:8000/posts`
- `http://localhost:8000/posts/create`

## Implemented in this scaffold

- MVC + Service + Repository split
- SQLite persistence
- CSRF protection for write operations
- Post create / update / delete
- PHPUnit test skeleton

## Run tests

```bash
cd bbs-v2
composer install
composer test
```
