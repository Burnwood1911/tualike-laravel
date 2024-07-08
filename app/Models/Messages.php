<?php

namespace App\Models;

use Illuminate\Support\Collection;

class Messages
{
    public Collection $messages;
    public string $reference;

    public function __construct(array $messages, string $reference)
    {
        $this->messages = collect($messages);
        $this->reference = $reference;
    }

    public function toArray(): array
    {
        return [
            'messages' => $this->messages->map(function ($message) {
                return $message->toArray();
            })->toArray(),
            'reference' => $this->reference,
        ];
    }
}
