<?php

namespace Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    /**
     * Indicates whether the default seeder should run before each test.
     */
    protected bool $seed = false;

    /**
     * Setup for each test.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Safety check: Verify we're using test database (never run against production)
        $currentDatabase = config('database.connections.mysql.database');
        $this->assertNotEquals(
            'opencorebsdb',
            $currentDatabase,
            "SAFETY ERROR: Tests must not run against production database! Current: {$currentDatabase}"
        );
    }
}
