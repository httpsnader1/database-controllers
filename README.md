# Laravel Database Controllers

A powerful Laravel package to manage your database directly from your dashboard. Easily view tables, manage records, export/import backups, and more.

## Features

- **Database Overview**: View connection details, host, port, PHP/Laravel versions, and general stats.
- **Table Management**:
    - Browse all database tables.
    - View, add, edit, and delete records.
    - Bulk delete support.
    - Advanced filtering options.
    - Support for pagination.
- **Backup & Export**:
    - Export full database or structure only.
    - Support for SQL and ZIP formats.
    - Exclude specific tables from backups.
    - View, download, and delete existing backups.
- **Import & Restore**:
    - Import SQL or ZIP files directly.
    - Restore database from existing local backups.
    - Optimized for large files (tested with 1.8GB+).
- **Security**: Optional password protection for the dashboard.
- **Localization**: Multi-language support including:
    - Arabic (AR)
    - English (EN)
    - Spanish (ES)
    - French (FR)

## Installation

You can install the package via composer:

```bash
composer require httpsnader1/database-controllers
```

If you are developing locally and have the package in a `packages` folder:

```json
"repositories": [
    {
        "type": "path",
        "url": "./packages/httpsnader1/database-controllers"
    }
],
"require": {
    "httpsnader1/database-controllers": "dev-main"
}
```

## Configuration

You can publish the configuration file using:

```bash
php artisan vendor:publish --provider="Httpsnader1\DatabaseControllers\DatabaseControllersServiceProvider" --tag="config"
```

The configuration file will be available at `config/database-controllers.php`.

### Available Options

- `route_prefix`: The URL prefix for the dashboard (default: `database-controllers`).
- `password`: Password for dashboard access. Set to `null` to disable protection.
- `excluded_tables`: Array of tables to hide from the dashboard and backups.
- `default_per_page`: Default number of rows per page in the table viewer.

## Usage

Access the dashboard by visiting:
`your-app.test/database-controllers`

### Local Development (Laragon)

This package is optimized for Windows/Laragon environments and will automatically search for `mysql` and `mysqldump` binaries in standard Laragon paths.

## Large File Support

The package is specifically optimized to handle large database imports and exports (1.8 GB and above) by:
- Increasing PHP memory limits dynamically.
- Setting unlimited execution time.
- Using `--quick` and `--single-transaction` for `mysqldump`.
- Handling large SQL warning/error outputs efficiently to prevent memory crashes.

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
