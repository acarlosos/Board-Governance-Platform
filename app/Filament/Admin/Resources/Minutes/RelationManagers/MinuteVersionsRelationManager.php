<?php

namespace App\Filament\Admin\Resources\Minutes\RelationManagers;

use App\Actions\Minutes\CreateMinuteVersionAction;
use App\Enums\MinuteStatus;
use App\Models\Minute;
use App\Models\MinuteVersion;
use App\Support\Filament\RemapValidationToMountedAction;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Textarea;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class MinuteVersionsRelationManager extends RelationManager
{
    protected static string $relationship = 'versions';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('version_number')
            ->columns([
                TextColumn::make('version_number')
                    ->label(__('minute-versions.fields.version_number'))
                    ->sortable(),
                TextColumn::make('changes_summary')
                    ->label(__('minute-versions.fields.changes_summary'))
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->label(__('fields.created_at'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label(__('minute-versions.actions.create'))
                    ->visible(function (): bool {
                        /** @var Minute $minute */
                        $minute = $this->getOwnerRecord();

                        return $minute->status === MinuteStatus::Draft;
                    })
                    ->form([
                        RichEditor::make('content')
                            ->label(__('minutes.fields.content'))
                            ->required()
                            ->extraAttributes(['class' => 'bgp-minute-rich-editor']),
                        Textarea::make('changes_summary')
                            ->label(__('minute-versions.fields.changes_summary')),
                    ])
                    ->using(fn (array $data): MinuteVersion => RemapValidationToMountedAction::run(function () use ($data): MinuteVersion {
                        /** @var Minute $minute */
                        $minute = $this->getOwnerRecord();

                        return app(CreateMinuteVersionAction::class)->create(auth()->user(), $minute, [
                            'content' => (string) $data['content'],
                            'changes_summary' => $data['changes_summary'] ?? null,
                        ]);
                    }, $this)),
            ]);
    }
}
