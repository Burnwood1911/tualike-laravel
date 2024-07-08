<?php

namespace App\Services;

use App\Jobs\GenerateSingle;
use App\Models\Card;
use \App\Models\Event;
use App\Models\MessageData;
use App\Models\Messages;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Str;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use App\Models\Guest;


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

    public function handleGenerateBulk(array $data) {

        $eventId = $data['event_id'];

        $guests = Guest::where('event_id', $eventId)
               ->where('generated', false)
               ->get();


        foreach($guests as $guest) {

            GenerateSingle::dispatch($guest->id);
        }

    }

    public function handleDispatchBulk(array $data) {
        $eventId = $data['event_id'];

        $guests = Guest::where('event_id', $eventId)
               ->where('dispatched', false)
               ->get();

        $messages = array();

        foreach($guests as $guest) {

            $messageData = new MessageData("TUALIKE", $guest->phone, "Mamboi");

            array_push($messages, $messageData);

            $guest->update([
                'dispatched' => true
            ]);


        }

        $final = new Messages($messages, Str::uuid());

        $this->smsService->send($final);

    }


    public function dispatchSingle(Guest $guest) {

        $messageData = new MessageData("TUALIKE", $guest->phone, "Mamboi");
        $messages = new Messages(array($messageData), Str::uuid());

        $this->smsService->send($messages);

        $guest->update([
            'dispatched' => true
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
                'uses' => $row[1] == "SINGLE" ? 1 : 2,
                'qr' => Str::random(4),
                'event_id' => $eventId,
            ]);
        }
    }
}
