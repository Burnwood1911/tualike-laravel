<?php
namespace App\Filament\Resources\CardResource\Pages;

use App\Filament\Resources\CardResource;
use App\Models\Card;
use Filament\Resources\Pages\Page;

class CardBuilder extends Page
{
    protected static string $resource = CardResource::class;
    protected static string $view     = 'filament.resources.card-resource.pages.card-builder';

    public Card $record;

    public function mount($record): void
    {
        // Handle both ID and model object
        if ($record instanceof Card) {
            $this->record = $record;
        } else {
            $this->record = Card::findOrFail($record);
        }
    }

    public function updateCardData($data)
    {
        $this->record->update($data);
        $this->notify('success', 'Card updated successfully!');
    }
}
