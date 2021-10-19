<?php

namespace Tests\Feature\APIRoutes\Letter;

use Illuminate\Foundation\Testing\DatabaseTransactions;
// use Illuminate\Foundation\Testing\WithoutMiddleware;
use Tests\TestCase;
use App\Models\LetterMessage;
use App\Models\Member;



/**
 * 站內信 - 訊息 (letter-message)
 */
class LetterMessageTest extends TestCase
{
    use DatabaseTransactions;
    // use WithoutMiddleware;

    const API_LOGIN = '/api/public/login';
    const API_SEND = '/api/letter-message/send';
    const API_REPLY = '/api/letter-message/reply';
    const API_READ = '/api/letter-message/read';
    const API_LIST = '/api/letter-message/list';

    /**
     * send
     *
     * @return void
     */
    public function testSend()
    {
        // login
        $loginData = [
            'account' => 'chloe',
            'password' => 'chloe'
        ];
        $this->post(static::API_LOGIN, $loginData)
            ->assertStatus(200);

        // ---------------- error ----------------

        $data = [];
        $this->post(static::API_SEND, $data)
            ->assertStatus(400)
            ->assertExactJson([
                'errors' => [
                    'memberId' => ['The member id field is required.'],
                    'title' => ['The title field is required.'],
                    'content' => ['The content field is required.']
                ],
                'message' => 'fail'
            ]);

        // ---------------- success ----------------

        $data = [
            'memberId' => Member::all()->random()->id,
            'title' => 'xxxxxxxxxxxxxxxx',
            'content' => 'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx'
        ];
        $this->post(static::API_SEND, $data)
            ->assertStatus(204);

        $data = [
            'memberId' => 3,
            'title' => 'xxxxxxxxxxxxxxxx',
            'content' => 'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx'
        ];
        $this->post(static::API_SEND, $data)
            ->assertStatus(204);
    }

    /**
     * reply
     *
     * @return void
     */
    public function testReply()
    {
        // login
        $loginData = [
            'account' => 'chloe',
            'password' => 'chloe'
        ];
        $this->post(static::API_LOGIN, $loginData)
            ->assertStatus(200);

        // ---------------- error ----------------

        $data = [];
        $this->post(static::API_REPLY, $data)
            ->assertStatus(400)
            ->assertExactJson([
                'errors' => [
                    'letterId' => ['The letter id field is required.'],
                    'memberId' => ['The member id field is required.'],
                    'title' => ['The title field is required.'],
                    'content' => ['The content field is required.']
                ],
                'message' => 'fail'
            ]);


        $data = [
            'letterId' => 9999,
            'memberId' => 9999,
            'title' => 'xxxxxxxx',
            'content' => 'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx'
        ];
        $this->post(static::API_REPLY, $data)
            ->assertStatus(400)
            ->assertExactJson([
                'errors' => [
                    'letterId' => ['The selected letter id is invalid.'],
                    'memberId' => ['The selected member id is invalid.']
                ],
                'message' => 'fail'
            ]);

        // ---------------- success ----------------

        $letterMessage = LetterMessage::all()->random();
        $data = [
            'letterId' => $letterMessage->id,
            'memberId' => $letterMessage->member_id,
            'title' => 'xxxxxxxx',
            'content' => 'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx'
        ];
        $this->post(static::API_REPLY, $data)
            ->assertStatus(204);


        // 新增測試資料
        $letterMessage = new LetterMessage();
        $letterMessage->from = 'agent';
        $letterMessage->agent_id = user()->model()->id;
        $letterMessage->member_id = 1;
        $letterMessage->title = 'xxxxxxxxxxxx';
        $letterMessage->content = 'xxxxxxxxxxxxxxxxxx';
        $letterMessage->reply_count = 0;
        $letterMessage->saveOrError();

        $letterMessage = $letterMessage->fresh();

        // test
        $data = [
            'letterId' => $letterMessage->id,
            'memberId' => $letterMessage->member_id,
            'title' => 'xxx',
            'content' => 'xxxxxxx'
        ];
        $this->post(static::API_REPLY, $data)
            ->assertStatus(204);
    }

    public function testRead()
    {
        // login
        $loginData = [
            'account' => 'chloe',
            'password' => 'chloe'
        ];
        $this->post(static::API_LOGIN, $loginData)
            ->assertStatus(200);

        // ---------------- error ----------------
        $data = [];
        $this->post(static::API_READ, $data)
            ->assertStatus(400)
            ->assertExactJson([
                'message' => 'not found'
            ]);

        $data = [
            'id' => 9999999
        ];
        $this->post(static::API_READ, $data)
            ->assertStatus(400)
            ->assertExactJson([
                'message' => 'not found'
            ]);

        // ---------------- success ----------------

        $letterMessage = LetterMessage::all()->random();
        $data = [
            'id' => $letterMessage->id
        ];

        $this->post(static::API_READ, $data)
            ->assertStatus(200);

    }

    public function testList()
    {

        // login
        $loginData = [
            'account' => 'chloe',
            'password' => 'chloe'
        ];
        $this->post(static::API_LOGIN, $loginData)
            ->assertStatus(200);

        // ---------------- error ----------------

        $data = [
            'sorts[]' => '321',
            'page' => 'xxxx',
            'perPage' => 'xxxx'
        ];
        $this->call('GET', static::API_LIST, $data)
            ->assertStatus(400)
            ->assertExactJson([
                'errors' => [
                    'perPage' => ['The per page must be a number.']
                ],
                'message' => 'fail'
            ]);

        // ---------------- success ----------------

        $data = [];
        $this->call('GET', static::API_LIST, $data)
            ->assertStatus(200);

        $data = [
            'sorts[]' => 'id,desc',
            'page' => 3,
            'perPage' => 2
        ];
        $this->call('GET', static::API_LIST, $data)
            ->assertStatus(200);
    }
}
