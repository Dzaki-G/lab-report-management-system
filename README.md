# Tugas Akhir Website

[![License: MIT](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)

> Digitizing a lab's paper-based test-report approval process — from submission to signed, delivered document.

A Laravel 12 application for managing laboratory/testing forms and document workflows used internally by administrators, analysts, and division/unit heads. It provides a full submission → verification → testing → signing → delivery workflow, automated document generation with Google Docs/Drive, role-based dashboards, and notifications.

## Key features

- Role-based access control (SUPER_ADMIN, ADMIN, KEPALA_UPA, KEPALA_DIVISI, ANALIS) with middleware-protected routes.
- Full form lifecycle for "Form Pengujian": submission, UPA/Divisi verification, assignment to analysts, input of results, LHP input, signatures, and final delivery.
- Automated document generation and management using Google Drive & Google Docs (SPU, SP3, LHP): template copying, placeholder replacement, dynamic table population, and deletion of Drive files when forms are removed.
- Notifications to users/roles when forms require action (submission, approvals, signatures).
- Powerful filtering, search, and pagination for large form lists (status, stage, quick filters like deadlines/overdue, date ranges).
- Admin utilities: user management, unit/parameter CRUD, and a Super Admin endpoint to clear test data (with confirmation).
- Test scaffold (PHPUnit) and development scripts for quick setup.

## Tech stack

- PHP 8.2+ (platform configured for 8.3)
- Laravel 12
- Blade templating for views
- Google API Client (google/apiclient) for Drive & Docs automation
- phpoffice/phpword (installed for docx handling)
- Tailwind CSS + Vite for frontend assets
- PHPUnit for testing

## Repository layout (important files)

```
app/
  Http/Controllers/           # Controllers (FormPengujianController, DashboardController, etc.)
  Services/                   # GoogleDocsService.php - Google Drive & Docs logic
  Enums/Role.php              # Role constants used across middleware
routes/
  web.php                     # Application routes and role-based routing
resources/views/              # Blade views (UI templates)
database/                     # Migrations and seeders
doc/                          # Project-specific documentation
composer.json                 # PHP dependencies & helpful scripts (setup, dev, test)
package.json                  # Node dependencies for assets
README.md                     # This file
```

## Quickstart — run locally

1. Clone the repository

   git clone https://github.com/Dzaki-G/tugas-akhir-website.git
   cd tugas-akhir-website

2. Install PHP dependencies

   composer install

3. Copy and configure environment

   cp .env.example .env
   php artisan key:generate

   Edit `.env` and set database credentials and other required env variables (see *Environment/Google* below).

4. Migrate and seed database

   php artisan migrate --seed

5. Install frontend dependencies and build assets

   npm install
   npm run build

6. Run the application

   php artisan serve

   Visit http://127.0.0.1:8000

Notes: composer.json contains useful scripts:
- composer run-script setup  — runs install, .env copy, key generate, migrate, npm install, and build
- composer run-script dev    — runs concurrent dev processes (artisan serve, queue worker, pail, vite dev)
- composer run-script test   — runs phpunit tests

## Environment / Google API setup

This project integrates with Google Drive & Google Docs. You can authenticate either using a Service Account (recommended for server-to-server) or OAuth credentials (with a refresh token).

Required environment variables (used by app/Services/GoogleDocsService.php and config/services.php):

- GOOGLE_SERVICE_ACCOUNT_PATH — path to service account JSON (if using service account)
- GOOGLE_TEMPLATES_FOLDER_ID — Google Drive folder ID that contains templates (SPU, SP3 templates)
- GOOGLE_SPU_FOLDER_ID — target folder ID where generated SPU documents are saved
- GOOGLE_SP3_FOLDER_ID — target folder ID where generated SP3 documents are saved

If using OAuth client credentials, configure in config/services.php or via env:

- SERVICES_GOOGLE_CLIENT_ID (or set in config/services.php)
- SERVICES_GOOGLE_CLIENT_SECRET
- SERVICES_GOOGLE_REFRESH_TOKEN

Steps to enable API access:
1. Go to Google Cloud Console and enable Google Drive API and Google Docs API for your project.
2. If using a Service Account: create a service account, download the JSON, store it in the server, set GOOGLE_SERVICE_ACCOUNT_PATH to the path, and grant the service account access to the templates and target folders (share the folder with the service account email).
3. If using OAuth: create OAuth credentials, obtain a refresh token for an account that has access to the Drive folders, and set the client id/secret/refresh token in config/services.php or env.
4. Set the template and target folder IDs in the env file.

## Key configuration & files to review

- app/Services/GoogleDocsService.php — document generation, placeholder replacement, table population logic.
- app/Http/Controllers/FormPengujianController.php — main form lifecycle and document generation orchestration.
- routes/web.php — routing and middleware (role-based access) definitions.
- app/Enums/Role.php — numeric role ids used across controllers/middleware.

## Running tests

- Run the test suite with:

  php artisan test

or directly via phpunit:

  ./vendor/bin/phpunit

## Security & production notes

- Rotate credentials used for Google or other integrations if they are leaked. Never commit service account JSON or client secrets to the repository.
- When making the repository public, ensure no secrets remain in git history. Use tools like `git-secrets`, `trufflehog`, or `BFG Repo-Cleaner` to scrub sensitive data.
- For production, prefer Service Accounts and restrict Drive folder permissions. Consider using queues for long-running document generation tasks.

## Contributing

Contributions are welcome. Open an issue or a PR for bug fixes and features. When contributing:
- Follow PSR-12 / Laravel coding conventions.
- Run tests locally before submitting a PR.
- Add relevant documentation to `doc/` when adding features.

## License

This project is released under the MIT License.
