<?php

namespace Tests\Feature\Controllers;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class IndexControllerTest extends TestCase
{
    /**
     * A basic test example.
     *
     * @return void
     */
    public function testLogin()
    {
        $this->visit('/api/public/login')
            ->type('ivan', 'account')
            ->type('xxx', 'password')
            ->seeJson([
                'message' => 'login-error.not-found',
            ]);
        // $this->assertTrue(true);
    }
}
