<?php

namespace App\Models;

use Spatie\LaravelData\Data;

/** @typescript */
class DemoData extends Data
{
    public function __construct(
        public string $message,
        public bool $success,
    ) {}
}

