<?php

namespace Httpsnader1\DatabaseControllers\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class DbRestoreCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * Examples:
     *  php artisan db:restore storage/app/DatabaseControllers/backups/my.sql
     *  php artisan db:restore my.zip --from-backups
     */
    protected $signature = 'db:restore
                            {path? : Path to .sql or .zip file, or backup filename when using --from-backups}
                            {--from-backups : Treat the path/filename as located under storage/app/DatabaseControllers/backups}
                            {--db= : Database name (defaults to current connection)}
                            {--host= : DB host (defaults to mysql host)}
                            {--user= : DB user (defaults to mysql username)}
                            {--pass= : DB password (defaults to mysql password)}
                            {--port= : DB port (defaults to mysql port)}';

    /**
     * The console command description.
     */
    protected $description = 'Restore a MySQL dump (.sql or .zip containing .sql) using the mysql client. Designed for very large files via CLI.';

    public function handle(): int
    {
        if (!function_exists('exec')) {
            $this->error('The PHP exec() function is disabled. Please enable it in your php.ini.');
            return self::FAILURE;
        }

        $path = $this->argument('path');
        $fromBackups = (bool) $this->option('from-backups');

        if (!$path) {
            $this->error('You must provide the path or filename of the backup.');
            return self::INVALID;
        }

        if ($fromBackups) {
            $path = storage_path('app/DatabaseControllers/backups/' . $path);
        }

        if (!file_exists($path)) {
            $this->error("File not found: {$path}");
            return self::INVALID;
        }

        $dbName = $this->option('db') ?: DB::connection()->getDatabaseName();
        $host = $this->option('host') ?: config('database.connections.mysql.host', '127.0.0.1');
        $user = $this->option('user') ?: config('database.connections.mysql.username', 'root');
        $pass = $this->option('pass') ?: config('database.connections.mysql.password', '');
        $port = $this->option('port') ?: config('database.connections.mysql.port', '3306');

        $sqlPath = $path;
        $extractPath = null;

        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        if ($ext === 'zip') {
            if (!class_exists('\\ZipArchive')) {
                $this->error('PHP ZipArchive extension is not installed.');
                return self::FAILURE;
            }

            $zip = new \ZipArchive();
            if ($zip->open($path) === TRUE) {
                $extractPath = storage_path('app/DatabaseControllers/temp_' . time());
                if (!is_dir($extractPath) && !mkdir($extractPath, 0755, true) && !is_dir($extractPath)) {
                    $this->error(sprintf('Directory "%s" was not created', $extractPath));
                    return self::FAILURE;
                }
                $zip->extractTo($extractPath);
                $zip->close();

                $files = scandir($extractPath);
                $foundSql = false;
                foreach ($files as $f) {
                    if (str_ends_with(strtolower($f), '.sql')) {
                        $sqlPath = $extractPath . DIRECTORY_SEPARATOR . $f;
                        $foundSql = true;
                        break;
                    }
                }

                if (!$foundSql) {
                    $this->recursiveRmdir($extractPath);
                    $this->error('No .sql file found in the zip archive.');
                    return self::INVALID;
                }
            } else {
                $this->error('Failed to open the zip archive.');
                return self::FAILURE;
            }
        }

        // Perform the import
        [$returnVar, $output] = $this->performImport($sqlPath, $dbName, $host, $user, $pass, $port);

        if ($extractPath) {
            $this->recursiveRmdir($extractPath);
        }

        if ($returnVar !== 0) {
            $errorMsg = !empty($output) ? implode(' ', array_slice($output, 0, 100)) : "Unknown error (code: {$returnVar})";
            $this->error('Import failed: ' . $errorMsg);
            return self::FAILURE;
        }

        $this->info('Database restored successfully from: ' . $path);
        return self::SUCCESS;
    }

    private function getBinaryPath(string $binary): string
    {
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $drive = substr(base_path(), 0, 2);
            $searchPaths = [
                $drive . DIRECTORY_SEPARATOR . 'laragon' . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'mysql',
                'C:' . DIRECTORY_SEPARATOR . 'laragon' . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'mysql',
            ];

            foreach ($searchPaths as $path) {
                if (is_dir($path)) {
                    $versions = array_diff(scandir($path, SCANDIR_SORT_DESCENDING), ['.', '..']);
                    foreach ($versions as $v) {
                        $testPath = $path . DIRECTORY_SEPARATOR . $v . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . "{$binary}.exe";
                        if (file_exists($testPath)) {
                            return '"' . $testPath . '"';
                        }
                    }
                }
            }
        }

        // Fallback to search in path or direct binary name
        return $binary;
    }

    private function performImport(string $sqlPath, string $dbName, string $host, string $user, string $pass, string $port): array
    {
        $mysql = $this->getBinaryPath('mysql');
        $passFlag = !empty($pass) ? "--password=\"{$pass}\"" : "";

        // Optimizations for faster import
        $initCommand = "SET FOREIGN_KEY_CHECKS=0; SET UNIQUE_CHECKS=0; SET AUTOCOMMIT=1;";

        $command = "{$mysql} --user={$user} {$passFlag} --host={$host} --port={$port} --max_allowed_packet=512M --binary-mode --connect-timeout=10 --net_buffer_length=1048576 --init-command=\"{$initCommand}\" {$dbName} < \"{$sqlPath}\" 2>&1";

        exec($command, $output, $returnVar);

        return [$returnVar, $output];
    }

    private function recursiveRmdir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        $objects = scandir($dir);
        foreach ($objects as $object) {
            if ($object === '.' || $object === '..') {
                continue;
            }
            $path = $dir . DIRECTORY_SEPARATOR . $object;
            if (is_dir($path)) {
                $this->recursiveRmdir($path);
            } else {
                @unlink($path);
            }
        }
        @rmdir($dir);
    }
}
