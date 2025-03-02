<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class TestR2Connection extends Command
{
    protected $signature = 'r2:test-connection';
    protected $description = 'Test the connection to Cloudflare R2';

    public function handle()
    {
        $this->info('Testing connection to Cloudflare R2...');

        try {
            // Get R2 configuration
            $config = config('filesystems.disks.r2');
            $this->info('R2 Configuration:');
            $this->info('- Driver: ' . $config['driver']);
            $this->info('- Bucket: ' . $config['bucket']);
            $this->info('- Region: ' . $config['region']);
            $this->info('- Endpoint: ' . $config['endpoint']);
            $this->info('- URL: ' . ($config['url'] ?? 'Not set'));

            // Test listing files
            $this->info('Attempting to list files in R2 bucket...');
            $files = Storage::disk('r2')->allFiles();
            $this->info('Successfully connected to R2!');
            $this->info('Found ' . count($files) . ' files in the bucket.');

            if (count($files) > 0) {
                $this->info('First 5 files:');
                foreach (array_slice($files, 0, 5) as $file) {
                    $this->info('- ' . $file);
                }
            }

            // Test creating a test file
            $this->info('Attempting to create a test file in R2...');
            $testContent = 'This is a test file created at ' . now();
            $testFilename = 'test-' . time() . '.txt';

            Storage::disk('r2')->put($testFilename, $testContent, 'public');
            $this->info('Successfully created test file: ' . $testFilename);

            // Test reading the test file
            $this->info('Attempting to read the test file from R2...');
            $content = Storage::disk('r2')->get($testFilename);
            $this->info('Successfully read test file. Content: ' . $content);

            // Test deleting the test file
            $this->info('Attempting to delete the test file from R2...');
            Storage::disk('r2')->delete($testFilename);
            $this->info('Successfully deleted test file.');

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Error connecting to R2: ' . $e->getMessage());
            $this->error('Stack trace: ' . $e->getTraceAsString());
            return Command::FAILURE;
        }
    }
}