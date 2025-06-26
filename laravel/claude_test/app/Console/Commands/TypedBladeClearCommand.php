<?php

namespace App\Console\Commands;

use App\TypedBlade\Compilers\TypedBladeCompiler;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;

class TypedBladeClearCommand extends Command
{
    protected $signature = 'typed-blade:clear';
    
    protected $description = 'Clear all compiled typed Blade templates';

    public function handle(Filesystem $files): int
    {
        $path = config('typed-blade.compiled_path', storage_path('framework/views/typed-blade'));
        
        if (!$files->exists($path)) {
            $this->info('Typed Blade cache directory does not exist.');
            return 0;
        }

        $files->deleteDirectory($path);
        $files->makeDirectory($path, 0755, true);
        
        $this->info('Typed Blade cache cleared!');
        
        return 0;
    }
}