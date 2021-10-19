<?php

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Services\Review\ReviewKeyMemberWithdrawService;
use App\Models\Member;
use App\Models\LogMemberWallet;
use App\Models\GamePlatform;
use App\Models\MemberPlatformActive;
use App\Models\LogMemberTransfer;
use App\Models\MemberWallet;
use App\Models\Review\ReviewMemberWithdraw;

class ReviewMemberWithDrawTest extends TestCase
{
    use WithoutMiddleware;
    use DatabaseTransactions;

    const API_LOGIN = '/api/public/login';
    const API_APPROVE = '/api/review/member-withdraw/approve';
    const API_DISAPPROVE = '/api/review/member-withdraw/disapprove';
    const API_LIST = '/api/review/member-withdraw/list';

    # 10/01 新增API
    const API_TRANSACTION = '/api/review/member-withdraw/transaction';
    const API_THIRD_WITHDRAWS = '/api/review/member-withdraw/third-withdraws';
    const API_THIRD_PARAMS = '/api/review/member-withdraw/third-params';
    const API_THIRD_LOG = '/api/review/member-withdraw/third-log-all';

    # 10/08 新增API
    const API_BANK_OPTIONS = '/api/review/member-withdraw/bank-options';
    const API_LOG_WALLET = '/api/review/member-withdraw/log-wallet/list';
    const API_PLATFORM_OPTIONS = '/api/review/member-withdraw/platform-options';
    const API_PLATFORM_WALLET = '/api/review/member-withdraw/platform-wallet';
    const API_LOG_TRANSFER = '/api/review/member-withdraw/log-transfer/list';
    const API_STEP_LOG = '/api/review/member-withdraw/step-log/all';

    public function testError()
    {
        # approve
        $data = [];
        $this->post(static::API_APPROVE, $data)
            ->assertStatus(400)
            ->assertExactJson([
                'errors' => [
                    'id' => ['The id field is required.']
                ],
                'message' => 'fail'
            ]);
        $data = [
            'id' => 'xxx',
            'remark' => 0
        ];
        $this->post(static::API_APPROVE, $data)
            ->assertStatus(400)
            ->assertExactJson([
                'errors' => [
                    'id' => ['The selected id is invalid.'],
                    'remark' => ['The remark must be a string.']
                ],
                'message' => 'fail'
            ]);

        # disapprove
        $data = [];
        $this->post(static::API_DISAPPROVE, $data)
            ->assertStatus(400)
            ->assertExactJson([
                'errors' => [
                    'id' => ['The id field is required.'],
                    'reason' => ['The reason field is required.']
                ],
                'message' => 'fail'
            ]);
        $data = [
            'id' => 'xxx',
            'reason' => 0,
            'remark' => 0
        ];
        $this->post(static::API_DISAPPROVE, $data)
            ->assertStatus(400)
            ->assertExactJson([
                'errors' => [
                    'id' => ['The selected id is invalid.'],
                    'reason' => ['The reason must be a string.'],
                    'remark' => ['The remark must be a string.']
                ],
                'message' => 'fail'
            ]);

        # list
        $data = [
            'status' => 'xxx',
            'page' => 'xxx',
            'perPage' => 'xxx'
        ];
        $this->call('GET', static::API_LIST, $data)
            ->assertStatus(400)
            ->assertExactJson([
                'errors' => [
                    'status' => ['The selected status is invalid.'],
                    'page' => ['The page must be an integer.'],
                    'perPage' => ['The per page must be an integer.']
                ],
                'message' => 'fail'
            ]);
        $data = [
            'sorts' => ['..']
        ];
        $this->call('GET', static::API_LIST, $data)
            ->assertStatus(500);
    }

