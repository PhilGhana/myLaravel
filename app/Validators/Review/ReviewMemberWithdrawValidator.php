<?php

namespace App\Validators\Review;

use App\Validators\ExpansionRules\SortArray;
use App\Rules\StringRegex;
use App\Rules\StringSymbolRegex;
class ReviewMemberWithdrawValidator extends ReviewBaseValidator
{

    public static function checkApprove($data)
    {
        (new static($data, [
            'id'     => 'required|exists:review_member_withdraw,id',
            'remark' => [
                'nullable',
                'string',
                'max:50',
            ],
        ]))->check();
    }

    public static function checkDisapprove($data)
    {
        (new static($data, [
            'id'                 => 'required|exists:review_member_withdraw,id',
            'reason'             => [
                'required',
                'string',
                'max:50',
            ],
            'deductionBetAmount' => 'required_if:hasCheated,true|numeric',
            'remark'             => [
                'nullable',
                'string',
                'max:50',
            ],
        ]))->check();
    }

    public static function checkGetList($data)
    {
        (new static($data, [
            'status'             => 'nullable|in:all,review,approved,disapproved,transaction-none,transaction-pending,transaction-completed',
            'transactionAtStart' => 'nullable|date_format:Y-m-d H:i:s',
            'transactionAtEnd'   => 'nullable|date_format:Y-m-d H:i:s',
            'page'               => 'nullable|integer|min:1',
            'perPage'            => 'nullable|integer|min:1',
        ]))->check();
    }

    public static function checkListLogWallet($data)
    {
        $type = [
            'commission',
            'reward',
            'edit-bonus',
            'deposit-bank',
            'deposit-third',
            'withdraw',
            'withdraw-reject',
            'edit-money',
            'give-money',
            'take-back',
            'transfer-game',
            'transfer-wallet',
            'coupon',
        ];
        (new static($data, [
            'memberId'  => 'required|exists:member,id',
            'startTime' => 'nullable|string|date',
            'endTime'   => 'nullable|string|date',
            'type'      => 'nullable|in:' . implode(',', $type),
            'sorts'     => ['nullable', new SortArray(['id', 'type', 'createdAt', 'editorId'])],
        ]))->check();
    }

    public static function checkPlatformWallet($data)
    {
        (new static($data, [
            'memberId'   => 'required|exists:member,id',
            'platformId' => 'required|exists:game_platform,id',
        ]))->check();
    }

    public static function checkListLogTransfer($data)
    {
        (new static($data, [
            'memberId'   => 'required|exists:member,id',
            'platformId' => 'nullable|exists:game_platform,id',
            'startTime'  => 'nullable|string|date',
            'endTime'    => 'nullable|string|date',
            'sorts'      => ['nullable', new SortArray(['id', 'platformId', 'createdAt'])],
        ]))->check();
    }

    public static function checkTransaction($data)
    {
        $checkData = [
            'id'   => 'required|exists:review_member_withdraw,id',
            'type' => 'required|in:bank,third',
            'fee'  => 'required|numeric|min:0',
        ];

        if (isset($data['type'])) {
            if ($data['type'] === 'bank') {
                $checkData['payerName']       = 'required|string';
                $checkData['payerAccount']    = 'required|string';
                $checkData['payerBankName']   = 'required|string';
                $checkData['payerBranchName'] = 'nullable|string';
                $checkData['transactionId']   = 'nullable|string';
                $checkData['transactionAt']   = 'nullable|date_format:Y-m-d H:i:s';
            }
        }

        (new static($data, $checkData))->check();
    }

    public static function checkthirdWithdraws($data)
    {
        (new static($data, [
            'memberId' => 'required',
        ]))->check();
    }

    public static function checkThirdParams($data)
    {
        (new static($data, [
            'id'       => 'required|string',
            'type'     => 'required|string',
            'memberId' => 'required',
        ]))->check();
    }

    public static function checkTransactionCancel($data)
    {
        (new static($data, [
            'id'     => 'required|exists:review_member_withdraw,id',
            'remark' => ['required', 'string', 'max:50'],
        ]))->check();
    }

    public static function checkTransactionManual($data)
    {
        (new static($data, [
            'id' => 'required|exists:review_member_withdraw,id',
        ]))->check();
    }

    public static function checkBankOptions($data)
    {
        (new static($data, [
            'memberId' => 'required',
        ]))->check();
    }
}