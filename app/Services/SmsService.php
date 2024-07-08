<?php

namespace App\Services;

use App\Models\Messages;
use GuzzleHttp\Client;

class SmsService
{
    protected $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    public function send(Messages $messages)
    {
        $payload = json_encode($messages->toArray());


        $headers = [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Authorization' => 'Basic RGFsb246VHVhbGlrZTEyMw==',
        ];

        $url = 'https://messaging-service.co.tz/api/sms/v1/text/multi';

        $response = $this->client->post($url, [
            'headers' => $headers,
            'body' => $payload,
        ]);

        return $response->getStatusCode();
    }
}
