<?php

/*
<COPYRIGHT>

    Copyright © 2016-2024, Canyon GBS LLC. All rights reserved.

    Advising App™ is licensed under the Elastic License 2.0. For more details,
    see https://github.com/canyongbs/advisingapp/blob/main/LICENSE.

    Notice:

    - You may not provide the software to third parties as a hosted or managed
      service, where the service provides users with access to any substantial set of
      the features or functionality of the software.
    - You may not move, change, disable, or circumvent the license key functionality
      in the software, and you may not remove or obscure any functionality in the
      software that is protected by the license key.
    - You may not alter, remove, or obscure any licensing, copyright, or other notices
      of the licensor in the software. Any use of the licensor’s trademarks is subject
      to applicable law.
    - Canyon GBS LLC respects the intellectual property rights of others and expects the
      same in return. Canyon GBS™ and Advising App™ are registered trademarks of
      Canyon GBS LLC, and we are committed to enforcing and protecting our trademarks
      vigorously.
    - The software solution, including services, infrastructure, and code, is offered as a
      Software as a Service (SaaS) by Canyon GBS LLC.
    - Use of this software implies agreement to the license terms and conditions as stated
      in the Elastic License 2.0.

    For more information or inquiries please visit our website at
    https://www.canyongbs.com or contact us via email at legal@canyongbs.com.

</COPYRIGHT>
*/

namespace AdvisingApp\Interaction\Filament\Resources\InteractionResource\Pages;

use Filament\Actions;
use Filament\Tables\Table;
use Carbon\CarbonInterface;
use Laravel\Pennant\Feature;
use Filament\Actions\ImportAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use App\Filament\Tables\Columns\IdColumn;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;
use AdvisingApp\Interaction\Models\Interaction;
use App\Features\EnableInteractionInitiativesFeature;
use AdvisingApp\Interaction\Imports\InteractionsImporter;
use AdvisingApp\Interaction\Filament\Resources\InteractionResource;

class ListInteractions extends ListRecords
{
    protected static string $resource = InteractionResource::class;

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                IdColumn::make(),
                Feature::active(EnableInteractionInitiativesFeature::class)
                    ? TextColumn::make('initiative.name')
                        ->searchable()
                    : TextColumn::make('campaign.name')
                        ->label('Initiative')
                        ->searchable(),
                TextColumn::make('driver.name')
                    ->searchable(),
                TextColumn::make('division.name')
                    ->searchable(),
                TextColumn::make('outcome.name')
                    ->searchable(),
                TextColumn::make('relation.name')
                    ->searchable(),
                TextColumn::make('status.name')
                    ->searchable(),
                TextColumn::make('type.name')
                    ->searchable(),
                TextColumn::make('start_datetime')
                    ->label('Start Time')
                    ->dateTime(),
                TextColumn::make('end_datetime')
                    ->label('End Time')
                    ->dateTime(),
                TextColumn::make('created_at')
                    ->state(fn ($record) => $record->end_datetime->diffForHumans($record->start_datetime, CarbonInterface::DIFF_ABSOLUTE, true, 6))
                    ->label('Duration'),
                TextColumn::make('subject')
                    ->searchable(),
                TextColumn::make('description')
                    ->searchable(),
            ])
            ->actions([
                EditAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            ImportAction::make()
                ->importer(InteractionsImporter::class)
                ->authorize('import', Interaction::class),
            Actions\CreateAction::make(),
        ];
    }
}
