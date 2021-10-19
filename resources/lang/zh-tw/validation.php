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

    'accepted' => ' :attribute 必須同意',
    'active_url' => ' :attribute 不是有效的網址',
    'after' => ' :attribute 必須在 :date 之後',
    'after_or_equal' => ' :attribute 必須等於或大於 :date',
    'alpha' => ' :attribute 僅包含字母',
    'alpha_dash' => ' :attribute 僅包含字母、數字、破折號（ - ）以及底線（ _ ）',
    'alpha_num' => ' :attribute 僅包含字母、數字',
    'array' => ' :attribute 必須為陣列',
    'before' => ' :attribute 必須在 :date 之前',
    'before_or_equal' => ' :attribute 必須等於或小於 :date',
    'between' => [
        'numeric' => ' :attribute 必需介於 :min ~ :max 之間',
        'file' => ' :attribute 必需介於 :min ~ :max 個千位元組',
        'string' => ' :attribute 必需介於 :min ~ :max 個字元',
        'array' => ' :attribute 必須介於 :min ~ :max 個元素之間',
    ],
    'boolean' => ' :attribute 必須是 true 或 false',
    'confirmed' => '欄位與 :attribute 不一致',
    'date' => ' :attribute 不是有效日期',
    'date_format' => ' :attribute 不符合 :format 日期格式',
    'different' => ' :attribute 必須與 :other 欄位不同',
    'digits' => ' :attribute 必須為 :digits 位數',
    'digits_between' => ' :attribute 必須介於 :min ~ :max 位數之間',
    'dimensions' => ' :attribute 無效的圖片尺寸',
    'distinct' => ' :attribute 欄位有重複的值',
    'email' => ' :attribute 必須是有效的 email 格式',
    'exists' => ' 選定的 :attribute 不存在',
    'file' => ' :attribute 必須是一個檔案',
    'filled' => ' :attribute 是必填',
    'gt' => [
        'numeric' => ' :attribute 必須大於 :value',
        'file' => ' :attribute 必須大於 :value 個千位元組',
        'string' => ' :attribute 必須大於 :value 個字元',
        'array' => ' :attribute 必須大於 :value 個元素',
    ],
    'gte' => [
        'numeric' => ' :attribute 必須大於或等於 :value',
        'file' => ' :attribute 必須大於或等於 :value 個千位元組',
        'string' => ' :attribute 必須大於或等於 :value 個字元',
        'array' => ' :attribute 至少包含 :value 個元素',
    ],
    'image' => ' :attribute 檔案必須為圖片格式（ jpeg、png、bmp、gif、 或 svg ）',
    'in' => ' 選定的 :attribute 是無效的',
    'in_array' => ' :attribute 不存在於 :other',
    'integer' => ' :attribute 必須為整數',
    'ip' => ' :attribute 必須為有效的 ip 位址',
    'ipv4' => ' :attribute 必須為有效的 IPv4 位址',
    'ipv6' => ' :attribute 必須為有效的 iIPv6p 位址',
    'json' => ' :attribute 必須為有效的 JSON 字串',
    'lt' => [
        'numeric' => ' :attribute 必須小於 :value',
        'file' => ' :attribute 必須小於 :value 個千位元組',
        'string' => ' :attribute 必須小於 :value 個字元',
        'array' => ' :attribute 必須少於 :value 個元素',
    ],
    'lte' => [
        'numeric' => ' :attribute 最大為 :value',
        'file' => ' :attribute 最多 :value 個千位元祖',
        'string' => ' :attribute 最多 :value 個字元',
        'array' => ' :attribute 最多包含 :value 個元素',
    ],
    'max' => [
        'numeric' => ' :attribute 不可大於 :max.',
        'file' => ' :attribute 不可大於 :max 個千位元祖',
        'string' => ' :attribute 不可大於 :max 個字元',
        'array' => ' :attribute 不可超過 :max 個元素',
    ],
    'mimes' => ' :attribute 檔案類型必須為: :values',
    'mimetypes' => ' :attribute 檔案類型必須為: :values',
    'min' => [
        'numeric' => ' :attribute 至少須為 :min',
        'file' => ' :attribute 至少須 :min 個千位元組',
        'string' => ' :attribute 至少須 :min 個字元',
        'array' => ' :attribute 至少需 :min 個元素',
    ],
    'not_in' => ' 選定的 :attribute 是無效的',
    'not_regex' => ' :attribute 的格式無效',
    'numeric' => ' :attribute 必須是數字',
    'present' => ' :attribute 必須存在',
    'regex' => ' :attribute 格式是無效的',
    'required' => ' :attribute 為必填',
    'required_if' => '當 :other 為 :value 時 :attribute 為必填',
    'required_unless' => ' :attribute 為必填除非 :other 為 :values',
    'required_with' => ' 當 :values 任一個值存在， :attribute 為必填',
    'required_with_all' => '當所有 :values 存在，:attribute 為必填',
    'required_without' => '當 :values 任一個值不存在，:attribute 為必填',
    'required_without_all' => '當所有 :values 都沒有值時，:attribute 為必填',
    'same' => ' :attribute 與 :other 必須相符',
    'size' => [
        'numeric' => ' :attribute 須為 :size',
        'file' => ' :attribute 須為 :size 個千位元組',
        'string' => ' :attribute 須為 :size 個字元',
        'array' => ' :attribute 須包含 :size 個元素',
    ],
    'string' => ' :attribute 必須為字串',
    'timezone' => ' :attribute 必須為有效時區',
    'unique' => ' :attribute 已存在',
    'uploaded' => ' :attribute 上傳失敗',
    'url' => ' :attribute 無效的格式',

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
            'lte' => '必須有儲值條件',
            'min' => '必須有儲值條件',
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
        'checkDeposit' => '儲值條件',
        'image'        => '圖片',
        'imgMobile'    => '手機圖片',
    ],

];
