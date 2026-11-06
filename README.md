# Thameside ERP

A modular ERP application built with Laravel 13, Inertia, Vue 3, and SQLite by default. It includes inventory, purchasing, sales, nominal accounting, HR/payroll, reports, role-based access, activity history, and optional AI-assisted insights.

## Requirements

- PHP 8.3 or later with the extensions required by Laravel and SQLite
- Composer
- Node.js and npm

## Local setup

```powershell
composer install
Copy-Item .env.example .env
php artisan key:generate
php artisan migrate --seed
npm install
npm run build
php artisan serve
```

For development with hot reload, use two terminals: run `npm run dev` in one and `php artisan serve` in the other. The app uses SQLite by default; configure `DB_CONNECTION` and the corresponding database settings in `.env` if you use another Laravel-supported database.

## Tests and code style

```powershell
php artisan test
vendor/bin/pint --test
npm run build
```

The feature tests exercise authentication, permissions, inventory movements, purchasing, sales, journal posting, payroll, reporting, and exports.

## Modules

- **Inventory:** products, categories, stock ledger, serial tracking, valuation.
- **Purchasing:** suppliers, purchase orders, goods receipt, supplier bills and payments.
- **Sales:** customers, sales orders, invoices, receipts and invoice PDFs.
- **Accounting:** chart of accounts, balanced double-entry journals, trial balance and VAT return.
- **People:** employee records, payroll runs and payslips.
- **Reporting:** sales and stock reports with spreadsheet exports.
- **Insights:** deterministic demand forecasts and anomaly checks, with optional provider generated summaries.

Business actions run inside database transactions. Money is stored as integer pence, and stock on hand is calculated from stock movement entries. Access is controlled through roles and permissions seeded by `RolesAndPermissionsSeeder`.

Company details, currency display, VAT rate, payment terms, low-stock threshold, and document prefixes are configured with the `ERP_*` variables listed in `.env.example`. Copy `.env.example` to `.env`, then replace the sample `ERP_COMPANY_*` values and set both `ERP_VAT_RATE_BPS` and `ERP_VAT_RATE_LABEL` to match your tax rate. Clear cached config after environment changes with `php artisan config:clear`.

## Optional AI summaries

Set `AI_BASE_URL`, `AI_MODEL`, and `AI_API_KEY` in the local `.env` to enable the optional OpenAI-compatible summary provider. Without a key, the insights page uses its deterministic fallback. Never commit `.env` or put API keys in frontend code.

## Deployment notes

Set `APP_ENV=production`, `APP_DEBUG=false`, a production `APP_URL`, a secure generated `APP_KEY`, and production database, session, cache, queue, mail, and filesystem settings. Configure the `ERP_*` values in the deployment environment before using the app for real business records. Review backups, HTTPS, queue workers, scheduled tasks, monitoring, and restore procedures for the target host.

This repository is a single-tenant ERP starter. The sample company profile and demo seeders are illustrative; they are not a substitute for validating statutory accounting, payroll, VAT, or data retention requirements with qualified advisers before production use.
