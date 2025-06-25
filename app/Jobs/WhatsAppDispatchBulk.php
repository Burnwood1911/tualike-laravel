<?php

namespace App\Jobs;

use App\Models\Event;
use App\Models\Guest;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Bus;

class WhatsAppDispatchBulk implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $eventId,
        public int $templateId
    ) {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $guests = Guest::where('event_id', $this->eventId)
            ->where('whatsapp_dispatched', false)
            ->get();

        $jobs = [];

        foreach ($guests as $guest) {
            $jobs[] = new WhatsAppDispatchSingle($guest->id, $this->templateId);
        }

        // Dispatch individual jobs in batches with some delay to avoid rate limiting
        Bus::batch($jobs)
            ->allowFailures()
            ->dispatch();
    }
}
