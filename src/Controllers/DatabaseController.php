<?php

namespace Httpsnader1\DatabaseControllers\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Http\Request;

class DatabaseController extends Controller
{
    public function login()
    {
        // If already logged in, redirect to dashboard
        if (session('db_controller_auth') === true || empty(config('database-controllers.password'))) {
            return redirect()->route('database-controllers.index');
        }

        return view('database-controllers::login');
    }

    public function authenticate(Request $request)
    {
        $password = (string) config('database-controllers.password');

        if ($request->input('password') === $password) {
            session(['db_controller_auth' => true]);
            return redirect()->route('database-controllers.index');
        }

        return back()->with('error', 'Invalid password. Please try again.');
    }

    public function logout()
    {
        session()->forget('db_controller_auth');
        return redirect()->route('database-controllers.login');
    }

    private function getExcludedTables()
    {
        $configExcluded = config('database-controllers.excluded_tables', []);
        $dynamicExcluded = [];
        $filePath = storage_path('app/database-excluded-tables.json');
        
        if (file_exists($filePath)) {
            $dynamicExcluded = json_decode(file_get_contents($filePath), true) ?: [];
        }

        return array_unique(array_merge($configExcluded, $dynamicExcluded));
    }

    private function getTables()
    {
        try {
            $dbName = DB::connection()->getDatabaseName();
            $excluded = $this->getExcludedTables();
            
            if (config('database.default') === 'sqlite') {
                $dbTables = DB::select("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'");
                $tables = array_map(fn($t) => $t->name, $dbTables);
            } else {
                // For MySQL/MariaDB and others
                $dbTables = DB::select("SELECT TABLE_NAME as name FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = ?", [$dbName]);
                
                if (empty($dbTables)) {
                    // Fallback if INFORMATION_SCHEMA is restricted
                    $tables = Schema::getTableListing();
                } else {
                    $tables = array_map(fn($t) => $t->name, $dbTables);
                }
            }

            $tables = array_values(array_filter($tables, fn($t) => !in_array($t, $excluded)));

            return array_map(function($table) {
                try {
                    $count = DB::table($table)->count();
                } catch (\Exception $e) {
                    $count = 0;
                }
                return [
                    'name' => $table,
                    'count' => $count
                ];
            }, $tables);

        } catch (\Exception $e) {
            return [];
        }
    }

    public function index()
    {
        $activeConnection = config('database.default');
        $tables = $this->getTables();
        
        $dbInfo = [
            'Connection' => $activeConnection,
            'Host' => config("database.connections.{$activeConnection}.host"),
            'Port' => config("database.connections.{$activeConnection}.port"),
            'Database' => DB::connection()->getDatabaseName(),
            'Username' => config("database.connections.{$activeConnection}.username"),
            'PHP Version' => PHP_VERSION,
            'Laravel Version' => app()->version(),
        ];
        
        $totalRows = 0;
        foreach ($tables as $table) {
            $totalRows += $table['count'];
        }

        $stats = [
            'Total Tables' => count($tables),
            'Total Records' => number_format($totalRows),
            'Database Size' => $this->getDatabaseSize(),
            'Last Backup' => $this->getLastBackupDate(),
        ];

        return view('database-controllers::index', compact('dbInfo', 'tables', 'stats'));
    }

    public function backup()
    {
        $tables = $this->getTables();
        $backups = [];
        $backupPath = storage_path('app/backups');
        
        if (is_dir($backupPath)) {
            $files = scandir($backupPath);
            foreach ($files as $file) {
                if ($file !== '.' && $file !== '..') {
                    $backups[] = [
                        'name' => $file,
                        'size' => round(filesize($backupPath . '/' . $file) / 1024, 2) . ' KB',
                        'date' => date('Y-m-d H:i:s', filemtime($backupPath . '/' . $file))
                    ];
                }
            }
        }
        
        usort($backups, fn($a, $b) => $b['date'] <=> $a['date']);

        return view('database-controllers::backup', [
            'tables' => $tables,
            'backups' => $backups,
            'excludedTables' => $this->getExcludedTables()
        ]);
    }

