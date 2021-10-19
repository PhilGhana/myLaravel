<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Models\UserToken;
use App\Services\Bot\TelebotService;
use App\Events\TelebotMessageEvent;

class SendTelebotMessage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    const QUEUE_NAME = 'telebot_msg_queue';

    public $tries = 2;

    public $timeout = 1800;

    protected $messageEvent;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(
        TelebotMessageEvent $messageEvent
    ) {
        $this->queue = self::QUEUE_NAME;
        $this->messageEvent = $messageEvent;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(
        TelebotService $telebotService
    ) {
        $event   = &$this->messageEvent;
        $imgUrl  = $event->imgUrl;
        $message = $event->message;
        $options = $event->options;
        $tokens  = $telebotService->getMemberTokens($event->members);

        if (empty($tokens) || (!$message && !$imgUrl)) {
            return;
        }

        $hasPhoto = strlen($imgUrl) > 0;

        foreach ($tokens as $token) {
            if ($hasPhoto) {
                $telebotService->sendPhotoCaption($token, $imgUrl, $message, $options);
            } else {
                $telebotService->sendMessage($token, $message, $options);
            }
        }
    }

}
