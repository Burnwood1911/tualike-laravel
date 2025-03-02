<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class MigrateFilesToR2 extends Command
{
    protected $signature = 'files:migrate-to-r2';
    protected $description = 'Migrate files from Minio to Cloudflare R2';

    public function handle()
    {
        $this->info('Starting migration of files from Minio to Cloudflare R2...');

        // Get all files from Minio
        $files = Storage::disk('minio')->allFiles();
        $bar = $this->output->createProgressBar(count($files));
        $bar->start();

        $migrated = 0;
        $errors = 0;

        foreach ($files as $file) {
            try {
                // Get the file contents from Minio
                $contents = Storage::disk('minio')->get($file);

                // Store the file in R2
                Storage::disk('r2')->put($file, $contents, 'public');

                $migrated++;
            } catch (\Exception $e) {
                $this->error("Error migrating file {$file}: " . $e->getMessage());
                $errors++;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->info("Migration completed: {$migrated} files migrated, {$errors} errors.");

        return Command::SUCCESS;
    }
}