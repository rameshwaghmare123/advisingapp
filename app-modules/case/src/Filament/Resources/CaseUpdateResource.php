<?php

namespace Assist\Case\Filament\Resources;

use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use Assist\Case\Models\CaseItem;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Assist\Case\Models\CaseUpdate;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Textarea;
use Illuminate\Database\Eloquent\Builder;
use Assist\Case\Enums\CaseUpdateDirection;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Assist\Case\Filament\Resources\CaseUpdateResource\Pages;

class CaseUpdateResource extends Resource
{
    protected static ?string $model = CaseUpdate::class;

    protected static ?string $navigationGroup = 'Cases';

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('case')
                    ->relationship('case', 'casenumber')
                    ->searchable()
                    ->label('Case')
                    ->translateLabel()
                    ->required()
                    ->exists(
                        table: (new CaseItem())->getTable(),
                        column: (new CaseItem())->getKeyName()
                    ),
                Textarea::make('update')
                    ->label('Update')
                    ->rows(3)
                    ->columnSpan('full')
                    ->required()
                    ->string(),
                Select::make('direction')
                    ->options(collect(CaseUpdateDirection::cases())->mapWithKeys(fn (CaseUpdateDirection $direction) => [$direction->value => $direction->name]))
                    ->label('Direction')
                    ->required()
                    ->enum(CaseUpdateDirection::class),
                Toggle::make('internal')
                    ->label('Internal')
                    ->rule(['boolean']),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                Tables\Columns\TextColumn::make('case.respondent.full')
                    ->label('Respondent')
                    ->sortable(query: function (Builder $query, string $direction, $record): Builder {
                        // TODO: Update this to work with other respondent types
                        return $query->join('case_items', 'case_updates.case_id', '=', 'case_items.id')
                            ->join('students', function ($join) {
                                $join->on('case_items.respondent_id', '=', 'students.sisid')
                                    ->where('case_items.respondent_type', '=', 'student');
                            })
                            ->orderBy('full', $direction);
                    })
                    ->searchable(),
                Tables\Columns\TextColumn::make('case.respondent.sisid')
                    ->label('SIS ID')
                    ->sortable(query: function (Builder $query, string $direction, $record): Builder {
                        // TODO: Update this to work with other respondent types
                        return $query->join('case_items', 'case_updates.case_id', '=', 'case_items.id')
                            ->join('students', function ($join) {
                                $join->on('case_items.respondent_id', '=', 'students.sisid')
                                    ->where('case_items.respondent_type', '=', 'student');
                            })
                            ->orderBy('sisid', $direction);
                    })
                    ->searchable(),
                Tables\Columns\TextColumn::make('case.respondent.otherid')
                    ->label('Other ID')
                    ->sortable(query: function (Builder $query, string $direction, $record): Builder {
                        // TODO: Update this to work with other respondent types
                        return $query->join('case_items', 'case_updates.case_id', '=', 'case_items.id')
                            ->join('students', function ($join) {
                                $join->on('case_items.respondent_id', '=', 'students.sisid')
                                    ->where('case_items.respondent_type', '=', 'student');
                            })
                            ->orderBy('otherid', $direction);
                    })
                    ->searchable(),
                Tables\Columns\TextColumn::make('case.casenumber')
                    ->label('Case')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\IconColumn::make('internal')
                    ->boolean()
                    ->label('Internal'),
                Tables\Columns\TextColumn::make('direction')
                    ->label('Direction')
                    ->formatStateUsing(fn (CaseUpdateDirection $state): string => Str::ucfirst($state->value))
                    ->icon(fn (CaseUpdateDirection $state): string => match ($state) {
                        CaseUpdateDirection::Inbound => 'heroicon-o-arrow-down-tray',
                        CaseUpdateDirection::Outbound => 'heroicon-o-arrow-up-tray',
                    }),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('internal')
                    ->label('Internal')
                    ->translateLabel(),
                Tables\Filters\SelectFilter::make('direction')
                    ->label('Direction')
                    ->translateLabel()
                    ->options(
                        collect(CaseUpdateDirection::cases())
                            ->mapWithKeys(fn (CaseUpdateDirection $direction) => [$direction->value => $direction->name])
                    ),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateActions([
                Tables\Actions\CreateAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                TextEntry::make('case.casenumber')
                    ->label('Case')
                    ->translateLabel()
                    ->url(fn (CaseUpdate $caseUpdate): string => CaseItemResource::getUrl('view', ['record' => $caseUpdate]))
                    ->color('primary'),
                IconEntry::make('internal')
                    ->boolean(),
                TextEntry::make('direction')
                    ->icon(fn (CaseUpdateDirection $state): string => match ($state) {
                        CaseUpdateDirection::Inbound => 'heroicon-o-arrow-down-tray',
                        CaseUpdateDirection::Outbound => 'heroicon-o-arrow-up-tray',
                    })
                    ->formatStateUsing(fn (CaseUpdateDirection $state): string => Str::ucfirst($state->value)),
                TextEntry::make('update')
                    ->columnSpanFull(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCaseUpdates::route('/'),
            'create' => Pages\CreateCaseUpdate::route('/create'),
            'view' => Pages\ViewCaseUpdate::route('/{record}'),
            'edit' => Pages\EditCaseUpdate::route('/{record}/edit'),
        ];
    }
}
