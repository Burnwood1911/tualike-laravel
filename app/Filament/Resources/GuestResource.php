<?php
namespace App\Filament\Resources;

use App\Filament\Resources\GuestResource\Pages;
use App\Models\Guest;
use App\Services\ImportService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class GuestResource extends Resource
{
    protected static ?string $model = Guest::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Select::make('guest_type')
                    ->options([
                        'SINGLE' => 'SINGLE',
                        'DOUBLE' => 'DOUBLE',
                        'NONE'   => 'NONE',
                    ])->native(false),
                Forms\Components\TextInput::make('phone')
                    ->tel()
                    ->required()
                    ->maxLength(255),

                Forms\Components\TextInput::make('qr')
                    ->required()->default(Str::random(4))->maxLength(4),
                Forms\Components\TextInput::make('final_url')
                    ->hiddenOn([Pages\EditGuest::class, Pages\CreateGuest::class])
                    ->maxLength(255),

                Forms\Components\Select::make('event_id')
                    ->required()
                    ->relationship(name: 'event', titleAttribute: 'name')
                    ->searchable()
                    ->preload()
                    ->native(false),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('guest_type')
                    ->searchable(),
                Tables\Columns\TextColumn::make('phone')
                    ->searchable(),
                Tables\Columns\TextColumn::make('uses')
                    ->numeric(),
                Tables\Columns\TextColumn::make('qr')
                    ->searchable(),
                Tables\Columns\IconColumn::make('generated')
                    ->boolean(),

                Tables\Columns\IconColumn::make('dispatched')
                    ->boolean(),
                Tables\Columns\IconColumn::make('whatsapp_dispatched')
                    ->boolean(),
                Tables\Columns\TextColumn::make('attendance_status')
                    ->searchable()
                    ->badge() // Optional: displays status as a badge
                    ->color(fn(string $state): string => match ($state) {
                        'attending'                       => 'success',
                        'not_attending'                   => 'danger',
                        default                           => 'warning',
                    }),
                Tables\Columns\TextColumn::make('event.name')
                    ->numeric()
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('event')
                    ->relationship('event', 'name')
                    ->searchable()
                    ->preload()
                    ->placeholder('All Events'),

                Filter::make('generated')
                    ->query(fn($query) => $query->where('generated', true))
                    ->label('Generated'),
                Filter::make('not_generated')
                    ->query(fn($query) => $query->where('generated', false))
                    ->label('Not Generated'),
                Filter::make('dispatched')
                    ->query(fn($query) => $query->where('dispatched', true))
                    ->label('Dispatched'),
                Filter::make('not_dispatched')
                    ->query(fn($query) => $query->where('dispatched', false))
                    ->label('Not Dispatched'),
                Filter::make('whatsapp_dispatched')
                    ->query(fn($query) => $query->where('whatsapp_dispatched', true))
                    ->label('WhatsApp Dispatched'),
                Filter::make('whatsapp_not_dispatched')
                    ->query(fn($query) => $query->where('whatsapp_dispatched', false))
                    ->label('WhatsApp Not Dispatched'),
                SelectFilter::make('attendance_status')
                    ->options([
                        'attending'     => 'Attending',
                        'not_attending' => 'Not Attending',
                        'pending'       => 'Pending',
                    ])
                    ->placeholder('All Statuses')
                    ->label('Attendance Status'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Action::make('generate')
                    ->action(function (Guest $record) {
                        app(ImportService::class)->generateSingle($record);
                    })
                    ->requiresConfirmation(),
                Action::make('dispatch')
                    ->action(function (Guest $record) {
                        app(ImportService::class)->dispatchSingle($record);
                    })
                    ->requiresConfirmation(),
                Action::make('whatsappSingle')
                    ->label('WhatsApp')
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->form([
                        Forms\Components\Select::make('template_id')
                            ->label('Message Template')
                            ->searchable()
                            ->options(function (): array {
                                try {
                                    $response = Http::withHeaders([
                                        'Authorization' => 'Basic Yjg2YWRlYWIzZTNhMmQ2MzpaRGt3Wm1JMk5qUmxaVGsyWVdVM056aGlNelF3WkRjM1pqa3pNVE5rTjJSbE5tWTRZamhoTVRVMk56VmtPV00yWldJNFltWmlNalZqTlRJM1lUZzJaZz09',
                                        'Content-Type'  => 'application/json',
                                    ])->get('https://apichatcore.beem.africa/v1/message-templates/list');

                                    if ($response->successful()) {
                                        $data = $response->json();

                                        return collect($data['data'] ?? [])
                                            ->mapWithKeys(function ($template) {
                                                return [$template['id'] => $template['name']];
                                            })
                                            ->toArray();
                                    }
                                } catch (\Exception $e) {
                                    \Log::error('Failed to fetch templates: ' . $e->getMessage());
                                }

                                return [];
                            })
                            ->placeholder('Select a template...')
                            ->required(),
                    ])
                    ->action(function (Guest $record, array $data) {
                        app(ImportService::class)->handleDispatchWhatsappSingle($record, $data['template_id']);
                    })
                    ->requiresConfirmation()
                    ->color('success'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])->headerActions([
            Action::make('Generate')
                ->form([
                    Forms\Components\Select::make('event_id')
                        ->required()
                        ->relationship(name: 'event', titleAttribute: 'name')
                        ->searchable()
                        ->preload()
                        ->native(false),
                ])
                ->action(function (array $data) {
                    app(ImportService::class)->handleGenerateBulk($data);

                }),
            Action::make('Dispatch')
                ->form([
                    Forms\Components\Select::make('event_id')
                        ->required()
                        ->relationship(name: 'event', titleAttribute: 'name')
                        ->searchable()
                        ->preload()
                        ->native(false),
                ])
                ->action(function (array $data) {
                    app(ImportService::class)->handleDispatchBulk($data);
                }),
            Action::make('whatsapp')
                ->label('Whatsapp')
                ->form([
                    Forms\Components\Select::make('event_id')
                        ->required()
                        ->relationship(name: 'event', titleAttribute: 'name')
                        ->searchable()
                        ->preload()
                        ->native(false),
                    Forms\Components\Select::make('template_id')
                        ->label('Message Template')
                        ->searchable()
                        ->options(function (): array {
                            try {
                                $response = Http::withHeaders([
                                    'Authorization' => 'Basic Yjg2YWRlYWIzZTNhMmQ2MzpaRGt3Wm1JMk5qUmxaVGsyWVdVM056aGlNelF3WkRjM1pqa3pNVE5rTjJSbE5tWTRZamhoTVRVMk56VmtPV00yWldJNFltWmlNalZqTlRJM1lUZzJaZz09',
                                    'Content-Type'  => 'application/json',
                                ])->get('https://apichatcore.beem.africa/v1/message-templates/list');

                                if ($response->successful()) {
                                    $data = $response->json();

                                    return collect($data['data'] ?? [])
                                        ->mapWithKeys(function ($template) {
                                            return [$template['id'] => $template['name']];
                                        })
                                        ->toArray();
                                }
                            } catch (\Exception $e) {
                                \Log::error('Failed to fetch templates: ' . $e->getMessage());
                            }

                            return [];
                        })
                        ->placeholder('Select a template...')
                        ->required(),
                ])
                ->action(function (array $data): void {
                    $templateId = $data['template_id'];

                    app(ImportService::class)->handleDispatchWhatsapp($data);

                }),
            Action::make('import')
                ->form([
                    Forms\Components\FileUpload::make('attachment')
                        ->required()
                        ->storeFiles(false),
                    Forms\Components\Select::make('event_id')
                        ->required()
                        ->relationship(name: 'event', titleAttribute: 'name')
                        ->searchable()
                        ->preload()
                        ->native(false),
                ])
                ->action(function (array $data) {
                    app(ImportService::class)->handleImportAction($data);
                }),
            Action::make('export')
                ->label('Export Guests')
                ->icon('heroicon-o-arrow-down-tray')
                ->form([
                    Forms\Components\Select::make('event_id')
                        ->required()
                        ->relationship(name: 'event', titleAttribute: 'name')
                        ->searchable()
                        ->preload()
                        ->native(false)
                        ->label('Event'),
                    Forms\Components\Select::make('filters')
                        ->multiple()
                        ->options([
                            'generated' => 'Generated Only',
                            'not_generated' => 'Not Generated Only',
                            'dispatched' => 'SMS Dispatched Only',
                            'not_dispatched' => 'SMS Not Dispatched Only',
                            'whatsapp_dispatched' => 'WhatsApp Dispatched Only',
                            'whatsapp_not_dispatched' => 'WhatsApp Not Dispatched Only',
                            'attending' => 'Attending Only',
                            'not_attending' => 'Not Attending Only',
                            'pending' => 'Pending Only',
                        ])
                        ->placeholder('Select filters (optional)')
                        ->label('Filters')
                        ->native(false),
                ])
                ->action(function (array $data) {
                    $exportService = app(ImportService::class);
                    $exportData = $exportService->handleGuestExport($data);
                    
                    // Store file in R2 with temporary path
                    $filename = $exportData['filename'];
                    $path = 'temp-exports/' . \Illuminate\Support\Str::uuid() . '.csv';
                    
                    // Upload to R2
                    \Storage::disk('r2')->put($path, $exportData['content']);
                    
                    // Generate temporary signed URL (valid for 1 hour)
                    $downloadUrl = \Storage::disk('r2')->temporaryUrl($path, now()->addHour());
                    
                    // Show success notification and provide download link
                    \Filament\Notifications\Notification::make()
                        ->title('Export Ready')
                        ->body('Your guest export is ready for download. Link expires in 1 hour.')
                        ->success()
                        ->actions([
                            \Filament\Notifications\Actions\Action::make('download')
                                ->label('Download CSV')
                                ->url($downloadUrl)
                                ->openUrlInNewTab()
                        ])
                        ->send();
                })
                ->color('info'),

        ])->poll('60s');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListGuests::route('/'),
            'create' => Pages\CreateGuest::route('/create'),
            'view'   => Pages\ViewGuest::route('/{record}'),
            'edit'   => Pages\EditGuest::route('/{record}/edit'),
        ];
    }
}
