<?php

namespace App\Http\Controllers\Game;

use App\Models\GamePlatform;
use App\Models\GamePlatformLimit;
use App\Models\Member;
use App\Services\Game\GamePlatformService;
use App\Services\Member\MemberWalletService;
use App\Support\Query;
use App\Validators\Game\GamePlatformValidator;
use Carbon\Carbon;
use DB;
use Exception;

class GamePlatformController extends GameBaseController
{
    public function addPlatform()
    {
        $data = request()->all();

        GamePlatformValidator::checkAdd($data);
        // 維護驗證時間格式
        $this->checkCrontab($data);

        $platform                              = new GamePlatform();
        $platform->key                         = $data['key'];
        $platform->name                        = $data['name'];
        $platform->member_prefix               = $data['memberPrefix'] ?? '';
        $platform->setting                     = $data['setting'] ?? '{}';
        $platform->enabled                     = $data['enabled'];
        $platform->maintain                    = $data['maintain'];
        $platform->fun                         = $data['fun'];
        $platform->maintain_crontab            = $data['maintainCrontab'] ?? null;
        $platform->maintain_minute             = $data['maintainMinute'] ?? 0;
        $platform->namespace                   = $data['namespace'] ?? '';
        $platform->whitelist                   = $data['whitelist'] ?? null;
        $platform->limit                       = $data['limit'] ?? 0;
        $platform->sync_report_delay           = $data['syncReportDelay'] ?? 0;
        $platform->order                       = $data['order'] ?? 0;
        $platform->platformId                  = $data['platformId'] ?? 0;
        $platform->has_app                     = $data['hasApp'] ?? 0;
        $platform                              = GamePlatformService::updateImage($platform, $data);

        $platform->disposable_maintain_date    = $data['disposableMaintainDate'] ?? null;
        $platform->disposable_maintain         = $data['disposableMaintain'] ?? 0;

        DB::transaction(function () use ($platform, $data) {
            $platform->saveOrError();
            GamePlatformService::updateOrder();
            if ($platform->isLimit()) {
                GamePlatformService::updateLimit($platform, $data['members']);
            }
            $this->clearGameCache();
        });
    }

    public function editPlatform()
    {
        $data = request()->all();

        GamePlatformValidator::checkEdit($data);
        // 維護驗證時間格式

        $platform = GamePlatform::findOrError($data['id']);
        $this->checkCrontab($data, $platform->disposable_maintain_date);

        $platform->key                         = $data['key'];
        $platform->name                        = $data['name'];
        $platform->member_prefix               = $data['memberPrefix'] ?? '';
        $platform->setting                     = $data['setting'] ?? '{}';
        $platform->enabled                     = $data['enabled'];
        $platform->maintain                    = $data['maintain'];
        $platform->fun                         = $data['fun'];
        $platform->namespace                   = $data['namespace'] ?? '';
        $platform->whitelist                   = $data['whitelist'] ?? null;
        $platform->limit                       = $data['limit'] ?? 0;
        $platform->sync_report_delay           = $data['syncReportDelay'] ?? 0;
        $platform->order                       = $data['order'] ?? 0;
        $platform->platformId                  = $data['platformId'] ?? 0;
        $platform->has_app                     = $data['hasApp'] ?? 0;
        $platform->maintain_crontab            = $data['maintainCrontab'] ?? null;
        $platform->maintain_minute             = $data['maintainMinute'] ?? 0;
        $platform                              = GamePlatformService::updateImage($platform, $data);
        $platform->disposable_maintain_date    = $data['disposableMaintainDate'] ?? null;
        $platform->disposable_maintain         = $data['disposableMaintain'] ?? 0;

        DB::transaction(function () use ($platform, $data) {
            $platform->saveOrError();
            GamePlatformService::updateOrder();
            if ($platform->isLimit()) {
                GamePlatformService::updateLimit($platform, $data['members']);
            }
            $this->clearGameCache();
        });
    }

    public function toggleEnable()
    {
        $data = request()->all();

        GamePlatformValidator::checkToggleEnable($data);

        $platform          = GamePlatform::findOrError($data['id']);
        $platform->enabled = $data['enabled'];
        DB::transaction(function () use ($platform) {
            $platform->saveOrError();
            $this->clearGameCache();
        });
    }

    public function toggleMaintain()
    {
        $id                 = request()->input('id');
        $maintain           = request()->input('maintain');
        $platform           = GamePlatform::findOrError($id);
        $platform->maintain = intval($maintain) ? 1 : 0;
        DB::transaction(function () use ($platform) {
            $platform->saveOrError();
            $this->clearGameCache();
        });
    }

