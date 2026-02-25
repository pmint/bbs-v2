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
- Composer

## Setup

```bash
cd bbs-v2
composer install
```

### Local config override

Default config is `config/app.php`.  
For local-only overrides, copy and edit:

```bash
copy config\\app.local.php.example config\\app.local.php
```

`config/app.local.php` is ignored by git.

## Run (local)

```bash
cd bbs-v2
php -S localhost:8000 -t public
```

Open:

- `http://localhost:8000/`
- `http://localhost:8000/posts`
- `http://localhost:8000/posts/create`

## Tests

```bash
cd bbs-v2
composer test
```

## Runtime data

- DB file: `storage/data/bbs.sqlite`
- This file is ignored by git to avoid accidental overwrite on deploy.

## Backup (minimal manual flow)

Keep this note even in preview environments:

```bash
copy storage\\data\\bbs.sqlite storage\\data\\bbs.sqlite.bak
```

Before deployments that replace files, ensure `storage/data/bbs.sqlite` is preserved.

## CI

GitHub Actions workflow is included:

- `.github/workflows/phpunit.yml`

On push/PR, it runs:

1. `composer validate --strict`
2. `composer install --prefer-dist`
3. `composer test`

## vNext docs

- `docs/content-style-guide.md`: 文言ガイド
- `docs/terminology-glossary.md`: 用語辞書
- `docs/feature-priority.md`: 機能優先度表
