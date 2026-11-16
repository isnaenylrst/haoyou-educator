<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\DB;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $connection = config('database.default');
        $database = config("database.connections.{$connection}.database");

        $isMemory = ($database === ':memory:');
        $isTestDb = is_string($database) && (str_ends_with($database, '_test') || str_contains($database, '_test.'));

        if (!$isMemory && !$isTestDb) {
            throw new RuntimeException(
                "CRITICAL: Test attempted to run against non-test database! Connection: {$connection}, Database: {$database}. Execution halted."
            );
        }
    }
}
