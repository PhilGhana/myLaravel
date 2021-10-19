<?php

namespace App\Observers;

use App\Models\Carousel;

class CarouselObserver extends BaseObserver
{

    protected function getName(\App\Models\BaseModel $model, string $action)
    {
        /** @var Carousel $carousel */
        $carousel = $model;

        return $carousel->name;
    }
    public function deleting(\App\Models\BaseModel $model, $data = null)
    {
        /** @var Carousel $carousel */
        $carousel = $model;
        $data = [
            'position' => $carousel->position,
            'name' => $carousel->name,
            'image' => $carousel->image,
            'mobile_image' => $carousel->mobile_image,
            'link_type' => $carousel->link_type,
            'platform_id' => $carousel->platform_id,
            'game_id' => $carousel->game_id,
        ];
        parent::deleting($model, $data);
    }
}
