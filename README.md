# Laravel Courier Management API

A JSON REST API for courier master data, built with Laravel 12, Eloquent ORM, Form Requests, and API Resources. The project has no frontend and does not require authentication.

## Features

- Create, view, update, and delete couriers.
- Search courier names using multiple unordered terms (for example, `budi agung`).
- Filter by one or more courier levels.
- Sort by name or newest registration date.
- Paginate results with Laravel pagination metadata.
- Validate request data and return consistent JSON responses.

## Requirements

- PHP 8.2 or later and the PHP extensions required by Laravel and the selected database driver. For SQLite development and tests, enable PDO SQLite. PHPUnit also needs the XML extension.
- Composer 2.
- SQLite for the default local and test setup, or another database supported by Laravel.

## Installation

```sh
git clone https://github.com/luqmanulhakimdev/laravel-courier-management-api.git
cd laravel-courier-management-api
composer install
cp .env.example .env
php artisan key:generate
```

On Windows, copy `.env.example` to `.env` using File Explorer or `copy .env.example .env` in Command Prompt.

## Environment and database setup

The default `.env.example` uses SQLite. Create the database file and confirm the database settings in `.env`:

```sh
touch database/database.sqlite
```

```dotenv
DB_CONNECTION=sqlite
DB_DATABASE=/absolute/path/to/laravel-courier-management-api/database/database.sqlite
```

Use an absolute path for `DB_DATABASE`. On Windows, use a Windows absolute path. If you choose MySQL or PostgreSQL, set the matching `DB_CONNECTION`, host, port, database, username, and password instead, and ensure the corresponding PHP PDO driver is installed. Never commit `.env` or database credentials; `.env` and SQLite database files are excluded by `.gitignore`.

For a one-command setup after PHP extensions and Composer are installed, run `composer setup`; it installs dependencies, creates `.env` when missing, generates the application key, creates the default SQLite file, and migrates the database. To run these steps manually, run the migrations:

```sh
php artisan migrate
```

This creates Laravel's standard support tables and the `couriers` table. The courier API does not use authentication tables or queue features.

## Run the API

```sh
php artisan serve
```

The default base URL is `http://127.0.0.1:8000`; API routes are under `/api`. For example, the courier list is `http://127.0.0.1:8000/api/couriers`.

## API Documentation & Postman

