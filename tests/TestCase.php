<?php
namespace Tests;

use PHPUnit\Framework\TestCase as PHPUnitTestCase;

/**
 * Base test case for all Farkle tests.
 *
 * Provides common setup, teardown, and utility methods
 * for unit and integration tests.
 */
abstract class TestCase extends PHPUnitTestCase
{
    /**
     * Set up before each test
     */
    protected function setUp(): void
    {
        parent::setUp();
    }

    /**
     * Tear down after each test
     */
    protected function tearDown(): void
    {
        parent::tearDown();
    }
}
