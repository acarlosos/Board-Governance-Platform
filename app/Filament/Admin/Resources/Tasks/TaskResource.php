<?php

namespace App\Filament\Admin\Resources\Tasks;

use App\Actions\Tasks\CancelTaskAction;
use App\Actions\Tasks\CompleteTaskAction;
use App\Actions\Tasks\PersistTaskAction;
use App\Actions\Tasks\StartTaskAction;
use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Filament\Admin\Resources\Tasks\Pages\ManageTasks;
use App\Filament\Admin\Resources\Tasks\RelationManagers\TaskCommentsRelationManager;
use App\Filament\Admin\Resources\Tasks\RelationManagers\TaskHistoryRelationManager;
use App\Models\Task;
use App\Models\User;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class TaskResource extends Resource
{
    protected static ?string $model = Task::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static ?int $navigationSort = 70;

    public static function getNavigationGroup(): ?string
    {
        return __('tasks.navigation_group');
    }

    public static function getModelLabel(): string
    {
        return __('tasks.model_label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('tasks.plural_label');
    }

    public static function getNavigationLabel(): string
    {
        return __('tasks.navigation_label');
    }

    public static function form(Schema $schema): Schema
    {
        $sectionLayout = static fn (Section $section): Section => $section
            ->extraAttributes(['class' => 'w-full min-w-0'])
            ->columnSpanFull()
            ->grow()
            ->contained(false)
            ->columns(2);

        return $schema
            ->columns(1)
            ->extraAttributes(['class' => 'w-full min-w-0'])
            ->components([
                $sectionLayout(Section::make(__('tasks.sections.data')))
                    ->components([
                        TextInput::make('title')
                            ->label(__('tasks.fields.title'))
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),
                        Textarea::make('description')
                            ->label(__('tasks.fields.description'))
                            ->columnSpanFull(),
                    ]),

                $sectionLayout(Section::make(__('tasks.sections.assignment')))
                    ->components([
                        Select::make('assigned_to')
                            ->label(__('tasks.fields.assigned_to'))
                            ->relationship(
                                name: 'assignedTo',
                                titleAttribute: 'email',
                                modifyQueryUsing: function (Builder $query): Builder {
                                    $user = auth()->user();
                                    if (! $user instanceof User || $user->isSuperAdmin()) {
                                        return $query;
                                    }
                                    return $query->where('tenant_id', $user->tenant_id);
                                },
                            )
                            ->searchable()
                            ->preload(),
                    ]),

                $sectionLayout(Section::make(__('tasks.sections.due')))
                    ->components([
                        DateTimePicker::make('due_date')
                            ->label(__('tasks.fields.due_date')),
                        Select::make('priority')
                            ->label(__('tasks.fields.priority'))
                            ->required()
                            ->options([
                                TaskPriority::Low->value => __('tasks.priority.low'),
                                TaskPriority::Normal->value => __('tasks.priority.normal'),
                                TaskPriority::High->value => __('tasks.priority.high'),
                                TaskPriority::Urgent->value => __('tasks.priority.urgent'),
                            ]),
                    ]),

                $sectionLayout(Section::make(__('tasks.sections.related')))
                    ->components([
                        Select::make('related_type')
                            ->label(__('tasks.fields.related_type'))
                            ->options([
                                \App\Models\Minute::class => __('minutes.model_label'),
                                \App\Models\Vote::class => __('votes.model_label'),
                                \App\Models\Document::class => __('documents.model_label'),
                                \App\Models\Meeting::class => __('meetings.model_label'),
                            ])
                            ->live(),
                        TextInput::make('related_id')
                            ->label(__('tasks.fields.related_id'))
                            ->numeric(),
                    ]),

                $sectionLayout(Section::make(__('tasks.sections.status')))
                    ->columns(1)
                    ->components([
                        TextInput::make('status')
                            ->label(__('tasks.fields.status'))
                            ->disabled()
                            ->dehydrated(false)
                            ->visible(fn (?Task $record): bool => (bool) $record)
                            ->formatStateUsing(fn (?Task $record): string => $record ? __('tasks.status.'.$record->status->value) : __('tasks.status.pending')),
                        DateTimePicker::make('completed_at')
                            ->label(__('tasks.fields.completed_at'))
                            ->disabled()
                            ->dehydrated(false)
                            ->visible(fn (?Task $record): bool => (bool) $record),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->label(__('tasks.fields.title'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('assignedTo.email')
                    ->label(__('tasks.fields.assigned_to'))
                    ->toggleable(),
                TextColumn::make('priority')
                    ->label(__('tasks.fields.priority'))
                    ->badge()
                    ->formatStateUsing(fn ($state): string => __('tasks.priority.'.((string) $state))),
                TextColumn::make('status')
                    ->label(__('tasks.fields.status'))
                    ->badge()
                    ->formatStateUsing(fn ($state): string => __('tasks.status.'.((string) $state))),
                TextColumn::make('due_date')
                    ->label(__('tasks.fields.due_date'))
                    ->dateTime()
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->label(__('fields.created_at'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label(__('tasks.filters.status'))
                    ->options([
                        TaskStatus::Pending->value => __('tasks.status.pending'),
                        TaskStatus::InProgress->value => __('tasks.status.in_progress'),
                        TaskStatus::Completed->value => __('tasks.status.completed'),
                        TaskStatus::Cancelled->value => __('tasks.status.cancelled'),
                    ]),
                TrashedFilter::make(),
            ])
            ->actions([
                EditAction::make()
                    ->label(__('actions.edit'))
                    ->using(fn (Task $record, array $data): Task => app(PersistTaskAction::class)->update(auth()->user(), $record, $data)),

                Action::make('start')
                    ->label(__('tasks.actions.start'))
                    ->icon(Heroicon::OutlinedPlay)
                    ->visible(fn (Task $record): bool => $record->status === TaskStatus::Pending)
                    ->action(fn (Task $record) => app(StartTaskAction::class)->start(auth()->user(), $record)),

                Action::make('complete')
                    ->label(__('tasks.actions.complete'))
                    ->icon(Heroicon::OutlinedCheckCircle)
                    ->visible(fn (Task $record): bool => $record->status === TaskStatus::InProgress)
                    ->action(fn (Task $record) => app(CompleteTaskAction::class)->complete(auth()->user(), $record)),

                Action::make('cancel')
                    ->label(__('tasks.actions.cancel'))
                    ->icon(Heroicon::OutlinedXCircle)
                    ->visible(fn (Task $record): bool => in_array($record->status, [TaskStatus::Pending, TaskStatus::InProgress], true))
                    ->action(fn (Task $record) => app(CancelTaskAction::class)->cancel(auth()->user(), $record)),

                DeleteAction::make()->label(__('actions.delete')),
                RestoreAction::make()->label(__('actions.restore')),
                ForceDeleteAction::make()->label(__('actions.force_delete')),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            TaskCommentsRelationManager::class,
            TaskHistoryRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageTasks::route('/'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->withoutGlobalScopes([SoftDeletingScope::class]);

        $user = auth()->user();
        if (! $user instanceof User) {
            return $query->whereRaw('1 = 0');
        }

        if ($user->isSuperAdmin()) {
            return $query;
        }

        if ($user->tenant_id === null) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where('tenant_id', $user->tenant_id);
    }
}

