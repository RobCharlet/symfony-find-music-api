# API Endpoints

Base URL: `/api`

## Public

| Method | Path | Description |
|--------|------|-------------|
| POST | /api/login_check | Get JWT token `{ email, password }` |
| POST | /api/token/refresh | Refresh JWT |
| POST | /api/register | Self-register `{ email, password }` |
| GET | /api/health | Health check |
| GET | /api/doc | Swagger UI |
| GET | /api/doc.json | OpenAPI spec |
| GET | /api/profiles/{uuid} | Public profile + collection (opt-in, 404 if not public) |

## Users (IS_AUTHENTICATED_FULLY)

| Method | Path | Description | Role |
|--------|------|-------------|------|
| GET | /api/users/me | Current user profile | USER |
| POST | /api/users | Create user | ADMIN |
| GET | /api/users/{uuid} | Get user | USER |
| PUT | /api/users/{uuid} | Update user (`isPublic` toggle exempt from currentPassword gate) | USER |
| DELETE | /api/users/{uuid} | Delete user | USER |

## Discogs credentials (IS_AUTHENTICATED_FULLY)

| Method | Path | Description |
|--------|------|-------------|
| PUT | /api/users/me/discogs-access-token | Store personal access token `{ accessToken }` |
| DELETE | /api/users/me/discogs-access-token | Clear personal access token |

## Albums (IS_AUTHENTICATED_FULLY)

| Method | Path | Description |
|--------|------|-------------|
| POST | /api/albums | Create album |
| GET | /api/albums/{uuid} | Get album |
| PUT | /api/albums/{uuid} | Update album |
| DELETE | /api/albums/{uuid} | Delete album |
| POST | /api/albums/{uuid}/enrich | Enrich album from Discogs (requires Discogs PAT + `Discogs` external reference) |

## Collections (IS_AUTHENTICATED_FULLY)

| Method | Path | Description |
|--------|------|-------------|
| GET | /api/collections/owner/{ownerUuid} | Paginated collection |
| GET | /api/collections/owner/{ownerUuid}/stats | Collection statistics |
| GET | /api/collections/owner/{ownerUuid}/export | Export collection (CSV/JSON) |
| POST | /api/collections/import/discogs | Import from Discogs CSV |

## External References (IS_AUTHENTICATED_FULLY)

| Method | Path | Description |
|--------|------|-------------|
| POST | /api/external-references | Create external reference |
| GET | /api/external-references/{uuid} | Get external reference |
| GET | /api/external-references/album/{albumUuid} | List references for album |
| PUT | /api/external-references/{uuid} | Update external reference |
| DELETE | /api/external-references/{uuid} | Delete external reference |

## Admin (ROLE_ADMIN)

| Method | Path | Description |
|--------|------|-------------|
| GET | /api/admin/collections | All collections (all users) |
| GET | /api/admin/external-references | All external references (all users) |

## Error shapes

All errors return `application/problem+json`:

```json
{ "type": "not_found" }
{ "type": "forbidden" }
{ "type": "validation_error", "violations": [...] }
{ "type": "conflict" }
{ "type": "server_error" }
```
