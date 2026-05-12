<?php

namespace App\Filament\Admin\Resources\Notifications;

use App\Actions\Notifications\MarkNotificationAsReadAction;
use App\Actions\Notifications\SendNotificationAction;
use App\Enums\NotificationChannel;
use App\Enums\NotificationStatus;
use App\Filament\Admin\Resources\Notifications\Pages\ManageNotificationsCenter;
use App\Models\NotificationCenter;
use App\Models\User;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class NotificationCenterResource extends Resource
{
    protected static ?string $model = NotificationCenter::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBell;

    protected static ?int $navigationSort = 96;

    public static function getNavigationGroup(): ?string
    {
        return __('notifications.navigation_group');
    }

    public static function getModelLabel(): string
    {
        return __('notifications.model_label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('notifications.plural_label');
    }

    public static function getNavigationLabel(): string
    {
        return __('notifications.navigation_label');
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->label(__('notifications.fields.title'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('user.email')
                    ->label(__('notifications.fields.user'))
                    ->toggleable(),
                TextColumn::make('channel')
                    ->label(__('notifications.fields.channel'))
                    ->badge()
                    ->formatStateUsing(fn ($state): string => __('notifications.channel.'.((string) $state))),
                TextColumn::make('status')
                    ->label(__('notifications.fields.status'))
                    ->badge()
                    ->formatStateUsing(fn ($state): string => __('notifications.status.'.((string) $state))),
                TextColumn::make('read_at')
                    ->label(__('notifications.fields.read_at'))
                    ->dateTime()
                    ->toggleable(),
                TextColumn::make('sent_at')
                    ->label(__('notifications.fields.sent_at'))
                    ->dateTime()
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->label(__('notifications.fields.created_at'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label(__('notifications.filters.status'))
                    ->options([
                        NotificationStatus::Unread->value => __('notifications.status.unread'),
                        NotificationStatus::Read->value => __('notifications.status.read'),
                        NotificationStatus::Sent->value => __('notifications.status.sent'),
                        NotificationStatus::Failed->value => __('notifications.status.failed'),
                    ]),
                TrashedFilter::make(),
            ])
            ->actions([
                Action::make('mark_read')
                    ->label(__('notifications.actions.mark_read'))
                    ->icon(Heroicon::OutlinedCheckCircle)
                    ->visible(fn (NotificationCenter $record): bool => $record->status === NotificationStatus::Unread)
                    ->action(fn (NotificationCenter $record) => app(MarkNotificationAsReadAction::class)->mark(auth()->user(), $record)),

                Action::make('resend_fake')
                    ->label(__('notifications.actions.resend_fake'))
                    ->icon(Heroicon::OutlinedPaperAirplane)
                    ->visible(fn (NotificationCenter $record): bool => $record->channel === NotificationChannel::Email)
                    ->action(fn (NotificationCenter $record) => app(SendNotificationAction::class)->send(auth()->user(), $record)),

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

    public static function getPages(): array
    {
        return [
            'index' => ManageNotificationsCenter::route('/'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->withoutGlobalScopes([SoftDeletingScope::class]); // reason: apenas SoftDeletingScope; incluir trashed no admin; TenantScope mantém-se no query base.

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

