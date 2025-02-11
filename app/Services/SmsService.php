<?php

namespace App\Services;

use App\Models\Messages;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;

class SmsService
{
    protected Client $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * @throws GuzzleException
     */
    public function send(Messages $messages): int
    {
        try {
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

        Log::info('Sent SMS with payload: {payload} and Response: {response}', ['payload' => $payload, 'response' => $response->getBody()->getContents()]);

        return $response->getStatusCode();
        }catch(\Exception $e) {

            Log::info('Error sending sms: {messgae}', ['message' => $e->getMessage()]);
        }
    }
}
