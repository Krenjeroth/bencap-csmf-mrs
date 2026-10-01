<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Seed reference data (permission catalog, System Administrator and
     * Admin roles) once when RefreshDatabase migrates; each test then runs
     * inside a rolled-back transaction.
     */
    protected bool $seed = true;
}