    public function testSuccess()
    {
        # login
        $this->post('/api/public/login', ['account' => 'admin', 'password' => 'admin']);

        # approve
        $memberId = 99;
        $reviewMemberWithdraw = new ReviewMemberWithdraw();
        $reviewMemberWithdraw->member_id = $memberId;
        $reviewMemberWithdraw->money += 1;
        $reviewMemberWithdraw->fee = 0;
        $reviewMemberWithdraw->type = 'bank';
        $reviewMemberWithdraw->third_withdraw_id = 0;
        $reviewMemberWithdraw->payer_name = 'xxx';
        $reviewMemberWithdraw->payer_account = 'xxx';
        $reviewMemberWithdraw->payer_bank_name = 'xxx';
        $reviewMemberWithdraw->payer_branch_name = 'xxx';
        $reviewMemberWithdraw->payee_name = 'xxx';
        $reviewMemberWithdraw->payee_account = 'xxx';
        $reviewMemberWithdraw->payee_bank_name = 'xxx';
        $reviewMemberWithdraw->review_step_count = 0;
        $reviewMemberWithdraw->review_step = 0;
        $reviewMemberWithdraw->review_role_id = 0;
        $reviewMemberWithdraw->status = 'pending';
        $reviewMemberWithdraw->reason = '';
        $reviewMemberWithdraw->saveOrError();
        $review = new ReviewKeyMemberWithdrawService($reviewMemberWithdraw);
        $review->commit();

        $memberWallet = MemberWallet::findOrError($memberId);
        $memberWallet->review_withdraw_money = 1;
        $memberWallet->saveOrError();

        $data = [
            'id' => $reviewMemberWithdraw->id,
            'remark' => 'xxx',
        ];
        $this->post(static::API_APPROVE, $data)
            ->assertStatus(200);

        # disapprove
        $memberId = 99;
        $reviewMemberWithdraw = new ReviewMemberWithdraw();
        $reviewMemberWithdraw->member_id = $memberId;
        $reviewMemberWithdraw->money += 1;
        $reviewMemberWithdraw->fee = 0;
        $reviewMemberWithdraw->type = 'bank';
        $reviewMemberWithdraw->third_withdraw_id = 0;
        $reviewMemberWithdraw->payer_name = 'xxx';
        $reviewMemberWithdraw->payer_account = 'xxx';
        $reviewMemberWithdraw->payer_bank_name = 'xxx';
        $reviewMemberWithdraw->payer_branch_name = 'xxx';
        $reviewMemberWithdraw->payee_name = 'xxx';
        $reviewMemberWithdraw->payee_account = 'xxx';
        $reviewMemberWithdraw->payee_bank_name = 'xxx';
        $reviewMemberWithdraw->review_step_count = 0;
        $reviewMemberWithdraw->review_step = 0;
        $reviewMemberWithdraw->review_role_id = 0;
        $reviewMemberWithdraw->status = 'pending';
        $reviewMemberWithdraw->reason = '';
        $reviewMemberWithdraw->saveOrError();
        $review = new ReviewKeyMemberWithdrawService($reviewMemberWithdraw);
        $review->commit();

        $memberWallet = MemberWallet::findOrError($memberId);
        $memberWallet->review_withdraw_money += 1;
        $memberWallet->saveOrError();

        $data = [
            'id' => $reviewMemberWithdraw->id,
            'reason' => 'xxx',
            'remark' => 'xxx'
        ];
        $this->post(static::API_DISAPPROVE, $data)
            ->assertStatus(200);

        # list
        $data = [];
        $this->call('GET', static::API_LIST, $data)
            ->assertStatus(200);
        $data = [
            'status' => 'all',
            'page' => 1,
            'perPage' => 1
        ];
        $this->call('GET', static::API_LIST, $data)
            ->assertStatus(200);

        # bank-options
        $this->get(static::API_BANK_OPTIONS)
            ->assertStatus(200);
    }

    /**
     * 2018-10-01 新增
     */

