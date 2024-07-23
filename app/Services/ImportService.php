<?php

namespace App\Services;

use App\Jobs\GenerateSingle;
use App\Models\Event;
use App\Models\Guest;
use App\Models\MessageData;
use App\Models\Messages;
use Filament\Notifications\Notification;
use Illuminate\Bus\Batch;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Facades\Excel;
use Throwable;

class GuestImport implements ToCollection
{
    public function collection(Collection $rows)
    {
        return $rows;
    }
}

class ImportService
{
    protected $imageService;

    protected $smsService;

    public function __construct(ImageService $imageService, SmsService $smsService)
    {
        $this->imageService = $imageService;
        $this->smsService = $smsService;
    }

    public function handleGenerateBulk(array $data)
    {
        $eventId = $data['event_id'];

        $guests = Guest::where('event_id', $eventId)
            ->where('generated', false)
            ->get();

        $jobs = [];

        foreach ($guests as $guest) {
            $jobs[] = new GenerateSingle($guest->id);
        }

        Bus::batch($jobs)->allowFailures()->catch(function (Batch $batch, Throwable $e) {
            Notification::make()
                ->title('Batch Job Failed')
                ->body($e->getMessage())
                ->danger()
                ->send();
        })->progress(function (Batch $batch) {

            Notification::make()
                ->title('Batch Job Progress')
                ->body('Processed job'.$batch->progress().' of '.$batch->totalJobs)
                ->success()
                ->send();

        })->dispatch();

    }

    // public function handleGenerateBulk(array $data)
    // {

    //     $eventId = $data['event_id'];

    //     $guests = Guest::where('event_id', $eventId)
    //         ->where('generated', false)
    //         ->get();

    //     foreach ($guests as $guest) {

    //         GenerateSingle::dispatch($guest->id);
    //     }

    // }

    public function handleDispatchBulk(array $data)
    {
        $eventId = $data['event_id'];

        $guests = Guest::where('event_id', $eventId)
            ->where('dispatched', false)
            ->get();

        $messages = [];

        foreach ($guests as $guest) {

            $event = Event::find($eventId);

            $template = $event->sms_template;

            $channel = $event->sms_channel;

            $template = str_replace('[NAME]', $guest->name, $template);
            $template = str_replace('[LINK]', $guest->final_url, $template);
            $template = str_replace('[CODE]', $guest->qr, $template);

            $messageData = new MessageData($channel, $guest->phone, $template);

            array_push($messages, $messageData);

            $guest->update([
                'dispatched' => true,
            ]);

        }

        $final = new Messages($messages, Str::uuid());

        $this->smsService->send($final);

    }

    public function dispatchSingle(Guest $guest)
    {

        $event = Event::find($guest->event_id);

        $template = $event->sms_template;

        $channel = $event->sms_channel;

        $template = str_replace('[NAME]', $guest->name, $template);
        $template = str_replace('[LINK]', $guest->final_url, $template);
        $template = str_replace('[CODE]', $guest->qr, $template);

        $messageData = new MessageData($channel, $guest->phone, $template);
        $messages = new Messages([$messageData], Str::uuid());

        $this->smsService->send($messages);

        $guest->update([
            'dispatched' => true,
        ]);

    }

    public function generateSingle(Guest $guest)
    {
        GenerateSingle::dispatch($guest->id);
    }

    public function handleImportAction(array $data)
    {

        $path = $data['attachment']->getPathname();
        $eventId = $data['event_id'];

        $event = Event::find($eventId);

        $card = $event->card();

        $excelData = Excel::toCollection(new GuestImport, $path);

        $dd = $excelData[0];

        foreach ($dd as $row) {

            Guest::create([
                'name' => $row[0],
                'guest_type' => $row[1],
                'phone' => $row[2],
                'uses' => $row[1] == 'SINGLE' ? 1 : 2,
                'qr' => Str::random(4),
                'event_id' => $eventId,
            ]);
        }
    }
}
