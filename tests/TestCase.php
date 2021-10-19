<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Artisan;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    public function login($account, $password)
    {
        $res = $this->call('POST', '/api/', [
            'account' => $account,
            'password' => $password,
        ]);
        $this->assertEquals(200, $res->status());
    }

}
