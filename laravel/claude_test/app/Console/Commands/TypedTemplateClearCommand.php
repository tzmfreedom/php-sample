<?php

namespace App\Console\Commands;

use App\TypedTemplate\TypedTemplateEngine;
use Illuminate\Console\Command;

class TypedTemplateClearCommand extends Command
{
    protected $signature = 'typed-template:clear';
    
    protected $description = 'Clear all compiled typed templates';

    public function handle(TypedTemplateEngine $engine): int
    {
        $engine->flushCache();
        
        $this->info('Typed template cache cleared!');
        
        return 0;
    }
}