    public function getList()
    {
        $data = request()->all();

        GamePlatformValidator::checkList($data);

        // 部分功能修改頁，撈取需要的欄位
        if (request()->is('*/list/limited-auth')) {
            $selectParams = [
                'id',
                'key',
                'name',
                'maintain',
                'maintain_crontab',
                'maintain_minute',
                'disposable_maintain_date',
                'disposable_maintain',
                'limit',
                'image',
                'icon',
                'enabled',
                'order',
                'platformId',
                'page_bg_img',
                'index_img',
                'header_img',
            ];
        } else {
            $selectParams = [
                'id',
                'key',
                'name',
                'member_prefix',
                'fun',
                'setting',
                'member_prefix',
                'maintain',
                'maintain_crontab',
                'maintain_minute',
                'disposable_maintain_date',
                'disposable_maintain',
                'namespace',
                'limit',
                'image',
                'sync_report_delay',
                'icon',
                'enabled',
                'order',
                'platformId',
                'whitelist',
                'has_app',
                'page_bg_img',
                'index_img',
                'header_img',
            ];
        }

        $query = GamePlatform::with('limits')
            ->select($selectParams);

        $key = $data['key'] ?? null;
        if ($key) {
            $query->where('key', $key);
        }

        $name = $data['name'] ?? null;
        if ($name) {
            $query->where('name', 'like', '%'.$name.'%');
        }

        $enabled = intval($data['enabled'] ?? -1);
        if ($enabled === 0 || $enabled === 1) {
            $query->where('enabled', $enabled);
        }

        $maintain = intval($data['maintain'] ?? -1);
        if ($maintain >= 0) {
            $query->where('maintain', $maintain ? 1 : 0);
        }

        $sorts = $data['sorts'] ?? [];
        Query::orderBy($query, $sorts);

        return apiResponse()->paginate($query, function (GamePlatform $row) {
            $attrs = $row->toArray();
            $attrs['imageUrl'] = $row->imageUrl;
            $attrs['iconUrl'] = $row->iconUrl;
            $attrs['pageBgImgUrl'] = $row->pageBgImgUrl;
            $attrs['indexImgUrl'] = $row->indexImgUrl;
            $attrs['headerImgUrl'] = $row->headerImgUrl;

            return $attrs;
        });
    }

    /**
     * 取得平台資訊.
     */
    public function getListAndBalance()
    {
        $data = request()->all();
        GamePlatformValidator::checkList($data);
        $member  = Member::findOrError($data['id']);
        $service = new MemberWalletService($member);
        $result  = $service->getListAndBalance($data);
        $arr     = [];
        $arr     = apiResponse()->paginate($result, function (GamePlatform $row) {
            $attrs = $row->toArray();
            $attrs['imageUrl'] = $row->imageUrl;
            $attrs['iconUrl'] = $row->iconUrl;

            return $attrs;
        });

        return $arr;
    }

    /**
     * 取得平台餘額.
     */
    public function getPlatformBalance()
    {
        $data    = request()->all();
        $member  = Member::findOrError($data['id']);
        $service = new MemberWalletService($member);
        $balance = $service->getPlatformBalance($data);

        return apiResponse()->data([
            'balance' => $balance,
        ]);
    }

    public function getLimitMembers()
    {
        $platformId = request()->input('platformId', 0);
        $pid        = intval($platformId ?: 0) ?: 0;
        $res        = GamePlatformLimit::where('platform_id', $pid)
            ->select([
                'member_id',
                'member_account',
            ])
            ->get();

        return apiResponse()->data($res->map(function ($row) {
            return [
                'id'      => $row->member_id,
                'account' => $row->member_account,
            ];
        }));
    }

    public function getPlatformOptions()
    {
        $platforms = GamePlatform::select('id', 'name', 'order')
            ->where('enabled', 1)
            ->orderBy('order')
            ->get();

        return apiResponse()->data($platforms);
    }

    public function queryMember()
    {
        $account = request()->input('account', '');

        $res = Member::select('id', 'account')
            ->where('account', 'like', "%{$account}%")
            ->take(10)
            ->orderBy('account')
            ->get();

        return apiResponse()->data($res);
    }

    /**
     * 編輯遊戲平台 (特定項目).
     *
     * @return void
     */
    public function editPlatformByLimitedAuth()
    {
        $data = request()->all();
        GamePlatformValidator::checkEditByLimitedAuth($data);

        $platform           = GamePlatform::findOrError($data['id']);
        // $platform->key      = $data['key'];
        // $platform->name     = $data['name'];
        $platform->maintain                    = $data['maintain'];
        $platform->limit                       = $data['limit'] ?? 0;
        $platform                              = GamePlatformService::updateImage($platform, $data);
        $platform->maintain_crontab            = $data['maintainCrontab'] ?? null;
        $platform->maintain_minute             = $data['maintainMinute'] ?? 0;
        $platform->disposable_maintain_date    = $data['disposableMaintainDate'] ?? null;
        $platform->disposable_maintain         = $data['disposableMaintain'] ?? 0;

        DB::transaction(function () use ($platform, $data) {
            $platform->saveOrError();
            GamePlatformService::updateOrder();
            if ($platform->isLimit()) {
                GamePlatformService::updateLimit($platform, $data['members']);
            }
            $this->clearGameCache();
        });
    }

    /**
     * 驗證維護時間格式.
     *
     * @return void
     */
    public function checkCrontab($data, $db_disposable_maintain_date = null)
    {
        // 驗證 單次的維護日期 必須大於當下時間
        if (! is_null($data['disposableMaintainDate'])) {
            // 如果是修改動作 disposableMaintainDate跟資料庫時間一樣 略過
            if (
                (is_null($db_disposable_maintain_date)) ||
                (
                    ! is_null($db_disposable_maintain_date) &&
                    $db_disposable_maintain_date != $data['disposableMaintainDate']
                )
            ) {
                $now        = Carbon::now();
                $disDate    = Carbon::parse($data['disposableMaintainDate']);
                if ($now->gt($disDate)) {
                    throw new Exception(__('game_platform.disposableMaintainDate'));
                }
            }
        }
        // 驗證使用Crontab的維護日期 是否符合Crontab格式
        try {
            if (! is_null($data['maintainCrontab'])) {
                \Cron\CronExpression::factory($data['maintainCrontab']);
            }
        } catch (Exception  $e) {
            throw new Exception(__('game_platform.crontab_error'));
        }
    }
}