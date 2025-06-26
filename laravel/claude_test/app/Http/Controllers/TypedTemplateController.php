<?php

namespace App\Http\Controllers;

use App\TypedTemplate\TypedTemplateEngine;
use Illuminate\Http\Request;

class TypedTemplateController extends Controller
{
    private TypedTemplateEngine $engine;

    public function __construct(TypedTemplateEngine $engine)
    {
        $this->engine = $engine;
    }

    public function example()
    {
        $data = [
            'title' => 'Typed Template Example',
            'message' => 'This is a typed template example!',
            'users' => [
                ['name' => 'John Doe', 'email' => 'john@example.com'],
                ['name' => 'Jane Smith', 'email' => 'jane@example.com'],
            ],
            'count' => 2,
        ];

        return $this->engine->render('example', $data);
    }

    public function typeMismatchExample()
    {
        try {
            $data = [
                'title' => 'Type Mismatch Example',
                'message' => 'This should fail type checking',
                'users' => 'not an array',
                'count' => 'not a number',
            ];

            return $this->engine->render('example', $data);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'type' => get_class($e)
            ], 400);
        }
    }
}