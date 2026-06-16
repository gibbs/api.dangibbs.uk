<?php

use Illuminate\Support\Facades\File;

/**
 * Load a JSON fixture and return it as an array
 */
function json_fixture(string $name): array
{
    $path = base_path("tests/Fixtures/{$name}");

    if (! File::exists($path)) {
        throw new InvalidArgumentException("Fixture [{$name}] does not exist.");
    }

    return json_decode(File::get($path), true);
}
