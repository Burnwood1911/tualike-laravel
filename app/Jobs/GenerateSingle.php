<?php

namespace App\Jobs;

use App\Models\Guest;
use App\Services\ImageService;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class GenerateSingle implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $guest;

    /**
     * Create a new job instance.
     */
    public function __construct($guest_id)
    {
        $this->guest = Guest::findOrFail($guest_id);
    }

    /**
     * Execute the job.
     */
    public function handle(ImageService $imageService): void
    {
        $event = $this->guest->event;
        $card = $event->card;
        $url = $imageService->encode($this->guest, $card);

        $this->guest->update([
            'final_url' => $url,
            'generated' => true,
        ]);
    }
}
