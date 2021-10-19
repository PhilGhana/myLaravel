<?php
namespace App\Validators\Review;

use App\Validators\BaseValidator;
use App\Validators\ExpansionRules\SortArray;

class ReviewBaseValidator extends BaseValidator
{
    public static function checkGetList($data)
    {
        (new static ($data, [
            'page'      => 'nullable|integer|min:1',
            'perPage'   => 'nullable|integer|min:1'
        ]))->check();
    }
}
