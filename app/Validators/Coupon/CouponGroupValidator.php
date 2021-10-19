<?php
namespace App\Validators\Coupon;

use App\Validators\BaseValidator;
use App\Validators\ExpansionRules\SortArray;

class CouponGroupValidator extends BaseValidator
{


    public static function checkAdd($data)
    {
        return (new static($data, [
            'name' => 'required|string|max:50',
            'enabled' => 'required|in:1,0',
            'order' => 'required|integer|min:0',
            'image' => 'nullable|image',
        ]))->check();
    }

    public static function checkEdit($data)
    {
        return (new static($data, [
            'id' => 'required|exists:coupon_group,id',
            'name' => 'required|string|max:50',
            'enabled' => 'required|in:1,0',
            'order' => 'required|integer|min:0',
            'image' => 'nullable|image',
        ]))->check();
    }

    public static function checkList($data)
    {
        return (new static($data, [
            'enabled' => 'required|in:1,0,-1',
            'page' => 'nullable|integer|min:1',
            'perPage' => 'nullable|integer|min:1',
            'sorts' => ['nullable', 'array', new SortArray(['id', 'order', 'updatedAt', 'createdAt'])],
        ]))->check();
    }

}
