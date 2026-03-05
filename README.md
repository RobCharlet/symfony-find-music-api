# Find Music API

A Symfony 8 REST API for managing music collections, built with DDD/CQRS architecture across two bounded contexts: **Collection** (albums, external references) and **User** (authentication, user management).

---

## Tech Stack

| Layer | Technology |
|---|---|
| Runtime | PHP 8.4+ |
| Framework | Symfony 8 |
| Dependencies | Composer |
| ORM | Doctrine ORM + Doctrine Migrations |
| Auth | LexikJWTAuthenticationBundle (JWT TTL: 86400s / 24h) |
| API Docs | NelmioApiDocBundle — `GET /api/doc.json` |
| Testing | PHPUnit 12 |
| Architecture | DDD + CQRS with Symfony Messenger |

---

## Architecture

The project follows a strict layered DDD architecture:

| Layer | Role |
|---|---|
| **Domain** | Entities, value objects, domain services, repository interfaces. No Symfony or Doctrine dependency. |
| **App** | Orchestrates use cases via Commands/Queries and their handlers. |
| **Infra** | Doctrine repositories, security adapters, external integrations. |
| **UI** | HTTP controllers and event listeners. Thin layer: parse input, call use case, map output. |

Key principles:
- Messenger buses for all CQRS flows (command bus / query bus).
- Input validation at the boundary (command/query creation); transactions managed in App/Infra, never in controllers.
- Predictable JSON error shapes across all endpoints (400 / 403 / 422 / 500).
- Cross-context references by ID/value object only — no rich object graphs across contexts.

---

## Directory Structure

```
src/
├── Collection/              # Collection bounded context
│   ├── App/
│   │   ├── Command*/        # Write use cases
│   │   └── Query*/          # Read use cases
│   ├── Domain/              # Entities, value objects, interfaces
│   ├── Infra/               # Doctrine adapters, repositories
│   └── UI/
│       ├── Controller/      # HTTP controllers
│       └── EventListener/   # Context-level exception listeners
├── User/                    # User bounded context (same structure)
└── Shared/                  # Shared cross-context technical code

config/                      # Framework and package configuration
migrations/                  # Doctrine migrations
tests/                       # Automated tests
```

---

## Authentication

- **Stateless JWT** via LexikJWTAuthenticationBundle.
- Login: `POST /api/login_check` with `{ "email": "…", "password": "…" }`.
- Two roles: `ROLE_USER` (default on self-registration), `ROLE_ADMIN`.

**Public endpoints** (no token required):
- `POST /api/login_check`
- `POST /api/register`
- `GET /api/doc.json`
- `GET /api/health`

All other `/api/*` routes require a valid JWT (`IS_AUTHENTICATED_FULLY`).

**Registration vs admin creation:**
- `POST /api/register` — public, assigns `ROLE_USER` automatically.
- `POST /api/users` — requires `ROLE_ADMIN`, accepts custom roles.

---

## API Endpoints

### Shared

| Method | Path | Auth | Description |
|---|---|---|---|
| `GET` | `/api/health` | Public | Service healthcheck |

### User Context

| Method | Path | Auth | Description |
|---|---|---|---|
| `POST` | `/api/login_check` | Public | JWT login |
| `POST` | `/api/register` | Public | Self-registration |
| `POST` | `/api/users` | `ROLE_ADMIN` | Admin user creation |
| `GET` | `/api/users/me` | Authenticated | Current user identity |
| `GET` | `/api/users/{uuid}` | Authenticated | Find user |
| `PUT` | `/api/users/{uuid}` | Authenticated | Update user |
| `DELETE` | `/api/users/{uuid}` | Authenticated | Delete user |

### Collection Context

| Method | Path | Auth | Description |
|---|---|---|---|
| `POST` | `/api/albums` | Authenticated | Add album |
| `GET` | `/api/albums/{uuid}` | Authenticated | Find album |
| `PUT` | `/api/albums/{uuid}` | Authenticated | Update album |
| `DELETE` | `/api/albums/{uuid}` | Authenticated | Delete album |
| `GET` | `/api/albums/owner/{uuid}` | Authenticated | Find albums by owner (paginated, filterable by `genre`, sortable via `sort_by`/`sort_order`) |
| `POST` | `/api/external-references` | Authenticated | Add external reference |
| `GET` | `/api/external-references/{uuid}` | Authenticated | Find external reference |
| `PUT` | `/api/external-references/{uuid}` | Authenticated | Update external reference |
| `DELETE` | `/api/external-references/{uuid}` | Authenticated | Delete external reference |
| `GET` | `/api/external-references/album/{albumUuid}` | Authenticated | Find external refs by album |

---

## Getting Started

> **Note:** Use `symfony php` / `symfony console` (Symfony CLI) instead of `php` / `bin/console` directly. The Symfony CLI auto-exposes Docker environment variables (e.g. dynamic PostgreSQL port).

```bash
# Install dependencies
composer install

# Run database migrations
symfony console doctrine:migrations:migrate

# Run tests
symfony php bin/phpunit

# Fix code style
./vendor/bin/php-cs-fixer fix

# Static analysis
vendor/bin/phpstan analyse --memory-limit 256M

# Lint configuration
symfony console lint:yaml config/
symfony console lint:container
```

---

## Coding Conventions

- **PSR-12** code style.
- Small methods with explicit naming; no hidden side effects.
- Prefer value objects for constrained domain data.
- Avoid static mutable state; use constructor injection.
- Tests focus on use-case behavior (command/query handlers) and API contracts.
- Use realistic data in tests (real artist names, album titles) — no generic placeholders.
- Add regression tests for every bug fix.