     public function testTransaction()
     {
        // 新增測試資料
        $memberId = 80;

        $reviewMemberWithdraw = new ReviewMemberWithdraw;
        $reviewMemberWithdraw->member_id = $memberId;
        $reviewMemberWithdraw->money = 1;
        $reviewMemberWithdraw->fee = 0;
        $reviewMemberWithdraw->payee_name = '';
        $reviewMemberWithdraw->payee_account = '';
        $reviewMemberWithdraw->payee_bank_name = '';
        $reviewMemberWithdraw->review_step_count = 0;
        $reviewMemberWithdraw->review_step = 0;
        $reviewMemberWithdraw->review_role_id = 0;
        $reviewMemberWithdraw->status = 'pending';
        $reviewMemberWithdraw->reason = '';
        $reviewMemberWithdraw->saveOrError();

        $review = new ReviewKeyMemberWithdrawService($reviewMemberWithdraw);
        $review->commit();

        $MemberWallet = MemberWallet::findOrError($memberId);
        $MemberWallet->review_withdraw_money += 1;
        $MemberWallet->saveOrError();

         # error
        $data = [
            'id' => 'xxx',
            'type' => 'xxx',
            'fee' => 'xxx'
        ];
        $this->post(static::API_TRANSACTION, $data)
            ->assertStatus(400)
            ->assertExactJson([
                'errors' => [
                    'id' => ['The selected id is invalid.'],
                    'type' => ['The selected type is invalid.'],
                    'fee' => ['The fee must be a number.']
                ],
                'message' => 'fail'
            ]);
        $data = [
            'id' => $reviewMemberWithdraw->id,
            'type' => 'bank',
            'fee' => 0
        ];
        $this->post(static::API_TRANSACTION, $data)
            ->assertStatus(400)
            ->assertExactJson([
                'errors' => [
                    'payerName' => ['The payer name field is required.'],
                    'payerAccount' => ['The payer account field is required.'],
                    'payerBankName' => ['The payer bank name field is required.'],
                ],
                'message' => 'fail'
            ]);
        $data = [
            'id' => $reviewMemberWithdraw->id,
            'type' => 'bank',
            'fee' => 0,
            'payerName' => 0,
            'payerAccount' => 0,
            'payerBankName' => 0,
            'payerBranchName' => 0,
            'transactionAt' => 0,
            'transactionId' => 0
        ];
        $this->post(static::API_TRANSACTION, $data)
            ->assertStatus(400)
            ->assertExactJson([
                'errors' => [
                    'payerName' => ['The payer name must be a string.'],
                    'payerAccount' => ['The payer account must be a string.'],
                    'payerBankName' => ['The payer bank name must be a string.'],
                    'payerBranchName' => ['The payer branch name must be a string.'],
                    'transactionAt' => ['The transaction at does not match the format Y-m-d H:i:s.'],
                    'transactionId' => ['The transaction id must be a string.'],
                ],
                'message' => 'fail'
            ]);

        # success
        $data = [
            'account' => 'admin',
            'password' => 'admin'
        ];
        $this->post(static::API_LOGIN, $data)
            ->assertStatus(200);

        $data = [
            'id' => $reviewMemberWithdraw->id,
            'type' => 'bank',
            'fee' => 0,
            'payerName' => 'xxx',
            'payerAccount' => 'xxx',
            'payerBankName' => 'xxx',
            'payerBranchName' => 'xxx',
            'transactionAt' => date('Y-m-d H:i:s'),
            'transactionId' => 'xxx'
        ];
     }

     public function testThirdLog()
     {
         $data = [
             'account' => 'admin',
             'password' => 'admin'
         ];
         $this->post(static::API_LOGIN, $data)
            ->assertStatus(200);

         $data = [
             'id' => 4
         ];
         $this->call('GET', static::API_THIRD_LOG, $data)
            ->assertStatus(200);
     }

    /**
     * 2018-10-08 新增
     */

    // listLogWallet
    public function testListLogWallet()
    {
        // ---------------- error ----------------
        $data = [
            'memberId' => '!',
            'startTime' => 'aaa',
            'endTime' => 'bbb',
            'type' => '321',
            'sorts' => ['type,asc']
        ];
        $this->call('GET', static::API_LOG_WALLET, $data)
            ->assertStatus(400)
            ->assertExactJson([
                'errors' => [
                    'startTime' => ['The start time is not a valid date.'],
                    'endTime' => ['The end time is not a valid date.'],
                    'type' => ['The selected type is invalid.']
                ],
                'message' => 'fail'
            ]);

        // ---------------- success ----------------
        $data = [
            'memberId' => 3,
            'startTime' => '2018-09-04',
            'endTime' => '2018-12-01'
        ];
        $this->call('GET', static::API_LOG_WALLET, $data)
            ->assertStatus(200);
    }

