<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class UpdateReview implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 15;

    protected $uri = '';
    protected $data = null;

    public function __construct($uri, $data)
    {
        $this->uri = $uri;
        $this->data = $data;
    }

    protected function send($uri, $data = null)
    {
        $host   = config('app.socket_server.api_host');
        $port   = config('app.socket_server.port');
        $client = new \GuzzleHttp\Client();
        $host   = "{$host}:{$port}" . ($uri && $uri[0] !== '/' ? '/' : '');
        $url    = "{$host}{$uri}";

        try {
            $client->request('POST', $url, [
                'json' => $data,
            ]);
        } catch (Exception $err) {
            event(new ThrowException($err));
        }
    }

    /**
     * 執行任務。
     *
     * @return void
     */
    public function handle()
    {
        $this->send($this->uri, $this->data);
    }
}
