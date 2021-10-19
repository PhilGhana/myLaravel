<?php
namespace App\Support;

use Illuminate\Database\Eloquent\Builder AS EloquentBuilder;
use Illuminate\Database\Query\Builder AS QueryBuilder;

class Query
{


    /**
     * 加入 order by 的 query 語句
     *
     * @param EloquentBuilder|QueryBuilder $query
     * @param array $sorts
     * @return void
     */
    public static function orderBy($query, array $sorts = null)
    {
        $sorts = $sorts === null ? request()->input('sorts', []) : $sorts;
        $sorts = (array) $sorts;
        foreach ($sorts as $value) {
            [$col, $by] = explode(',', $value);
            $col = snake_case($col);
            $query->orderBy($col, $by);
        }
        return $query;
    }

    public static function filterFranchisee(Builder $query, $franchiseeCol = 'franchisee_id')
    {
        $user = user()->model();
        $fid = ($user->level > 0)
            ? $user->franchisee_id
            : request()->input('franchiseeId', 0);

        if ($fid) {
            $query->where($franchiseeCol, $fid);
        }
        return $query;
    }
}
