<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     *
     * @return void
     */
    public function test_example()
    {
        // The site root sends visitors to the resort portal's login page.
        $this->get('/')->assertRedirect('/resort');
        $this->get('/resort')->assertOk();
    }
}
