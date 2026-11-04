<?php

namespace Tests\Unit;

use Tests\Concerns\RefreshMongoDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshMongoDatabase;

    public function test_that_true_is_true()
    {
        $this->assertTrue(true);
    }
}
