<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class UploadCarousel implements Rule
{
    // protected $size = [
    //     'web'    => [
    //         'width'  => 1920,
    //         'height' => 500,
    //     ],
    //     'mobile' => [
    //         'width'  => 400,
    //         'height' => 180,
    //     ],
    // ];
    protected $width;
    protected $height;
    protected $scale;
    protected $type;

    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct($type = 'web')
    {
        $this->type        = $type;
        $config_pc_size    = json_decode(config('app.carousel.image')) ?? [null, null];
        $config_mob_size   = json_decode(config('app.carousel.mobile_image')) ?? [null, null];
        switch ($type) {
            case 'web':
                $this->width  = $config_pc_size[0];
                $this->height = $config_pc_size[1];
                break;

            case 'mobile':
                $this->width  = $config_mob_size[0];
                $this->height = $config_mob_size[1];
                break;

            default:
                $this->width  = 0;
                $this->height = 0;
                break;
        }
        if ($this->width && $this->height) {
            $this->scale = round((float) $this->width / (float) $this->height, 2);
        }
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        list($targetWidth, $targetHeight) = getimagesize($value);
        $targetScale                      = round((float) $targetWidth / (float) $targetHeight, 2);

        // 當ENV有設定比例才檢查方便開發
        if (($this->type == 'web' && ! config('app.carousel.image')) ||
        ($this->type == 'mobile' && ! config('app.carousel.mobile_image'))) {
            return true;
        }

        return $targetScale === $this->scale;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return trans('carousel.illegal-format',
            ['scale' => $this->scale, 'width' => $this->width, 'height' => $this->height]);
    }
}