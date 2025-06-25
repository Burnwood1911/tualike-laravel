<?php
namespace App\Services;

use App\Jobs\GenerateSingle;
use App\Jobs\WhatsAppDispatchBulk;
use App\Jobs\WhatsAppDispatchSingle;
use App\Models\Event;
use App\Models\Guest;
use App\Models\MessageData;
use App\Models\Messages;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Facades\Excel;
use Throwable;

class GuestImport implements ToCollection
{
    public function collection(Collection $rows): Collection
    {
        return $rows;
    }
}

class ImportService
{
    protected ImageService $imageService;

    protected SmsService $smsService;

    public function __construct(ImageService $imageService, SmsService $smsService)
    {
        $this->imageService = $imageService;
        $this->smsService   = $smsService;
    }

    /**
     * @throws Throwable
     */
    public function handleGenerateBulk(array $data): void
    {
        $eventId = $data['event_id'];

        $guests = Guest::where('event_id', $eventId)
            ->where('generated', false)
            ->get();

        $jobs = [];

        foreach ($guests as $guest) {
            $jobs[] = new GenerateSingle($guest->id);
        }

        Bus::batch($jobs)->allowFailures()->dispatch();

    }

    public function handleDispatchWhatsapp(array $data): void
    {
        $eventId    = $data['event_id'];
        $templateId = (int) $data['template_id'];

        // Dispatch the bulk WhatsApp job
        WhatsAppDispatchBulk::dispatch($eventId, $templateId);
    }

    public function handleDispatchBulk(array $data): void
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

            $link = 'https://tualike.com/guest/' . $eventId . "/" . $guest->id;

            $template = str_replace('[NAME]', $guest->name, $template);
            $template = str_replace('[LINK]', $link, $template);
            $template = str_replace('[CODE]', $guest->qr, $template);

            $messageData = new MessageData($channel, $guest->phone, $template);

            $messages[] = $messageData;

            $guest->update([
                'dispatched' => true,
            ]);

        }

        $final = new Messages($messages, Str::uuid());

        $this->smsService->send($final);

    }

    public function dispatchSingle(Guest $guest): void
    {

        $event = Event::find($guest->event_id);

        $template = $event->sms_template;

        $channel = $event->sms_channel;

        $link = 'https://tualike.com/guest/' . $event->id . "/" . $guest->id;

        $template = str_replace('[NAME]', $guest->name, $template);
        $template = str_replace('[LINK]', $link, $template);
        $template = str_replace('[CODE]', $guest->qr, $template);

        $messageData = new MessageData($channel, $guest->phone, $template);
        $messages    = new Messages([$messageData], Str::uuid());

        $this->smsService->send($messages);

        $guest->update([
            'dispatched' => true,
        ]);

    }

    public function handleDispatchWhatsappSingle(Guest $guest, int $templateId): void
    {
        // Dispatch the single WhatsApp job
        WhatsAppDispatchSingle::dispatch($guest->id, $templateId);
    }

    public function generateSingle(Guest $guest): void
    {
        GenerateSingle::dispatch($guest->id);
    }

    public function handleImportAction(array $data): void
    {

        $path    = $data['attachment']->getPathname();
        $eventId = $data['event_id'];

        $excelData = Excel::toCollection(new GuestImport, $path);

        $dd = $excelData[0];

        foreach ($dd as $row) {

            Guest::create([
                'name'       => $row[0],
                'guest_type' => $row[1],
                'phone'      => $row[2],
                'uses'       => $row[1] == 'SINGLE' ? 1 : 2,
                'qr'         => Str::random(4),
                'event_id'   => $eventId,
            ]);
        }
    }

    public function handleGuestExport(array $data)
    {
        $eventId = $data['event_id'];
        $filters = $data['filters'] ?? [];

        // Start with guests from the selected event
        $query = Guest::where('event_id', $eventId)->with('event');

        // Apply filters
        foreach ($filters as $filter) {
            switch ($filter) {
                case 'generated':
                    $query->where('generated', true);
                    break;
                case 'not_generated':
                    $query->where('generated', false);
                    break;
                case 'dispatched':
                    $query->where('dispatched', true);
                    break;
                case 'not_dispatched':
                    $query->where('dispatched', false);
                    break;
                case 'whatsapp_dispatched':
                    $query->where('whatsapp_dispatched', true);
                    break;
                case 'whatsapp_not_dispatched':
                    $query->where('whatsapp_dispatched', false);
                    break;
                case 'attending':
                    $query->where('attendance_status', 'attending');
                    break;
                case 'not_attending':
                    $query->where('attendance_status', 'not_attending');
                    break;
                case 'pending':
                    $query->where('attendance_status', 'pending');
                    break;
            }
        }

        $guests = $query->get();
        $event = $guests->first()?->event;

        // Create CSV content
        $csvContent = [];
        
        // CSV Headers
        $csvContent[] = [
            'Name',
            'Guest Type',
            'Phone',
            'Uses',
            'QR Code',
            'Generated',
            'SMS Dispatched',
            'WhatsApp Dispatched',
            'Attendance Status',
            'Event Name',
            'Final URL',
            'Created At',
            'Updated At'
        ];

        // CSV Data
        foreach ($guests as $guest) {
            $csvContent[] = [
                $guest->name,
                $guest->guest_type,
                $guest->phone,
                $guest->uses,
                $guest->qr,
                $guest->generated ? 'Yes' : 'No',
                $guest->dispatched ? 'Yes' : 'No',
                $guest->whatsapp_dispatched ? 'Yes' : 'No',
                $guest->attendance_status ?? 'pending',
                $guest->event?->name ?? '',
                $guest->final_url ?? '',
                $guest->created_at?->format('Y-m-d H:i:s') ?? '',
                $guest->updated_at?->format('Y-m-d H:i:s') ?? ''
            ];
        }

        // Generate filename
        $eventName = $event?->name ? Str::slug($event->name) : 'event';
        $filterSuffix = empty($filters) ? '' : '-' . implode('-', $filters);
        $filename = "guests-{$eventName}{$filterSuffix}-" . now()->format('Y-m-d-H-i-s') . '.csv';

        // Create CSV file content
        $output = fopen('php://temp', 'r+');
        foreach ($csvContent as $row) {
            fputcsv($output, $row);
        }
        rewind($output);
        $csvData = stream_get_contents($output);
        fclose($output);

        // Return data for the action to handle
        return [
            'content' => $csvData,
            'filename' => $filename
        ];
    }
}
