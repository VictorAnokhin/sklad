<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class ImportMahaTables extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:import-maha {--path= : Path to the sql file}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Imports maha_v3.sql tables, skipping existing users table';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Try multiple paths (inside container vs outside for local testing)
        $paths = [
            $this->option('path'),
            '/var/www/html/maha_v3.sql', // Expected path if copied into container/project root
            base_path('maha_v3.sql'), 
            '/tmp/maha_v3.sql'
        ];
        
        $sqlPath = null;
        foreach ($paths as $path) {
            if ($path && File::exists($path)) {
                $sqlPath = $path;
                break;
            }
        }
        
        if (!$sqlPath) {
            $this->error("File not found in any of the expected paths.");
            return;
        }

        $this->info("Reading SQL file from: {$sqlPath}");
        
        $this->info("Parsing and filtering SQL...");
        
        $sql = File::get($sqlPath);
        
        // Remove the users table creation and insertion
        // Regex to match CREATE TABLE `users` ... ;
        $sql = preg_replace('/CREATE TABLE `users`.*?(?=-- \n--|CREATE TABLE|INSERT INTO)/is', '', $sql);
        
        // Regex to match INSERT INTO `users` ... ;
        $sql = preg_replace('/INSERT INTO `users`.*?(?=-- \n--|CREATE TABLE|INSERT INTO)/is', '', $sql);
        
        $this->info("Executing filtered SQL (Size: " . strlen($sql) . " bytes)...");
        
        try {
            DB::unprepared($sql);
            $this->info("Database imported successfully!");
        } catch (\Exception $e) {
            $this->error("Import failed: " . $e->getMessage());
        }
    }
}
