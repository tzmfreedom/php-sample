<?php

if (!function_exists('typed_template')) {
    function typed_template(string $template, array $data = [])
    {
        return app('typed-template')->render($template, $data);
    }
}

if (!function_exists('typed_view')) {
    function typed_view(string $template, array $data = [])
    {
        return app('typed-template')->make($template, $data);
    }
}

if (!function_exists('typed_blade')) {
    function typed_blade(string $view, array $data = [], array $mergeData = [])
    {
        return app('typed-view')->make($view, $data, $mergeData);
    }
}

if (!function_exists('render_typed_blade')) {
    function render_typed_blade(string $view, array $data = [], array $mergeData = [])
    {
        return app('typed-view')->make($view, $data, $mergeData)->render();
    }
}