<?php
namespace App\Presenters;

use Illuminate\Support\Str;
use Illuminate\Support\Collection;
use App\Models\QuestRewardCombine;
use App\Models\Marquee;
use App\Models\LetterAnnouncement;
use App\Models\LetterAnnouncementRead;
use App\Modules\TelebotModule;
use App\Services\Bot\TelebotService;

class TelebotPresenter
{

    public function __construct()
    {

    }

    public function timeText(?string $start = null, ?string $end = null)
    {
        $text = '';
        if (!$end) {
            $text = __('telebot.long_term_activity');
        } else if (!$start) {
            $text = __('telebot.from_now_on') . ' ~ ' . $end;
        } else {
            $text = "$start ~ $end";
        }
        return TelebotModule::escapeText($text);
    }

    public function chunkKeyboards(array $keyboards, int $size = 1)
    {
        return collect($keyboards)
        ->chunk($size)
        ->map(function (Collection $collection) {
            return $collection->values();
        })
        ->toArray();
    }

    public function questDetail(QuestRewardCombine $quest, bool $addBack = true)
    {
        $url = config('app.url');
        if ($quest->franchisee) {
            $hosts = json_decode($quest->franchisee->host);
            if (!empty($hosts)) {
                $url = 'https://' . $hosts[0]->url;
            }
        }
        $group   = TelebotModule::escapeText($quest->group->name ?? null);
        $imgPath = $quest->image_small ?? ($quest->image_large ?? null);
        $content = [
            $this->timeText($quest->start_time, $quest->end_time),
            '*' . TelebotModule::escapeText($quest->name) . '*',
        ];
        if ($group) {
            array_unshift($content, "_{$group}_");
        }
        $keyboards = [
            [
                [ 'text' => __('telebot.go_website'), 'url' => $url ],
            ],
        ];
        if ($addBack) {
            $keyboards[] = [
                [
                    'text'          => __('telebot.back_to_list'),
                    'callback_data' => TelebotService::vendorCmd(TelebotService::VENDOR_QUESTS),
                ],
            ];
        }

        return [
            // content
            implode("\n", $content),
            // imgUrl
            $imgPath ? (Str::finish(config('app.quest_image_url'), '/') . $imgPath) : null,
            // options
            [
                'inline_keyboard' => $keyboards,
                'is_temporary'    => $addBack ? true : false,
            ],
        ];
    }

    public function announceDetail(Marquee $marquee)
    {
        $type = TelebotModule::escapeText(__('telebot.announce_type.' . $marquee->type));
        return [
            // content
            TelebotModule::escapeText(__('telebot.site_announce')) . "\[{$type}]\n---\n" . TelebotModule::escapeText($marquee->content),
            // options
            [ ],
        ];
    }

    public function letterDetail(LetterAnnouncement $letter, ?LetterAnnouncementRead $read, bool $addBack = true)
    {
        $keyboards = [
            [
                'text' => __('telebot.mark_read'),
                'callback_data' => TelebotService::vendorCmd(TelebotService::VENDOR_LETTER_READ, [ $letter->id ]),
            ]
        ];
        if ($addBack) {
            $keyboards[] = [
                'text' => __('telebot.back_to_list'),
                'callback_data' => TelebotService::vendorCmd(TelebotService::VENDOR_LETTERS),
            ];
        }
        $type    = TelebotModule::escapeText(__('telebot.letter_type.' . $letter->type));
        $title   = TelebotModule::escapeText($letter->title);
        $tagName = ($letter->letterTag->name ?? null) ? ('#' . TelebotModule::escapeText($letter->letterTag->name)) : '';
        $pinned  = ($read->pin ?? 0) ? '*' : '';
        return [
            // content
            implode("\n", [
                "\[{$type}] $tagName",
                "_{$pinned}{$title}_",
                TelebotModule::escapeText(__('telebot.send_at') . ": {$letter->created_at}"),
                TelebotModule::escapeText(
                    ($read->readed_at ?? null) ? (__('telebot.readed_at') . ": {$read->readed_at}") : ('(' . __('telebot.unread') . ')')
                ),
                '---',
                TelebotModule::escapeText($letter->content),
            ]),
            // options
            [
                'inline_keyboard' => $this->chunkKeyboards($keyboards),
                'is_temporary'    => $addBack ? true : false,
            ],
        ];
    }

}
