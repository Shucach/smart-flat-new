<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

final class SmartFrameService
{
    private string $host;

    private string $key;

    public function __construct()
    {
        $this->host = config('services.smart_frame.host');
        $this->key = config('services.smart_frame.key');
    }

    public function getListImages($page, $prePage = 1): array
    {
        $response = Http::withHeaders([
            'Authorization' => $this->key,
        ])->get($this->host."/api/v1/list-images?page={$page}&prePage={$prePage}");

        if ($response->ok()) {
            return [$response->json(), false];
        }

        return [[], $response->json()];
    }

    public function deleteImages(array $names)
    {
        $data = [];
        foreach ($names as $image) {
            $data['names'] = $image;
        }

        $response = Http::asForm()->withHeaders([
            'Authorization' => $this->key,
        ])->post($this->host.'/api/v1/delete-images', $data);

        if ($response->ok()) {
            return [$response->json(), false];
        }

        return [[], $response->json()];
    }

    public function saveImages(array $files)
    {
        $connection = new AMQPStreamConnection('localhost', 5672, 'guest', 'guest');
        $channel = $connection->channel();
        $channel->queue_declare('smart_frame_queue', false, true, false, false);

        foreach ($files as $file) {
            $imageData = base64_encode(file_get_contents($file));
            $message = new AMQPMessage(json_encode([
                'filename' => $file->getClientOriginalName(),
                'data' => $imageData,
            ]), ['delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT]);
            $channel->basic_publish($message, '', 'smart_frame_queue');
        }

        $channel->close();
        $connection->close();

        return [[], false];
    }
}
