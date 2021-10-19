<?php

namespace App\Providers;

use App\Models\Agent;

// use Illuminate\Support\Facades\Response;
use Closure;
use \Illuminate\Http\Response;
use Illuminate\Database\Eloquent\Builder AS EloquentBuilder;
use Illuminate\Database\Query\Builder AS QueryBuilder;

class ApiResponseServiceProvider extends Response
{
    protected $result = [];

    public function data($data)
    {
        $this->result['data'] = $data;
        $this->setContent($this->result);
        return $this;
    }

    /**
     * Undocumented function
     *
     * @param EloquentBuilder|QueryBuilder $query
     * @param Closure $eachRow
     * @return array
     */
    public function paginate($query, Closure $eachRow = null)
    {
        $res = $query->paginate(intval(request()->input('perPage', 20)) ?: 20);
        return [
            'data' => [
                'content' => $eachRow ? $res->map($eachRow) : $res->items(),
                'page' => $res->currentPage(),
                'total' => $res->total(),
                'perPage' => $res->perPage(),
            ]
        ];
    }


    public function noPaginate($query, Closure $eachRow = null)
    {
        $resfirt = $query->paginate(intval(request()->input('perPage', 20)) ?: 20);
        $res = $query->paginate($resfirt->total());
        return [
            'data' => [
                'content' => $eachRow ? $res->map($eachRow) : $res->items(),
                'page' => $res->currentPage(),
                'total' => $res->total(),
                'perPage' => $res->perPage(),
            ]
        ];
    }

}
