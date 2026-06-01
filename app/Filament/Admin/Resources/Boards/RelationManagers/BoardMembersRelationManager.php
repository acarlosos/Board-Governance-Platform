<?php

namespace App\Filament\Admin\Resources\Boards\RelationManagers;

use App\Actions\Boards\PersistBoardMemberAction;
use App\Enums\BoardMemberRole;
use App\Enums\BoardMemberStatus;
use App\Models\Board;
use App\Models\BoardMember;
use App\Support\Filament\RemapValidationToMountedAction;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Component;
use Filament\Support\Enums\Width;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class BoardMembersRelationManager extends RelationManager
{
    protected static string $relationship = 'boardMembers';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('board-members.section_main');
    }

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        $user = auth()->user();

        return $user !== null && $user->can('view', $ownerRecord);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.email')
                    ->label(__('board-members.fields.user'))
                    ->searchable(),
                TextColumn::make('role')
                    ->label(__('board-members.fields.role'))
                    ->formatStateUsing(fn (BoardMemberRole|string $state): string => __('board-members.roles.'.($state instanceof BoardMemberRole ? $state->value : $state)))
                    ->badge(),
                TextColumn::make('status')
                    ->label(__('board-members.fields.status'))
                    ->formatStateUsing(fn (BoardMemberStatus|string $state): string => __('board-members.status.'.($state instanceof BoardMemberStatus ? $state->value : $state)))
                    ->badge(),
                TextColumn::make('joined_at')
                    ->label(__('board-members.fields.joined_at'))
                    ->dateTime()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('left_at')
                    ->label(__('board-members.fields.left_at'))
                    ->dateTime()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label(__('actions.create'))
                    ->modalWidth(Width::FiveExtraLarge)
                    ->form($this->formSchema())
                    ->using(fn (array $data): Model => RemapValidationToMountedAction::run(function () use ($data): Model {
                        /** @var Board $board */
                        $board = $this->getOwnerRecord();

                        return app(PersistBoardMemberAction::class)->create(auth()->user(), $board, $data);
                    }, $this)),
            ])
            ->actions([
                EditAction::make()
                    ->label(__('actions.edit'))
                    ->modalWidth(Width::FiveExtraLarge)
                    ->form($this->formSchema(isEdit: true))
                    ->using(fn (array $data, Model $record): Model => RemapValidationToMountedAction::run(function () use ($data, $record): Model {
                        /** @var BoardMember $record */

                        return app(PersistBoardMemberAction::class)->update(auth()->user(), $record, $data);
                    }, $this)),
                DeleteAction::make()
                    ->label(__('actions.delete'))
                    ->using(function (Model $record): void {
                        /** @var BoardMember $record */
                        app(PersistBoardMemberAction::class)->remove(auth()->user(), $record);
                    }),
            ])
            ->modifyQueryUsing(function (Builder $query): Builder {
                // RelationManager já usa a relação do board, mas reforçamos o tenant_id para segurança.
                /** @var Board $board */
                $board = $this->getOwnerRecord();

                return $query->where('tenant_id', $board->tenant_id);
            });
    }

    /**
     * @return array<int, Component>
     */
    private function formSchema(bool $isEdit = false): array
    {
        /** @var Board $board */
        $board = $this->getOwnerRecord();

        $roleOptions = collect(BoardMemberRole::cases())->mapWithKeys(fn (BoardMemberRole $r) => [$r->value => __('board-members.roles.'.$r->value)])->all();
        $statusOptions = collect(BoardMemberStatus::cases())->mapWithKeys(fn (BoardMemberStatus $s) => [$s->value => __('board-members.status.'.$s->value)])->all();

        return [
            Select::make('user_id')
                ->label(__('board-members.fields.user'))
                ->relationship(
                    name: 'user',
                    titleAttribute: 'email',
                    modifyQueryUsing: fn (Builder $query) => $query->where('tenant_id', $board->tenant_id),
                )
                ->searchable()
                ->preload()
                ->required()
                ->disabled($isEdit),
            Select::make('role')
                ->label(__('board-members.fields.role'))
                ->options($roleOptions)
                ->required()
                ->native(false),
            Select::make('status')
                ->label(__('board-members.fields.status'))
                ->options($statusOptions)
                ->default(BoardMemberStatus::Active->value)
                ->required()
                ->native(false),
            DateTimePicker::make('joined_at')
                ->label(__('board-members.fields.joined_at'))
                ->seconds(false),
            DateTimePicker::make('left_at')
                ->label(__('board-members.fields.left_at'))
                ->seconds(false),
        ];
    }
}
