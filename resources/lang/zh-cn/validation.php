<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines contain the default error messages used by
    | the validator class. Some of these rules have multiple versions such
    | as the size rules. Feel free to tweak each of these messages here.
    |
 */

    'accepted' => ' :attribute 必须同意',
    'active_url' => ' :attribute 不是有效的网址',
    'after' => ' :attribute 必须在 :date 之后',
    'after_or_equal' => ' :attribute 必须等于或大于 :date',
    'alpha' => ' :attribute 仅包含字母',
    'alpha_dash' => ' :attribute 仅包含字母、数字、破折号（ - ）以及底线（ _ ）',
    'alpha_num' => ' :attribute 仅包含字母、数字',
    'array' => ' :attribute 必须为数组',
    'before' => ' :attribute 必须在 :date 之前',
    'before_or_equal' => ' :attribute 必须等于或小于 :date',
    'between' => [
        'numeric' => ' :attribute 必须介于 :min ~ :max 之间',
        'file' => ' :attribute 必须介于 :min ~ :max 个千位元组',
        'string' => ' :attribute 必须介于 :min ~ :max 个字符',
        'array' => ' :attribute 必须介于 :min ~ :max 个元素之间',
    ],
    'boolean' => ' :attribute 必须是 true 或 false',
    'confirmed' => '栏位与 :attribute 不一致',
    'date' => ' :attribute 不是有效日期',
    'date_format' => ' :attribute 不符合 :format 日期格式',
    'different' => ' :attribute 必须与 :other 栏位不同',
    'digits' => ' :attribute 必须为 :digits 位数',
    'digits_between' => ' :attribute 必须介于 :min ~ :max 位数之间',
    'dimensions' => ' :attribute 无效的图片尺寸',
    'distinct' => ' :attribute 栏位有重复的值',
    'email' => ' :attribute 必须是有效的 email 格式',
    'exists' => ' 选定的 :attribute 不存在',
    'file' => ' :attribute 必须是一个档案',
    'filled' => ' :attribute 是必填',
    'gt' => [
        'numeric' => ' :attribute 必须大于 :value',
        'file' => ' :attribute 必须大于 :value 个千位元组',
        'string' => ' :attribute 必须大于 :value 个字符',
        'array' => ' :attribute 必须大于 :value 个元素',
    ],
    'gte' => [
        'numeric' => ' :attribute 必须大于或等于 :value',
        'file' => ' :attribute 必须大于或等于 :value 个千位元组',
        'string' => ' :attribute 必须大于或等于 :value 个字符',
        'array' => ' :attribute 至少包含 :value 个元素',
    ],
    'image' => ' :attribute 档案必须为图片格式（ jpeg、png、bmp、gif、 或 svg ）',
    'in' => ' 选定的 :attribute 是无效的',
    'in_array' => ' :attribute 不存在于 :other',
    'integer' => ' :attribute 必须为整数',
    'ip' => ' :attribute 必须为有效的 ip 位址',
    'ipv4' => ' :attribute 必须为有效的 IPv4 位址',
    'ipv6' => ' :attribute 必须为有效的 iIPv6p 位址',
    'json' => ' :attribute 必须为有效的 JSON 字串',
    'lt' => [
        'numeric' => ' :attribute 必须小于 :value',
        'file' => ' :attribute 必须小于 :value 个千位元组',
        'string' => ' :attribute 必须小于 :value 个字符',
        'array' => ' :attribute 必须少于 :value 个元素',
    ],
    'lte' => [
        'numeric' => ' :attribute 最大为 :value',
        'file' => ' :attribute 最多 :value 个千位元组',
        'string' => ' :attribute 最多 :value 个字符',
        'array' => ' :attribute 最多包含 :value 个元素',
    ],
    'max' => [
        'numeric' => ' :attribute 不可大于 :max.',
        'file' => ' :attribute 不可大于 :max 个千位元组',
        'string' => ' :attribute 不可大于 :max 个字符',
        'array' => ' :attribute 不可超过 :max 个元素',
    ],
    'mimes' => ' :attribute 档案类型必须为: :values',
    'mimetypes' => ' :attribute 档案类型必须为: :values',
    'min' => [
        'numeric' => ' :attribute 至少须为 :min',
        'file' => ' :attribute 至少须 :min 个千位元组',
        'string' => ' :attribute 至少须 :min 个字符',
        'array' => ' :attribute 至少须 :min 个元素',
    ],
    'not_in' => ' 选定的 :attribute 是无效的',
    'not_regex' => ' :attribute 的格式无效',
    'numeric' => ' :attribute 必须是数字',
    'present' => ' :attribute 必须存在',
    'regex' => ' :attribute 格式是无效的',
    'required' => ' :attribute 为必填',
    'required_if' => '当 :other 为 :value 时 :attribute 为必填',
    'required_unless' => ' :attribute 为必填除非 :other 为 :values',
    'required_with' => ' 当 :values 任一个值存在， :attribute 为必填',
    'required_with_all' => '当所有 :values 存在，:attribute 为必填',
    'required_without' => '当 :values 任一个值不存在，:attribute 为必填',
    'required_without_all' => '当所有 :values 都沒有值时，:attribute 为必填',
    'same' => ' :attribute 与 :other 必须相符',
    'size' => [
        'numeric' => ' :attribute 须为 :size',
        'file' => ' :attribute 须为 :size 个千位元组',
        'string' => ' :attribute 须为 :size 个字符',
        'array' => ' :attribute 须包含 :size 个元素',
    ],
    'string' => ' :attribute 必须为字串',
    'timezone' => ' :attribute 必须为有效时区',
    'unique' => ' :attribute 必须是唯一值',
    'uploaded' => ' :attribute 上传失敗',
    'url' => ' :attribute 无效的格式',

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | Here you may specify custom validation messages for attributes using the
    | convention "attribute.rule" to name the lines. This makes it quick to
    | specify a specific custom language line for a given attribute rule.
    |
     */

    'custom' => [
        'attribute-name' => [
            'rule-name' => 'custom-message',
        ],
        'allClubRank' => [
            'required' => '不分俱樂部等級的任務已存在',
        ],
        'checkDeposit' => [
            'lte' => '必须有储值条件',
            'min' => '必须有储值条件',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Attributes
    |--------------------------------------------------------------------------
    |
    | The following language lines are used to swap attribute place-holders
    | with something more reader friendly such as E-Mail Address instead
    | of "email". This simply helps us make messages a little cleaner.
    |
     */

    'attributes' => [
        'checkDeposit' => '储值条件',
        'image'        => '图片',
        'imgMobile'    => '手机图片',
    ],

];
