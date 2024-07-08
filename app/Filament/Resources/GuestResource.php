<?php

namespace App\Filament\Resources;

use App\Filament\Resources\GuestResource\Pages;
use App\Filament\Resources\GuestResource\RelationManagers;
use App\Models\Guest;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Filament\Tables\Actions\Action;
use App\Services\ImportService;



use Illuminate\Database\Eloquent\SoftDeletingScope;

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
                    ])->native(false)
                    ->required(),
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
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('qr')
                    ->searchable(),
                Tables\Columns\IconColumn::make('generated')
                    ->boolean(),

                Tables\Columns\IconColumn::make('dispatched')
                    ->boolean(),
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
                //
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
                    ->requiresConfirmation()
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
                            ->native(false)
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
                            ->native(false)
                    ])
                    ->action(function (array $data) {
                        app(ImportService::class)->handleDispatchBulk($data);
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
                            ->native(false)
                    ])
                    ->action(function (array $data) {
                        app(ImportService::class)->handleImportAction($data);
                    })

            ]);
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
            'index' => Pages\ListGuests::route('/'),
            'create' => Pages\CreateGuest::route('/create'),
            'view' => Pages\ViewGuest::route('/{record}'),
            'edit' => Pages\EditGuest::route('/{record}/edit'),
        ];
    }
}
