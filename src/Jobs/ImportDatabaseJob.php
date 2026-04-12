<?php

namespace Httpsnader1\DatabaseControllers\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ImportDatabaseJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 3600; // 1 hour timeout for very large imports

    public function __construct(
        protected string $sqlPath,
        protected string $dbName,
        protected string $host,
        protected string $user,
        protected string $pass,
        protected string $port,
        protected ?string $tempDirToDelete = null
    ) {}

    public function handle()
    {
        Log::info("Starting background database import from: {$this->sqlPath}");

        $exitCode = Artisan::call('db:restore', [
            'path' => $this->sqlPath,
            '--db' => $this->dbName,
            '--host' => $this->host,
            '--user' => $this->user,
            '--pass' => $this->pass,
            '--port' => $this->port,
            '--drop-tables' => true,
        ]);

        if ($this->tempDirToDelete && is_dir($this->tempDirToDelete)) {
            $this->recursiveRmdir($this->tempDirToDelete);
        } else if (file_exists($this->sqlPath) && str_contains($this->sqlPath, 'temp_imports')) {
            // If it's a specific temp file we moved
            unlink($this->sqlPath);
        }

        if ($exitCode !== 0) {
            $output = Artisan::output();
            Log::error("Background import failed: " . $output);
            throw new \Exception("Import failed: " . $output);
        }

        Log::info("Background database import completed successfully.");
    }

    private function recursiveRmdir($dir)
    {
        if (is_dir($dir)) {
            $objects = scandir($dir);
            foreach ($objects as $object) {
                if ($object != "." && $object != "..") {
                    if (is_dir($dir . DIRECTORY_SEPARATOR . $object) && !is_link($dir . DIRECTORY_SEPARATOR . $object))
                        $this->recursiveRmdir($dir . DIRECTORY_SEPARATOR . $object);
                    else
                        unlink($dir . DIRECTORY_SEPARATOR . $object);
                }
            }
            rmdir($dir);
        }
    }
}
