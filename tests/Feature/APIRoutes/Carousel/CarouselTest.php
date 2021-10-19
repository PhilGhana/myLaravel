<?php

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Models\Carousel;

class CarouselTest extends TestCase
{
    use WithoutMiddleware;
    use DatabaseTransactions;

    const API_ADD = '/api/carousel/add';
    const API_EDIT = '/api/carousel/edit';
    const API_TOGGLE_ENABLED = '/api/carousel/toggle-enabled';
    const API_LIST = '/api/carousel/list';
    const API_CAROUSEL_OPTIONS = '/api/carousel/carousel-options';
    const API_COUPONS = '/api/carousel/coupons';
    const API_PLATFORMS = '/api/carousel/platforms';
    const API_GAMES = '/api/carousel/games';

    public function testError()
    {
        # add
        $data = [];
        $this->post(static::API_ADD, $data)
            ->assertStatus(400)
            ->assertExactJson([
                'errors' => [
                    'position' => ['The position field is required.'],
                    'name' => ['The name field is required.'],
                    'enabled' => ['The enabled field is required.'],
                    'linkType' => ['The link type field is required.'],
                    'order' => ['The order field is required.'],
                ],
                'message' => 'fail'
            ]);
        $data = [
            'position' => 'xxx',
            'name' => 'xxx',
            'enabled' => 'xxx',
            'startTime' => 'xxx',
            'endTime' => 'xxx',
            'image' => 'xxx',
            'linkType' => 'xxx',
            'order' => 'xxx',
        ];
        $this->post(static::API_ADD, $data)
            ->assertStatus(400)
            ->assertExactJson([
                'errors' => [
                    'position' => ['The selected position is invalid.'],
                    'enabled' => ['The selected enabled is invalid.'],
                    'startTime' => ['The start time does not match the format Y-m-d H:i:s.'],
                    'endTime' => ['The end time does not match the format Y-m-d H:i:s.'],
                    'image' => ['The image must be an image.'],
                    'linkType' => ['The selected link type is invalid.'],
                    'order' => ['The order must be an integer.'],
                ],
                'message' => 'fail'
            ]);

        # edit
        $data = [];
        $this->post(static::API_EDIT, $data)
            ->assertStatus(400)
            ->assertExactJson([
                'errors' => [
                    'id' => ['The id field is required.'],
                    'position' => ['The position field is required.'],
                    'name' => ['The name field is required.'],
                    'enabled' => ['The enabled field is required.'],
                    'linkType' => ['The link type field is required.'],
                    'order' => ['The order field is required.'],
                ],
                'message' => 'fail'
            ]);
        $data = [
            'id' => 'xxx',
            'position' => 'xxx',
            'name' => 'xxx',
            'enabled' => 'xxx',
            'startTime' => 'xxx',
            'endTime' => 'xxx',
            'image' => 'xxx',
            'linkType' => 'xxx',
            'order' => 'xxx',
        ];
        $this->post(static::API_EDIT, $data)
            ->assertStatus(400)
            ->assertExactJson([
                'errors' => [
                    'id' => ['The selected id is invalid.'],
                    'position' => ['The selected position is invalid.'],
                    'enabled' => ['The selected enabled is invalid.'],
                    'startTime' => ['The start time does not match the format Y-m-d H:i:s.'],
                    'endTime' => ['The end time does not match the format Y-m-d H:i:s.'],
                    'image' => ['The image must be an image.'],
                    'linkType' => ['The selected link type is invalid.'],
                    'order' => ['The order must be an integer.'],
                ],
                'message' => 'fail'
            ]);

        # toggle-enabled
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
            'id' => 'xxx',
            'enabled' => 'xxx'
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

        # list
        $data = [
            'position' => 'xxx',
            'linkType' => 'xxx',
            'enabled' => 'xxx',
            'page' => 'xxx',
            'perPage' => 'xxx'
        ];
        $this->call('GET', static::API_LIST, $data)
            ->assertStatus(400)
            ->assertExactJson([
                'errors' => [
                    'position' => ['The selected position is invalid.'],
                    'linkType' => ['The selected link type is invalid.'],
                    'enabled' => ['The selected enabled is invalid.'],
                    'page' => ['The page must be an integer.'],
                    'perPage' => ['The per page must be an integer.']
                ],
                'message' => 'fail'
            ]);
    }
    public function testSuccess()
    {
        # add
        $data = [
            'position' => 'web-home',
            'name' => 'xxx',
            'enabled' => 1,
            'linkType' => 'none',
            'order' => 1
        ];
        $this->post(static::API_ADD, $data)
            ->assertStatus(200);

        $id = Carousel::max('id');

        # edit
        $data = [
            'id' => $id,
            'position' => 'web-home',
            'name' => 'xxx',
            'enabled' => 1,
            'linkType' => 'none',
            'order' => 1
        ];
        $this->post(static::API_EDIT, $data)
            ->assertStatus(200);

        # toggle-enabled
        $data = [
            'id' => $id,
            'enabled' => 1
        ];
        $this->post(static::API_TOGGLE_ENABLED, $data)
            ->assertStatus(200);

        # list
        $data = [];
        $this->call('GET', static::API_LIST, $data)
            ->assertStatus(200);
        $data = [
            'position' => 'web-home',
            'linkType' => 'platform',
            'enabled' => 1,
            'page' => 1,
            'perPage' => 1
        ];
        $this->call('GET', static::API_LIST, $data)
            ->assertStatus(200);

        # carousel-options
        $data = [];
        $this->call('GET', static::API_CAROUSEL_OPTIONS, $data)
            ->assertStatus(200);
        $data = [
            'position' => 'web-home',
        ];
        $this->call('GET', static::API_CAROUSEL_OPTIONS, $data)
            ->assertStatus(200);

        # coupons
        $this->call('GET', static::API_COUPONS)
            ->assertStatus(200);

        # platforms
        $this->call('GET', static::API_PLATFORMS)
            ->assertStatus(200);

        # games
        $data = [];
        $this->call('GET', static::API_GAMES, $data)
        ->assertStatus(200);
        $data = [
            'platformId' => 23
        ];
        $this->call('GET', static::API_GAMES, $data)
        ->assertStatus(200);
    }
}