### Interactive Swagger UI
Start the local server (`php artisan serve`) and visit:
- **Swagger UI**: [http://127.0.0.1:8000/docs](http://127.0.0.1:8000/docs) (or `http://127.0.0.1:8000/api/docs`)
- **OpenAPI 3.0 JSON**: [http://127.0.0.1:8000/openapi.json](http://127.0.0.1:8000/openapi.json) (file: `openapi.json`)
- **OpenAPI 3.0 YAML**: [http://127.0.0.1:8000/openapi.yaml](http://127.0.0.1:8000/openapi.yaml) (file: `openapi.yaml`)

### Import to Postman
You can import the API directly into Postman using either method:
1. **Direct Collection Import**: Import `courier-management-api.postman_collection.json` located in the root of this project (or download from `http://127.0.0.1:8000/courier-management-api.postman_collection.json`). It comes preconfigured with collection variables, query parameters, request bodies, and sample values for all endpoints.
2. **OpenAPI Spec Import**: In Postman, click **Import** and select `openapi.yaml` or `openapi.json` (or paste `http://127.0.0.1:8000/openapi.json`). Postman will automatically generate a complete API collection.

## Automated tests

```sh
php artisan test
```

Feature tests use `RefreshDatabase` and the SQLite in-memory database configured in `phpunit.xml`; they do not need the local `database/database.sqlite` file.

## Database schema

The API's main table is `couriers`. Laravel's standard `created_at` and `updated_at` columns track record creation and modification.

| Column | Database type | Rules and purpose |
|---|---|---|
| `id` | unsigned big integer, primary key | Auto-incrementing courier identifier. |
| `name` | string, max 150 | Required courier name. |
| `phone` | string, max 30 | Required phone number; stored as text to preserve leading zeros. |
| `email` | string, nullable, unique | Optional email address; non-null values must be unique. |
| `address` | string, nullable, max 500 | Optional courier address. |
| `level` | unsigned tiny integer | Required level from 1 through 5, enforced by request validation. |
| `registered_at` | nullable datetime | Registration date used for newest-first sorting. Set to the current time on create when omitted or null; a supplied valid date/time is retained. It may be set to null on update. |
| `created_at` | nullable timestamp | Laravel creation timestamp. |
| `updated_at` | nullable timestamp | Laravel last-update timestamp. |

The `users`, `cache`, and `jobs` migrations are part of the standard Laravel application skeleton; they are not used by these courier endpoints. No relationship tables are required for the courier master data.

## API endpoints

All endpoints return JSON. The API uses Laravel route model binding; a courier ID that does not exist returns `404 Not Found`.

| Method | Endpoint | Description | Success status |
|---|---|---|---|
| `GET` | `/api/couriers` | List, search, filter, sort, and paginate couriers | `200 OK` |
| `POST` | `/api/couriers` | Create a courier | `201 Created` |
| `GET` | `/api/couriers/{id}` | Get one courier | `200 OK` |
| `PUT` | `/api/couriers/{id}` | Update courier fields (name, phone, level required) | `200 OK` |
| `PATCH` | `/api/couriers/{id}` | Partially update supplied fields | `200 OK` |
| `DELETE` | `/api/couriers/{id}` | Permanently delete a courier | `204 No Content` |

### List query parameters

| Parameter | Meaning | Validation/default |
|---|---|---|
| `search` | Name search; every whitespace-separated term must match somewhere in the name | Optional string, up to 150 characters. Term order and adjacency do not matter. |
| `level` | Comma-separated list of levels, such as `2,3` | Each value must be an integer from 1 through 5. Duplicate values are ignored; empty and invalid values return `422`. |
| `sort_by` | Selects the sort mode | Omit for `name ASC`; set to `registered_at` for newest registration first. Other values return `422`. |
| `per_page` | Number of results per page | Default `10`; allowed range is 1–100. |
| `page` | Page number | Default `1`; must be an integer greater than or equal to 1. |

Name sorting uses `id` as a secondary key. Registration sorting uses `id` as a stable secondary key. Search uses parameter-bound query builder conditions and database `LIKE` behavior. SQLite and common case-insensitive MySQL collations are case-insensitive for these names; other databases may follow their configured collation. Unsupported query parameters return `422`.

Examples:

```http
GET /api/couriers
GET /api/couriers?search=budi+agung
GET /api/couriers?level=2,3
GET /api/couriers?sort_by=registered_at
GET /api/couriers?search=budi+agung&level=2,3&sort_by=registered_at&per_page=20&page=1
```

A paginated response follows Laravel's resource pagination format:

```json
{
  "data": [
    {
      "id": 1,
      "name": "Budi Agung",
      "phone": "081234567890",
      "email": "budi@example.com",
      "address": "Jakarta",
      "level": 2,
      "registered_at": "2026-10-14T00:00:00.000000Z",
      "created_at": "2026-10-14T00:00:00.000000Z",
      "updated_at": "2026-10-14T00:00:00.000000Z"
    }
  ],
  "links": {"first": "...", "last": "...", "prev": null, "next": null},
  "meta": {"current_page": 1, "from": 1, "last_page": 1, "per_page": 10, "to": 1, "total": 1}
}
```

### Create a courier

```http
POST /api/couriers
Content-Type: application/json
Accept: application/json
```

```json
{
  "name": "Budi Agung",
  "phone": "081234567890",
  "email": "budi@example.com",
  "address": "Jakarta",
  "level": 2,
  "registered_at": "2026-10-14 09:00:00"
}
```

`registered_at` is optional. When omitted or null at creation, the API uses the current time. A supplied valid date/time is saved. `email` and `address` may be null. The response is `201 Created` with the courier resource under `data`.

### Get one courier

```http
GET /api/couriers/1
```

A successful response has a `data` object containing `id`, `name`, `phone`, `email`, `address`, `level`, `registered_at`, `created_at`, and `updated_at`.

### Update a courier

```http
PATCH /api/couriers/1
Content-Type: application/json
Accept: application/json
```

```json
{"name":"Budi Agung Pratama","level":3}
```

`PATCH` accepts partial updates and leaves omitted fields unchanged. `PUT` requires `name`, `phone`, and `level`; nullable `email` and `address` may be omitted or set to null. `registered_at` can be changed to a valid date/time or cleared with null. The API rejects `id`, `created_at`, `updated_at`, and other unknown input fields.

### Delete a courier

```http
DELETE /api/couriers/1
Accept: application/json
```

Successful deletion returns `204 No Content` with an empty body. Deletion is permanent; the API does not use soft deletes.

## Validation errors

Invalid request data returns `422 Unprocessable Entity` in Laravel's JSON validation format. For example, a level outside 1–5 or a duplicate email returns an error response such as:

```json
{
  "message": "The level field must be between 1 and 5.",
  "errors": {
    "level": ["The level field must be between 1 and 5."]
  }
}
```

Other common validation failures include missing required store fields, malformed email addresses, invalid dates, invalid level filters, unsupported sort values, and `per_page` values outside 1–100.

## Assumptions and scope

- Phone numbers are strings so leading zeroes are preserved.
- Email is optional, but unique when provided.
- New couriers default to the current registration time unless a valid value is supplied.
- `level` is constrained to 1–5 by API validation.
- Authentication, authorization, soft deletion, and frontend pages are outside the scope of this API.
