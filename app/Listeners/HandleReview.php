<?php

namespace App\Listeners;

use App\Events\ReviewUpdated;
use App\Events\ThrowException;
use App\Exceptions\ErrorException;
use App\Listeners\SyncSocketService;
use App\Models\Coupon;
use App\Models\Member;
use App\Models\MemberBank;
use App\Models\Review\BaseReviewModel;
use App\Models\Review\ReviewNotify;
use App\Models\ReviewType;
use App\Services\LetterService;
use App\Services\Review\ReviewService;
use Exception;

class HandleReview
{
    public function handle(ReviewUpdated $event)
    {
        // $review = new SyncSocketService;

        // $review->reviewUpdated($event);
        // $this->sendSystemLetter($review);
    }

    public function sendSystemLetter(BaseReviewModel $review)
    {
        $key    = classConst($review, 'REVIEW_KEY');
        $status = $review->status;

        $sendTypes = [
            ReviewType::KEY_MEMBER_BANK,
            ReviewType::KEY_MEMBER_INFORMATION,
            ReviewType::KEY_MEMBER_DEPOSIT_BANK,
            ReviewType::KEY_MEMBER_WITHDRAW,
        ];
        if (! in_array($key, $sendTypes)) {
            return;
        }

        if ($key == ReviewType::KEY_MEMBER_WITHDRAW && $review->transaction_status == ReviewService::TRANSACTION_STATUS_COMPLETED) {
            $status = ReviewNotify::TRANSACTION;
        }

        /** @var ReviewNotify $reviewNotify */
        $reviewNotify = ReviewNotify::where('key', $key)
            ->where('status', $status)
            ->first();

        if ($reviewNotify && $reviewNotify->isEnabled()) {

            /**
             * 只有會員相關的審核才需要發通知.
             * @var Member $member
             */
            $member = $review->member;
            if (! $member) {
                event(new ThrowException(new ErrorException('member not found')));

                return;
            }

            // 保留字替換掉
            $title = $reviewNotify->title;
            $title = str_replace('{user-name}', $member->name, $title);
            $title = str_replace('{user-account}', $member->account, $title);

            $content = $reviewNotify->content;

            if ($key == ReviewType::KEY_MEMBER_BANK) {
                $bank = MemberBank::where('id', $review->member_bank_id)->first();
            } elseif ($key == ReviewType::KEY_MEMBER_DEPOSIT_BANK) {
                $bank = MemberBank::where('id', $review->bank_id)->first();
            }

            if (in_array($key, [ReviewType::KEY_MEMBER_BANK, ReviewType::KEY_MEMBER_DEPOSIT_BANK])) {
                $title   = str_replace('{bank-username}', $bank->name ?? '', $title);
                $title   = str_replace('{bank-account}', $bank->account ?? '', $title);
                $title   = str_replace('{bank-name}', $bank->bank_name ?? '', $title);
                $title   = str_replace('{deposit-money}', $review->apply_amount ?? '', $title);
                $title   = str_replace('{committed-at}', $review->committed_at, $title);
                $content = str_replace('{bank-username}', $bank->name ?? '', $content);
                $content = str_replace('{bank-account}', $bank->account ?? '', $content);
                $content = str_replace('{bank-name}', $bank->bank_name ?? '', $content);
                $content = str_replace('{deposit-money}', $review->apply_amount ?? '', $content);
                $content = str_replace('{committed-at}', $review->committed_at, $content);
            } elseif ($key == ReviewType::KEY_MEMBER_WITHDRAW) {
                $title   = str_replace('{bank-username}', $review->payee_name ?? '', $title);
                $title   = str_replace('{bank-account}', $review->payee_account ?? '', $title);
                $title   = str_replace('{bank-name}', $review->payee_bank_name ?? '', $title);
                $title   = str_replace('{withdraw-money}', $review->money ?? '', $title);
                $title   = str_replace('{committed-at}', $review->committed_at, $title);
                $content = str_replace('{bank-username}', $review->payee_name ?? '', $content);
                $content = str_replace('{bank-account}', $review->payee_account ?? '', $content);
                $content = str_replace('{bank-name}', $review->payee_bank_name ?? '', $content);
                $content = str_replace('{withdraw-money}', $review->money ?? '', $content);
                $content = str_replace('{committed-at}', $review->committed_at, $content);
            }

            $content = str_replace('{user-name}', $member->name, $content);
            $content = str_replace('{user-account}', $member->account, $content);
            if ($key == ReviewType::KEY_AGENT_COUPON) {
                $title   = str_replace(' {coupon-name}', $review->name, $title);
                $content = str_replace('{coupon-name}', $review->name, $content);
            } elseif ($key == ReviewType::KEY_MEMBER_COUPON) {
                $title   = str_replace(' {coupon-name}', $review->name, $title);
                $coupon  = Coupon::findOrError($review->coupon_id);
                $content = str_replace('{coupon-name}', $coupon->name, $content);
            }

            $content = str_replace('{committed-at}', $review->created_at, $content);
            $content = str_replace('{reason}', $review->reason, $content);

            $service = new LetterService();
            $service->setMembers([$member->id]);

            // 站內信 (會員註冊不發信)
            if ($reviewNotify->letter_tag_id) {

                /* 無視錯誤發生，避免中斷 */
                try {
                    $service->sendAnnouncement($reviewNotify->letter_tag_id, $title, $content);
                } catch (Exception $err) {
                    event(new ThrowException($err));
                }
            }

            // 簡訊
            if ($reviewNotify->sms_user_id && $member->phone) {

                /* 無視錯誤發生，避免中斷 */
                try {
                    $service->sendSms($reviewNotify->sms_user_id, $content, $member->phone);
                } catch (Exception $err) {
                    event(new ThrowException($err));
                }
            }

            // email
            if ($reviewNotify->email_user_id) {
                // $service->sendEmail($title, $content);
            }
        }
    }
}
