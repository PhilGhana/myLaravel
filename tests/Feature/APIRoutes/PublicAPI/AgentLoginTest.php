<?php
namespace Tests\Feature\APIRoutes\PublicAPI;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

use App\Models\Agent;



/**
 * 登入測試
 * ./vendor/bin/phpunit Tests/Feature/APIRoutes/PublicAPI/
 */
class AgentLoginTest extends TestCase
{

    const API_LOGIN = '/api/public/login';

    protected function generatorTestAgent() {

        $testAgent = Agent::where('account', 'testAgent')->first();
        if (!$testAgent) {
            $testAgent = new Agent();
            $testAgent->name = 'testAgent';
            $testAgent->account = 'testAgent';
            $testAgent->setPassword('testAgent');
            $testAgent->role_id = 1;
            $testAgent->enabled = 1;
            $testAgent->locked = 0;
            $testAgent->level = 0;
            $testAgent->extend_id = 0;
            $testAgent->lv1 = 0;
            $testAgent->lv2 = 0;
            $testAgent->lv3 = 0;
            $testAgent->lv4 = 0;
            $testAgent->generatorInvitationCode();
            $testAgent->error_count = 0;
            $testAgent->save();
        }
        return $testAgent;

    }

    /**
     * A basic test example.
     *
     * @return void
     */
    public function login()
    {
        $agent = $this->generatorTestAgent();

        $agent->enabled = 0;
        $agent->save();

        $res = $this->call('POST', static::API_LOGIN, [
        ]);
        $this->assertEquals(200, $res->status());
    }

    public function testInvalidAccountOrPassword()
    {
        $agent = $this->generatorTestAgent();
        $data = [
            'account' => 'not-exists-account',
            'password' => 'error-password',
        ];

        $this->post(static::API_LOGIN, $data)
            ->assertStatus(400)
            ->assertJson([
                'message' => 'login-error.not-found',
            ]);

        $data = [
            'account' => $agent->account,
            'password' => 'error-password'
        ];
        $this->post(static::API_LOGIN, $data)
            ->assertStatus(400)
            ->assertJson([
                'message' => 'login-error.not-found',
            ]);
    }

    public function testWhenDisabled()
    {
        $password = 'test-password';
        $agent = $this->generatorTestAgent();
        $agent->setPassword($password);
        $agent->enabled = 0;
        $agent->save();
        $data = [
            'account' => $agent->account,
            'password' => $password
        ];
        $this->post(static::API_LOGIN, $data)
            ->assertStatus(400)
            ->assertJson([
                'message' => 'login-error.disabled',
            ]);
    }
    public function testLoginSuccess()
    {
        $password = 'test-password';
        $agent = $this->generatorTestAgent();
        $agent->setPassword($password);
        $agent->enabled = 1;
        $agent->locked = 1;
        $agent->save();
        $data = [
            'account' => $agent->account,
            'password' => $password
        ];
        $this->post(static::API_LOGIN, $data)
            ->assertStatus(200);
    }
}