    // platformOptions
    public function testPlatformOptions()
    {
        // ---------------- success ----------------

        // 新增測試資料
        $gamePlatform = new GamePlatform();
        $gamePlatform->key = 'test_key';
        $gamePlatform->name = 'test_name';
        $gamePlatform->paramter = 'test_paramter';
        $gamePlatform->saveOrError();
        $gamePlatform = $gamePlatform->fresh();

        $this->call('GET', static::API_PLATFORM_OPTIONS)
            ->assertStatus(200);

    }

    // platformWallet
    public function testPlatformWallet()
    {
        // ---------------- error ----------------
        $data = [];
        $this->call('GET', static::API_PLATFORM_WALLET, $data)
            ->assertStatus(400)
            ->assertExactJson([
                'errors' => [
                    'memberId' => ['The member id field is required.'],
                    'platformId' => ['The platform id field is required.'],
                ],
                'message' => 'fail'
            ]);

        $data = [
            'memberId' => 'qq',
            'platformId' => '@@',
        ];
        $this->call('GET', static::API_PLATFORM_WALLET, $data)
            ->assertStatus(400)
            ->assertExactJson([
                'errors' => [
                    'memberId' => ['The selected member id is invalid.'],
                    'platformId' => ['The selected platform id is invalid.'],
                ],
                'message' => 'fail'
            ]);

        $data = [
            'memberId' => 12,
            'platformId' => 24,
        ];
        $this->call('GET', static::API_PLATFORM_WALLET, $data)
            ->assertStatus(400)
            ->assertExactJson([
                'message' => 'empty member active'
            ]);

        // ---------------- success ----------------
        // 新增測試資料
        $memberPlatformActive = new MemberPlatformActive();
        $memberPlatformActive->member_id = 81;
        $memberPlatformActive->platform_id = 25;
        $memberPlatformActive->username = 'nameeee';
        $memberPlatformActive->active_status = 'completed';
        $memberPlatformActive->saveOrError();

        $memberPlatformActive = $memberPlatformActive->fresh();

        $data = [
            'memberId' => 81,
            'platformId' => 25,
        ];
        $this->call('GET', static::API_PLATFORM_WALLET, $data)
            ->assertStatus(200);
    }

        // listLogTransfer
    public function testListLogTransfer()
    {
        // ---------------- error ----------------
        $data = [];
        $this->call('GET', static::API_LOG_TRANSFER, $data)
            ->assertStatus(400)
            ->assertExactJson([
                'errors' => [
                    'memberId' => ['The member id field is required.'],
                    'platformId' => ['The platform id field is required.'],
                    'startTime' => ['The start time field is required.'],
                    'endTime' => ['The end time field is required.']
                ],
                'message' => 'fail'
            ]);

        // ---------------- success ----------------
        // 新增測試資料
        $logMemberTransfer = new LogMemberTransfer();
        $logMemberTransfer->type = 'transfer-game';
        $logMemberTransfer->member_id = 12;
        $logMemberTransfer->platform_id = 24;
        $logMemberTransfer->coupon_id = 3;
        $logMemberTransfer->saveOrError();

        $logMemberTransfer = $logMemberTransfer->fresh();

        $data = [
            'memberId' => 12,
            'platformId' => 24,
            'startTime' => '2018/01/01',
            'endTime' => '2018/12/31'
        ];
        $this->call('GET', static::API_LOG_TRANSFER, $data)
            ->assertStatus(200);
    }

    public function stepLog()
    {
        $data = [];
        $this->call('GET', static::API_STEP_LOG, $data)
            ->assertStatus(200);
    }
}