    public function updateExcludedTables(Request $request)
    {
        $excluded = array_values(array_filter($request->input('excluded_tables', []), function($val) {
            return !empty(trim((string)$val));
        }));

        $filePath = storage_path('app/database-excluded-tables.json');
        file_put_contents($filePath, json_encode($excluded));

        return back()->with('success', 'Excluded tables updated successfully.');
    }

    public function export()
    {
        $dbName = DB::connection()->getDatabaseName();
        $prefix = config('database-controllers.backup_prefix', 'backup');
        $fileName = "{$prefix}-" . $dbName . "-" . date('Y-m-d_H-i-s') . ".sql";
        $backupPath = storage_path('app/backups');

        if (!is_dir($backupPath)) {
            mkdir($backupPath, 0755, true);
        }

        $fullName = $backupPath . '/' . $fileName;

        $host = config("database.connections.mysql.host", '127.0.0.1');
        $user = config("database.connections.mysql.username", 'root');
        $pass = config("database.connections.mysql.password", '');
        $port = config("database.connections.mysql.port", '3306');

        $mysqldump = 'mysqldump';
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            // Get current drive letter (e.g., C: or D:)
            $drive = substr(base_path(), 0, 2);
            $laragonPath = $drive . '\\laragon\\bin\\mysql';
            
            // Check current drive Laragon, then fallback to C: if app is not on C:
            $searchPaths = [$laragonPath, 'C:\\laragon\\bin\\mysql'];
            
            foreach ($searchPaths as $path) {
                if (is_dir($path)) {
                    $versions = array_diff(scandir($path, SCANDIR_SORT_DESCENDING), ['.', '..']);
                    foreach ($versions as $v) {
                        $testPath = $path . '\\' . $v . '\\bin\\mysqldump.exe';
                        if (file_exists($testPath)) {
                            $mysqldump = '"' . $testPath . '"';
                            break 2;
                        }
                    }
                }
            }
        }

        $passFlag = !empty($pass) ? "--password=\"{$pass}\"" : "";
        
        $excluded = $this->getExcludedTables();
        $ignoreFlag = "";
        foreach ($excluded as $exTable) {
            $ignoreFlag .= "--ignore-table={$dbName}.{$exTable} ";
        }

        // Command to be executed
        $command = "{$mysqldump} --user={$user} {$passFlag} --host={$host} --port={$port} {$ignoreFlag} {$dbName} > \"{$fullName}\" 2>&1";
        
        exec($command, $output, $returnVar);

        if ($returnVar !== 0) {
            $errorMsg = !empty($output) ? implode(' ', $output) : "Unknown error (code: {$returnVar})";
            if (str_contains($errorMsg, 'not recognized')) {
                $errorMsg = "'mysqldump' is not in PATH. Please add it or install it.";
            }
            return back()->with('error', "Backup failed: {$errorMsg}. [Command: {$command}]");
        }

