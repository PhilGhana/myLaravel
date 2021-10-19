<?php
namespace App\Validators\Carousel;

use App\Rules\StringRegex;
use App\Rules\UploadCarousel;
use App\Validators\BaseValidator;

class CarouselValidator extends BaseValidator
{
    private static $checkData = [
        'franchiseeId' => 'nullable|exists:franchisee,id',
        'position'     => 'required|string|in:web-home,web-slot,mobile-home,mobile-slot',
        'name'         => 'required|string',
        'enabled'      => 'required|in:0,1',
        'startTime'    => 'nullable|date',
        'linkType'     => 'required|string|in:none,coupon,link,game',
        'linkUrl'      => 'required_if:linkType,link',
        'couponId'     => 'nullable|required_if:linkType,coupon|exists:coupon,id',
        'gameId'       => 'nullable|required_if:linkType,game|exists:game,id',
        'endTime'      => 'nullable|date',
        'image'        => 'nullable|mimes:jpeg,bmp,png,gif,jpg',
        'imgMobile'    => 'nullable|mimes:jpeg,bmp,png,gif,jpg',
        'order'        => 'required|integer|min:0',
    ];

    public static function checkAddImage($data)
    {
        (new static($data, [
            'franchiseeId' => 'nullable|exists:franchisee,id',
            'position'     => 'required|string|in:web-home,web-slot,mobile-home,mobile-slot',
            'name'         => ['required', 'string'],
            'enabled'      => 'required|in:0,1',
            'startTime'    => 'nullable|date',
            'linkType'     => 'required|string|in:none,coupon,link,game',
            'linkUrl'      => 'required_if:linkType,link',
            // 'couponId'     => 'nullable|required_if:linkType,coupon|exists:coupon,id',
            'gameId'       => 'nullable|required_if:linkType,game|exists:game,id',
            'endTime'      => 'nullable|date',
            'image'        => ['nullable', 'mimes:jpeg,bmp,png,gif,jpg', new UploadCarousel],
            'imgMobile'    => ['nullable', 'mimes:jpeg,bmp,png,gif,jpg', new UploadCarousel('mobile')],
            'order'        => 'required|integer|min:0',
        ]))->check();
    }

    public static function checkEditImage($data)
    {
        (new static($data, [
            'franchiseeId' => 'nullable|exists:franchisee,id',
            'position'     => 'required|string|in:web-home,web-slot,mobile-home,mobile-slot',
            'name'         => ['required', 'string'],
            'enabled'      => 'required|in:0,1',
            'startTime'    => 'nullable|date',
            'linkType'     => 'required|string|in:none,coupon,link,game',
            'linkUrl'      => 'required_if:linkType,link',
            // 'couponId'     => 'nullable|required_if:linkType,coupon|exists:coupon,id',
            'gameId'       => 'nullable|required_if:linkType,game|exists:game,id',
            'endTime'      => 'nullable|date',
            'image'        => ['nullable', 'mimes:jpeg,bmp,png,gif,jpg', new UploadCarousel],
            'imgMobile'    => ['nullable', 'mimes:jpeg,bmp,png,gif,jpg', new UploadCarousel('mobile')],
            'order'        => 'required|integer|min:0',
            'id'           => 'required|exists:carousel,id',
        ]))->check();
    }

    public static function checkToggleEnabled($data)
    {
        (new static($data, [
            'id'      => 'required|exists:carousel,id',
            'enabled' => 'required|in:0,1',
        ]))->check();
    }

    public static function checkGetList($data)
    {
        (new static($data, [
            'position' => 'nullable|string|in:web-home,web-slot,mobile-home,mobile-slot',
            'linkType' => 'nullable|string|in:none,link,game,coupon',
            'enabled'  => 'nullable|in:-1,0,1',
            'page'     => 'nullable|integer|min:1',
            'perPage'  => 'nullable|integer|min:1',
        ]))->check();
    }
}
