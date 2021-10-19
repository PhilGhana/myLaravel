<?php
namespace App\Listeners;

use Exception;
use DB;
use App\Events\LetterMessageUpdated;
use App\Events\ThrowException;
use App\Events\ReviewUpdated;
use App\Models\LetterMessage;
use App\Models\LetterAnnouncement;
use App\Events\SendAnnouncement;
use App\Models\Member;
use App\Jobs\UpdateReview;

class SyncSocketService
{
    protected function send($uri, $data = null)
    {
        $host   = config('app.socket_server.api_host');
        $port   = config('app.socket_server.port');
        $client = new \GuzzleHttp\Client();

        $host = $port ? "{$host}:{$port}" : $host;
        $host = $host . ($uri && $uri[0] !== '/' ? '/' : '');
        $url  = "{$host}{$uri}";

        try {
            $client->request('POST', $url, [
                'json' => $data,
            ]);
        } catch (Exception $err) {
            event(new ThrowException($err));
        }

    }

    public function messageUpdated(LetterMessageUpdated $event)
    {
        $letter = $event->letter;

        # 有發生變更, 而且「會員已讀」不是空值, 大部份是「代理已讀」的更新
        // $this->send('/letter/message-to-agent', [
        //     'franchiseeId' => $letter->franchisee_id,
        //     'nums'         => LetterMessage::franchiseeNumUnreads($letter->franchisee_id),
        // ]);
        // 使用隊列，避免socket異常的時候，造成系統問題
        UpdateReview::dispatch('/letter/message-to-agent', [
            'franchiseeId' => $letter->franchisee_id,
            'nums'         => LetterMessage::franchiseeNumUnreads($letter->franchisee_id),
        ])->onQueue('message');
    }

    public function sendAnnouncement(SendAnnouncement $announcement)
    {
        $members      = $announcement->data['members'] ?? [];
        $agents       = $announcement->data['agents'] ?? [];
        $franchiseeId = $announcement->data['franchiseeId'] ?? 0;
        $clubRanks    = $announcement->data['clubRanks'] ?? [];
        $tags         = $announcement->data['tags'] ?? [];
        $start        = $announcement->data['registerStart'] ?? null;
        $end          = $announcement->data['registerEnd'] ?? null;
        # 查詢
        if ($start || $end) {
            $query = Member::select('id');
            if ($start) {
                $query->where('created_at', '>', $start);
            }
            if ($end) {
                $query->where('created_at', '<', $end);
            }
            $mids    = $query->get()->pluck('id')->all();
            $members = array_merge($members, $mids);
            $members = array_unique($members);
        }

        $data = [
            'members'      => $members,
            'franchiseeId' => $franchiseeId,
            'tags'         => $tags,
            'clubRanks'    => $clubRanks,
            'agents'       => $agents,
        ];

        // $this->send('/letter/send-announcement', $data);
        // 使用隊列，避免socket異常的時候，造成系統問題
        UpdateReview::dispatch('/letter/send-announcement', $data)->onQueue('announcement');
    }

    public function reviewUpdated(ReviewUpdated $event)
    {
        # 通知 socket service 更新之前，必需先 commit transaction, 否則抓不到這次執行的結果
        DB::commit();
        // $review = $event->review;
        // $type   = classConst($review, 'REVIEW_KEY');
        // $fid    = $review->franchisee_id;
        // $this->send('/review/update', [
        //     'franchiseeId' => $fid,
        //     'type'         => $type,
        // ]);
        // 使用隊列，避免socket異常的時候，造成系統問題
        // 不再需要
        // UpdateReview::dispatch('/review/update', [
        //     'franchiseeId' => $fid,
        //     'type'         => $type,
        // ])->onQueue('review');
    }
}