        return back()->with('success', "Backup '{$fileName}' created successfully!");
    }

    public function downloadBackup($name)
    {
        $path = storage_path('app/backups/' . $name);
        if (file_exists($path)) {
            return response()->download($path);
        }
        return back()->with('error', 'Backup file not found.');
    }

    public function import(Request $request)
    {
        $request->validate([
            'sql_file' => 'required|file|mimes:sql,txt'
        ]);

        $file = $request->file('sql_file');
        $dbName = DB::connection()->getDatabaseName();
        $host = config("database.connections.mysql.host", '127.0.0.1');
        $user = config("database.connections.mysql.username", 'root');
        $pass = config("database.connections.mysql.password", '');
        $port = config("database.connections.mysql.port", '3306');

        $mysql = 'mysql';
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $drive = substr(base_path(), 0, 2);
            $searchPaths = [$drive . '\\laragon\\bin\\mysql', 'C:\\laragon\\bin\\mysql'];
            foreach ($searchPaths as $path) {
                if (is_dir($path)) {
                    $versions = array_diff(scandir($path, SCANDIR_SORT_DESCENDING), ['.', '..']);
                    foreach ($versions as $v) {
                        $testPath = $path . '\\' . $v . '\\bin\\mysql.exe';
                        if (file_exists($testPath)) {
                            $mysql = '"' . $testPath . '"';
                            break 2;
                        }
                    }
                }
            }
        }

        $passFlag = !empty($pass) ? "--password=\"{$pass}\"" : "";
        $tempPath = $file->getRealPath();
        
        // Command to import: mysql -u user -p pass -h host -P port db < file
        $command = "{$mysql} --user={$user} {$passFlag} --host={$host} --port={$port} {$dbName} < \"{$tempPath}\" 2>&1";

        exec($command, $output, $returnVar);

        if ($returnVar !== 0) {
            $errorMsg = !empty($output) ? implode(' ', $output) : "Unknown error (code: {$returnVar})";
            return back()->with('error', "Import failed: {$errorMsg}");
        }

        return back()->with('success', "Database restored successfully from '{$file->getClientOriginalName()}'");
    }

    public function restoreBackup($name)
    {
        $backupPath = storage_path('app/backups/' . $name);
        
        if (!file_exists($backupPath)) {
            return back()->with('error', "Backup file not found.");
        }

        $dbName = DB::connection()->getDatabaseName();
        $host = config("database.connections.mysql.host", '127.0.0.1');
        $user = config("database.connections.mysql.username", 'root');
        $pass = config("database.connections.mysql.password", '');
        $port = config("database.connections.mysql.port", '3306');

        $mysql = 'mysql';
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $drive = substr(base_path(), 0, 2);
            $searchPaths = [$drive . '\\laragon\\bin\\mysql', 'C:\\laragon\\bin\\mysql'];
            foreach ($searchPaths as $path) {
                if (is_dir($path)) {
                    $versions = array_diff(scandir($path, SCANDIR_SORT_DESCENDING), ['.', '..']);
                    foreach ($versions as $v) {
                        $testPath = $path . '\\' . $v . '\\bin\\mysql.exe';
                        if (file_exists($testPath)) {
                            $mysql = '"' . $testPath . '"';
                            break 2;
                        }
                    }
                }
            }
        }

        $passFlag = !empty($pass) ? "--password=\"{$pass}\"" : "";
        $command = "{$mysql} --user={$user} {$passFlag} --host={$host} --port={$port} {$dbName} < \"{$backupPath}\" 2>&1";

        exec($command, $output, $returnVar);

        if ($returnVar !== 0) {
            $errorMsg = !empty($output) ? implode(' ', $output) : "Unknown error (code: {$returnVar})";
            return back()->with('error', "Restoration failed: {$errorMsg}");
        }

        return back()->with('success', "Database successfully restored to backup version '{$name}'");
    }

    public function deleteBackup($name)
    {
        $path = storage_path('app/backups/' . $name);
        if (file_exists($path)) {
            unlink($path);
            return back()->with('success', 'Backup deleted successfully.');
        }
        return back()->with('error', 'Backup file not found.');
    }

    private function getDatabaseSize()
    {
        try {
            if (config('database.default') === 'mysql') {
                $dbName = DB::connection()->getDatabaseName();
                $size = DB::select("SELECT SUM(data_length + index_length) / 1024 / 1024 AS size FROM information_schema.TABLES WHERE table_schema = ?", [$dbName]);
                return round($size[0]->size ?? 0, 2) . ' MB';
            }
        } catch (\Exception $e) {}
        return 'N/A';
    }

    private function getLastBackupDate()
    {
        $backupPath = storage_path('app/backups');
        if (is_dir($backupPath)) {
            $files = scandir($backupPath, SCANDIR_SORT_DESCENDING);
            foreach ($files as $file) {
                if (str_ends_with($file, '.sql')) {
                    return date('Y-m-d H:i', filemtime($backupPath . '/' . $file));
                }
            }
        }
        return 'Never';
    }

    public function show(Request $request, $table)
    {
        if (!Schema::hasTable($table)) {
            abort(404, "Table not found");
        }

        $tables = $this->getTables();
        $columns = Schema::getColumnListing($table);
        
        $query = DB::table($table);

        $filters = $request->input('filters', []);
        foreach ($filters as $filter) {
            if (!empty($filter['column']) && !empty($filter['operator'])) {
                $col = $filter['column'];
                $op = $filter['operator'];
                $val = $filter['value'] ?? '';

                if ($op === 'LIKE') {
                    $query->where($col, 'LIKE', '%' . $val . '%');
                } else if ($op === 'IN') {
                    $query->whereIn($col, explode(',', trim($val)));
                } else if ($op === 'IS NULL') {
                    $query->whereNull($col);
                } else if ($op === 'IS NOT NULL') {
                    $query->whereNotNull($col);
                } else {
                    $query->where($col, $op, $val);
                }
            }
        }

        $rows = $query->paginate(20)->appends($request->all());

        $primaryKey = $this->getPrimaryKey($table);
        if (!$primaryKey) {
            $primaryKey = isset($columns[0]) ? $columns[0] : null;
        }

        $columnTypes = [];
        foreach ($columns as $col) {
            try {
                $columnTypes[$col] = Schema::getColumnType($table, $col);
            } catch (\Exception $e) {
                $columnTypes[$col] = 'string';
            }
        }

        $allTables = $tables; // For layout consistency

        return view('database-controllers::show', compact('tables', 'allTables', 'table', 'columns', 'rows', 'filters', 'primaryKey', 'columnTypes'));
    }

    public function store(Request $request, $table)
    {
        if (!Schema::hasTable($table)) {
            return back()->with('error', 'Table not found.');
        }

        $data = $request->except('_token', '_method', 'filters', 'page');
        $columns = Schema::getColumnListing($table);

        // Automatic Timestamp Handling
        $now = now();
        if (in_array('created_at', $columns) && (!isset($data['created_at']) || empty($data['created_at']))) {
            $data['created_at'] = $now;
        }
        if (in_array('updated_at', $columns) && (!isset($data['updated_at']) || empty($data['updated_at']))) {
            $data['updated_at'] = $now;
        }

        // Automatic Password Hashing
        if (isset($data['password']) && !empty($data['password'])) {
            $data['password'] = \Illuminate\Support\Facades\Hash::make($data['password']);
        }
        
        DB::table($table)->insert($data);

        return back()->with('success', 'Row created successfully!');
    }

    public function update(Request $request, $table, $id)
    {
         if (!Schema::hasTable($table)) {
            return back()->with('error', 'Table not found.');
        }

        $data = $request->except('_token', '_method', 'filters', 'page');
        $columns = Schema::getColumnListing($table);
        $primaryKey = $this->getPrimaryKey($table) ?? 'id';

        // Automatic Timestamp Handling
        if (in_array('updated_at', $columns) && (!isset($data['updated_at']) || empty($data['updated_at']))) {
            $data['updated_at'] = now();
        }

        // Automatic Password Hashing & Handling
        if (isset($data['password'])) {
            if (!empty($data['password'])) {
                $data['password'] = \Illuminate\Support\Facades\Hash::make($data['password']);
            } else {
                unset($data['password']); // Don't nullify existing password if input is empty
            }
        }

        DB::table($table)->where($primaryKey, $id)->update($data);

        return back()->with('success', 'Row updated successfully!');
    }

    public function destroy($table, $id)
    {
        if (!Schema::hasTable($table)) {
            return back()->with('error', 'Table not found.');
        }
        
        $primaryKey = $this->getPrimaryKey($table) ?? 'id';
        DB::table($table)->where($primaryKey, $id)->delete();

        return back()->with('success', 'Row deleted successfully!');
    }

    private function getPrimaryKey($table)
    {
        try {
            if (config('database.default') === 'sqlite') {
                // SQLite specific
                $columns = DB::select("PRAGMA table_info({$table})");
                foreach ($columns as $column) {
                    if ($column->pk == 1) {
                        return $column->name;
                    }
                }
            } else {
                $key = DB::select("SHOW KEYS FROM {$table} WHERE Key_name = 'PRIMARY'");
                if (count($key) > 0) {
                    return $key[0]->Column_name;
                }
            }
        } catch (\Exception $e) {}
        
        return 'id'; // fallback
    }
}
