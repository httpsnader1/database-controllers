<?php

namespace Httpsnader1\DatabaseControllers\Console\Commands;

use Httpsnader1\DatabaseControllers\Jobs\ChunkedImportJob;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;

class ChunkedImportCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * Example: php artisan import:raw my_table data.csv
     */
    protected $signature = 'import:raw
                            {table : The table name to import into}
                            {file : The path to the CSV file (simulated here)}';

    /**
     * The console command description.
     */
    protected $description = 'Demonstrate how to import large raw data using chunked jobs and batching for reliability.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $table = $this->argument('table');
        $this->info("Starting chunked import for table: {$table}");

        // In a real app, you would read a file. Here we simulate 10,000 rows.
        $totalRows = 10000;
        $chunkSize = 1000;
        $this->info("Processing {$totalRows} rows in chunks of {$chunkSize}...");

        $batch = Bus::batch([])->name("Import into {$table}")->dispatch();

        for ($i = 0; $i < $totalRows; $i += $chunkSize) {
            $chunk = [];
            for ($j = 0; $j < $chunkSize; $j++) {
                $chunk[] = [
                    'name' => 'Name ' . ($i + $j),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            $batch->add(new ChunkedImportJob($table, $chunk));
            $this->output->write('.');
        }

        $this->newLine();
        $this->info("Import batch [{$batch->id}] created and dispatched.");
        $this->info("You can monitor its status in the job_batches table.");

        return self::SUCCESS;
    }
}
