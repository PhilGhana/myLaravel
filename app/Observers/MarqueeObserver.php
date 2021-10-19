<?php

namespace App\Observers;

use App\Models\Marquee;

class MarqueeObserver extends BaseObserver
{

    protected function getName(\App\Models\BaseModel $model, string $action)
    {
        /** @var Marquee $marquee */
        $marquee = $model;

        return $marquee->content;
    }
    public function deleting(\App\Models\BaseModel $model, $data = null)
    {
        /** @var Marquee $marquee */
        $marquee = $model;
        $data = [
            'content' => $marquee->content,
            'suitable' => $marquee->suitable,
            'type' => $marquee->type,
        ];
        parent::deleting($model, $data);
    }
}
