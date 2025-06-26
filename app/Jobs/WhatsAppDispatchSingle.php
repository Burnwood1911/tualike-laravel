<?php

namespace App\Jobs;

use App\Models\Guest;
use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppDispatchSingle implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 60;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $guestId,
        public int $templateId
    ) {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $guest = Guest::find($this->guestId);
        
        if (!$guest) {
            Log::error("Guest not found for WhatsApp dispatch: {$this->guestId}");
            return;
        }

        if ($guest->whatsapp_dispatched) {
            Log::info("Guest {$guest->name} already has WhatsApp dispatched");
            return;
        }

        $link = $guest->final_url;

        $payload = [
            'from_addr'           => '255696971941',
            'destination_addr'    => [
                [
                    'phoneNumber' => $guest->phone,
                    'params'      => [
                        $guest->name,
                        $guest->qr,
                    ],
                ],
            ],
            'channel'             => 'whatsapp',
            'content'             => [
                'mediaUrl' => $link,
            ],
            'messageTemplateData' => [
                'isTemplateMessage' => true,
                'id'                => $this->templateId,
            ],
        ];

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Basic Yjg2YWRlYWIzZTNhMmQ2MzpaRGt3Wm1JMk5qUmxaVGsyWVdVM056aGlNelF3WkRjM1pqa3pNVE5rTjJSbE5tWTRZamhoTVRVMk56VmtPV00yWldJNFltWmlNalZqTlRJM1lUZzJaZz09',
                'Content-Type'  => 'application/json',
            ])
                ->timeout(30)
                ->post('https://apibroadcast.beem.africa/v1/broadcast/template/api-send', $payload);

            if ($response->successful()) {
                $guest->update([
                    'whatsapp_dispatched' => true,
                ]);
                Log::info("WhatsApp message sent successfully to {$guest->name}: " . $response->body());
            } else {
                Log::error("WhatsApp API error for {$guest->name}: " . $response->body() . " " . $link);
                throw new \Exception("WhatsApp API error: " . $response->body());
            }
        } catch (\Exception $e) {
            Log::error("WhatsApp API exception for {$guest->name}: " . $e->getMessage());
            throw $e;
        }
    }
}
