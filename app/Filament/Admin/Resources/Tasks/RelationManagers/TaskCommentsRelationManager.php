<?php

namespace App\Filament\Admin\Resources\Tasks\RelationManagers;

use App\Actions\Tasks\AddTaskCommentAction;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\Textarea;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class TaskCommentsRelationManager extends RelationManager
{
    protected static string $relationship = 'comments';

    public static function getTitle(\Illuminate\Database\Eloquent\Model $ownerRecord, string $pageClass): string
    {
        return __('task-comments.plural_label');
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                TextColumn::make('user.email')
                    ->label(__('task-comments.fields.user'))
                    ->toggleable(),
                TextColumn::make('comment')
                    ->label(__('task-comments.fields.comment'))
                    ->wrap()
                    ->limit(120),
                TextColumn::make('created_at')
                    ->label(__('fields.created_at'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label(__('task-comments.actions.add'))
                    ->form([
                        Textarea::make('comment')
                            ->label(__('task-comments.fields.comment'))
                            ->required(),
                    ])
                    ->using(function (array $data) {
                        /** @var \App\Models\Task $task */
                        $task = $this->getOwnerRecord();
                        return app(AddTaskCommentAction::class)->add(auth()->user(), $task, (string) $data['comment']);
                    }),
            ])
            ->actions([])
            ->bulkActions([]);
    }
}

