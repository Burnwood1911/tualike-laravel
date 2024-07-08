<?php

namespace App\Models;

class MessageData
{
    public string $from;
    public string $to;
    public string $text;

    public function __construct(string $from, string $to, string $text)
    {
        $this->from = $from;
        $this->to = $to;
        $this->text = $text;
    }

    public function toArray(): array
    {
        return [
            'from' => $this->from,
            'to' => $this->to,
            'text' => $this->text,
        ];
    }
}
