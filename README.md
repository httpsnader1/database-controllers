# Laravel Database Controllers

A powerful Laravel package to manage your database directly from your dashboard. Easily view tables, manage records, export/import backups, and more.

## Features

### 📊 Database Overview
- **Real-time Statistics**: View total number of tables, total records, and current database size.
- **Environment Details**: Quick access to connection details (Host, Port, Database, Driver) and PHP/Laravel versions.
- **Backup Status**: See the date of your most recent backup at a glance.

### 🗄️ Advanced Table Management
- **Smart Browsing**: Explore all tables in your database with an intuitive UI.
- **Full CRUD Support**: Add, Edit, and Delete records effortlessly.
- **Smart Field Handling**: 
    - **Automatic Hashing**: Automatically hashes fields named `password`.
    - **Timestamps**: Handles `created_at` and `updated_at` automatically.
    - **Primary Key Detection**: Support for MySQL and SQLite primary key identification.
- **Bulk Operations**: Perform bulk deletes or truncate entire tables with foreign key constraint safety.
- **Powerful Filtering**: Filter records using operators like `LIKE`, `IN`, `=`, `IS NULL`, and `IS NOT NULL`.
- **Flexible Sorting**: Sort data by any column in ascending or descending order.

### 📂 Backup & Export
- **Flexible Exports**: Choose between exporting the full database or just the structure.
- **Multiple Formats**: Support for both standard `.sql` files and compressed `.zip` archives.
- **Smart Exclusions**: Easily exclude specific tables (like logs or sessions) from your backups to keep them lightweight.
- **Backup Library**: View, download, or delete previous backups directly from the dashboard.

### 🔄 Import & Restore
- **File Uploads**: Import `.sql` or `.zip` files directly through the browser.
- **Local Restoration**: Restore your database from any existing local backup with a single click.
- **Background Jobs**: Support for background processing for large imports to prevent timeout issues.
- **CLI Power**: Includes a specialized `db:restore` artisan command for massive database files.

### 🛡️ Security & Localization
- **Password Protection**: Optional dashboard access protection via configurable password.
- **Multi-language Support**: Fully localized in:
    - 🇺🇸 English (EN)
    - 🇸🇦 Arabic (AR)
    - 🇫🇷 French (FR)
    - 🇪🇸 Spanish (ES)

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
    "httpsnader1/database-controllers": "dev-master"
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
- `password`: Password for dashboard access. Set to `null` to disable protection (default: `1234`).
- `backup_prefix`: Prefix for all generated backup files (default: `backup`).
- `excluded_tables`: Array of tables to hide from the dashboard and backups.
- `default_per_page`: Default number of rows per page in the table viewer (default: `10`).
- `per_page_options`: Options for the rows per page dropdown.

## Usage

Access the dashboard by visiting:
`your-app.test/database-controllers`

### CLI Restore Command

For very large files, it's recommended to use the Artisan command:

```bash
# Restore from a direct path
php artisan db:restore path/to/backup.sql

# Restore from the local backups folder
php artisan db:restore my-backup.zip --from-backups
```

## Large File Support

The package is specifically optimized to handle large database imports and exports (1.8 GB and above) by:
- Increasing PHP memory limits dynamically.
- Setting unlimited execution time.
- Using `--quick` and `--single-transaction` for `mysqldump`.
- Handling large SQL warning/error outputs efficiently to prevent memory crashes.
- Utilizing background jobs and Artisan commands for heavy operations.

## Local Development (Laragon)

This package is optimized for Windows/Laragon environments and will automatically search for `mysql` and `mysqldump` binaries in standard Laragon paths.

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
