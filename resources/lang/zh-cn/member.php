<?php

return [
    'not-found'                     => '会员不存在',
    'not-found-id'                  => '会员 :id 不存在',
    'not-found-account'             => '会员 :account 不存在',
    'not-found-phone'               => '会员电话不存在',
    'disabled'                      => '会员已停用',
    'locked'                        => '会员已锁定',
    'destroy'                       => '会员已销毁',
    'access-denied'                 => '无权限',
    'club-rank-id-not-match'        => '俱乐部 id 不符',
    'duplicate-account'             => '帐号重复',
    'duplicate-phone'               => '电话重复',
    'franchisee-error'              => '加盟商错误',
    'platform-percent-not-set'      => '游戏平台佔成未设定',
    'inactive-game'                 => '游戏未启用',
    'no-default-register-parent'    => '未设定预设上层',
    'no-permission-to-modify'       => '无修改权限',
    'no-default-register-club-rank' => '未设定预设俱乐部层级',
    'member-bank-repeat'            => '银行卡已存在',
    'member-bank-not-found'         => '银行卡不存在',
    'member-bank-prohibit-modify'   => '银行卡资讯禁止修改',
    'agent-edit-deposit-not-found'  => '系统错误 流水纪录不存在',
    'at-last-one'                   => '至少要一个搜寻条件',
    'create-error'                  => '会员建立失败',
    'game-account-not-found'        => '游戏帐号不存在',
    'check'                         => [
        'used'              => '您正使用该号码',
        'exist'             => '该号码已申请过或审核中',
        'pending-or-review' => '已有资料审核中',
    ],
    'excel-format' => [
        '建立时间',
        '会员帐号',
        '交易类型',
        '游戏平台',
        '备注',
        '异动额',
        '异动后',
        '操作人',
        'IP',
    ],
    // 会员交易纪录类型
    'type' => [
        'bonus-to-money'               => '红利转点数',
        'edit-bonus'                   => '公司补红利点数',
        'deposit-bank'                 => '银行存款',
        'deposit-fee'                  => '存款手续费',
        'deposit-third'                => '第三方存款',
        'withdraw'                     => '提款',
        'edit-money'                   => '可用点数修改（公司）',
        'get-from-agent'               => '代理派点',
        'be-taken-back-agent'          => '代理回收点数',
        'get-from-error'               => '错误补点',
        'be-taken-back-error'          => '错误回收',
        'bet'                          => '下注',
        'settle'                       => '开奖',
        'prize'                        => '彩金',
        'rollback'                     => '注单重算',
        'bet-completed'                => '注单完成',
        'water'                        => '退水',
        'undo-water'                   => '撤消退水',
        'bonus'                        => '分红',
        'undo-bonus'                   => '撤消分红',
        'payin'                        => '单钱包扣点数',
        'payout'                       => '单钱包加点数',
        'transfer-game'                => '点数转出至游戏',
        'transfer-wallet'              => '点数转回至平台',
        'edit-reward'                  => '上层修改优惠活动赠点',
        'agent-edit-deposit'           => '代客储值',
        'withdraw-disapproved'         => '提款审核不通过',
        'transaction-cancel'           => '取消出款',
        'agent-edit-deposit-rollback'  => '代客储值收回',
        'third-party-deposit'          => '第三方代储值',
        'third-party-deposit-rollback' => '第三方代储值收回',
        'manual-edit-add'              => '人工增加额度',
        'manual-edit-sub'              => '人工减少额度',
    ],
];