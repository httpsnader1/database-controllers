<?php

namespace Httpsnader1\DatabaseControllers\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Demonstrates the best way to ensure no errors for large raw data imports:
 * - Chunking
 * - Transactions
 * - Logging
 */
class ChunkedImportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        protected string $table,
        protected array $data
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            DB::beginTransaction();

            // Use bulk insert for performance
            DB::table($this->table)->insert($this->data);

            DB::commit();

            Log::info("Successfully imported chunk of " . count($this->data) . " rows into '{$this->table}' table.");
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Failed to import chunk into '{$this->table}': " . $e->getMessage());
            // Rethrowing will let the job be retried if configured
            throw $e;
        }
    }
}
