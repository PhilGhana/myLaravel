<?php

namespace Tests\Feature\APIRoutes\Letter;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Tests\TestCase;
use App\Models\LetterTag;

/**
 * 站內信類型設定測試
 */
class letterTagTest extends TestCase
{
    use DatabaseTransactions;
    use WithoutMiddleware;

    const API_ADD = '/api/letter-tag/add';
    const API_EDIT = '/api/letter-tag/edit';
    const API_TOGGLE_ENABLED = '/api/letter-tag/toggle-enabled';
    const API_LIST = '/api/letter-tag/list';

    /**
     * addTag
     *
     * @return void
     */
    public function testAdd()
    {
        // ----------------- error -----------------
        $data = [];
        $this->post(static::API_ADD, $data)
            ->assertStatus(400)
            ->assertExactJson([
                'errors' => [
                    'type' => ['The type field is required.'],
                    'name' => ['The name field is required.'],
                    'enabled' => ['The enabled field is required.']
                ],
                'message' => 'fail'
            ]);

        $data = [
            'type' => 'xxxx',
            'name' => 'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx',
            'enabled' => 99
        ];
        $this->post(static::API_ADD, $data)
            ->assertStatus(400)
            ->assertExactJson([
                'errors' => [
                    'type' => ['The selected type is invalid.'],
                    'name' => ['The name may not be greater than 10 characters.'],
                    'enabled' => ['The selected enabled is invalid.']
                ],
                'message' => 'fail'
            ]);

        // ----------------- success -----------------
        $data = [
            'type' => 'system',
            'name' => 'nameeeeee',
            'enabled' => 1
        ];
        $this->post(static::API_ADD, $data)
            ->assertStatus(200);
    }

    /**
     * editTag
     *
     * @return void
     */
    public function testEdit()
    {

        // ----------------- error -----------------

        $data = [];
        $this->post(static::API_EDIT, $data)
            ->assertStatus(400)
            ->assertExactJson([
                'errors' => [
                    'id' => ['The id field is required.'],
                    'type' => ['The type field is required.'],
                    'name' => ['The name field is required.'],
                    'enabled' => ['The enabled field is required.']
                ],
                'message' => 'fail'
            ]);

        $data = [
            'id' => 999,
            'type' => 'xxxxxxx',
            'name' => 'xxxxxxxxxxxxxxxx',
            'enabled' => 99
        ];
        $this->post(static::API_EDIT, $data)
            ->assertStatus(400)
            ->assertExactJson([
                'errors' => [
                    'id' => ['The selected id is invalid.'],
                    'type' => ['The selected type is invalid.'],
                    'name' => ['The name may not be greater than 10 characters.'],
                    'enabled' => ['The selected enabled is invalid.']
                ],
                'message' => 'fail'
            ]);

        // ----------------- success -----------------

        // 新增一筆測試資料
        $letterTag = new LetterTag();
        $letterTag->type = 'announcement';
        $letterTag->name = 'nameeeee';
        $letterTag->enabled = 1;
        $letterTag->saveOrError();

        // 修改測試資料
        $type = 'system';
        $name = 'testName';
        $enabled = 0;
        $editData = [
            'id' => $letterTag->id,
            'type' => $type,
            'name' => $name,
            'enabled' => $enabled
        ];
        $this->post(static::API_EDIT, $editData)
            ->assertStatus(200);

        // 檢查
        $letterTag = $letterTag->fresh();
        $this->assertEquals($letterTag->type, $type);
        $this->assertEquals($letterTag->name, $name);
        $this->assertEquals($letterTag->enabled, $enabled);

    }

    /**
     * toggleEnable
     *
     * @return void
     */
    public function testToggleEnable()
    {

        // ----------------- error -----------------

        $data = [];
        $this->post(static::API_TOGGLE_ENABLED, $data)
            ->assertStatus(400)
            ->assertExactJson([
                'errors' => [
                    'id' => ['The id field is required.'],
                    'enabled' => ['The enabled field is required.']
                ],
                'message' => 'fail'
            ]);

        $data = [
            'id' => 999,
            'enabled' => 99
        ];
        $this->post(static::API_TOGGLE_ENABLED, $data)
            ->assertStatus(400)
            ->assertExactJson([
                'errors' => [
                    'id' => ['The selected id is invalid.'],
                    'enabled' => ['The selected enabled is invalid.']
                ],
                'message' => 'fail'
            ]);

        // ----------------- success -----------------

        $id = 4;
        $enabled = 1;
        $data = [
            'id' => $id,
            'enabled' => $enabled
        ];
        $this->post(static::API_TOGGLE_ENABLED, $data)
            ->assertStatus(200);

        $letterTag = LetterTag::findOrError($id);
        $this->assertEquals($letterTag->enabled, $enabled);

    }

    public function testList()
    {

        // ----------------- error -----------------

        $data = [
            'type' => 'xxx',
            'enabled' => 9
        ];
        $this->call('GET', static::API_LIST, $data)
            ->assertStatus(400)
            ->assertExactJson([
                'errors' => [
                    'type' => ['The selected type is invalid.'],
                    'enabled' => ['The selected enabled is invalid.']
                ],
                'message' => 'fail'
            ]);


        // ----------------- success -----------------

        $data = [];
        $this->call('GET', static::API_LIST, $data)
            ->assertStatus(200);

        $data = [
            'type' => 'announcement',
            'enabled' => -1
        ];
        $this->call('GET', static::API_LIST, $data)
            ->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'content' => [
                        [
                            'id',
                            'type',
                            'name',
                            'enabled',
                            'updatedAt'
                        ]
                    ],
                    'page',
                    'perPage',
                    'total'
                ]
            ]);


    }
}
