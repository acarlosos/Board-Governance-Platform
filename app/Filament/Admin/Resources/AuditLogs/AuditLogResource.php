<?php

namespace App\Filament\Admin\Resources\AuditLogs;

use App\Enums\AuditAction;
use App\Filament\Admin\Resources\AuditLogs\Pages\ManageAuditLogs;
use App\Models\AuditLog;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class AuditLogResource extends Resource
{
    protected static ?string $model = AuditLog::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldCheck;

    protected static ?int $navigationSort = 90;

    public static function getNavigationGroup(): ?string
    {
        return __('audit.navigation_group');
    }

    public static function getModelLabel(): string
    {
        return __('audit.model_label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('audit.plural_label');
    }

    public static function getNavigationLabel(): string
    {
        return __('audit.navigation_label');
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('action')
                    ->label(__('audit.fields.action'))
                    ->formatStateUsing(fn (?string $state): string => filled($state) ? __('audit.actions.'.$state) : '—')
                    ->badge()
                    ->color('warning')
                    ->sortable(),
                TextColumn::make('tenant.name')
                    ->label(__('audit.fields.tenant'))
                    ->placeholder('—')
                    ->toggleable()
                    ->sortable(),
                TextColumn::make('user.email')
                    ->label(__('audit.fields.user'))
                    ->placeholder('—')
                    ->toggleable()
                    ->sortable(),
                TextColumn::make('auditable_type')
                    ->label(__('audit.fields.auditable_type'))
                    ->formatStateUsing(fn (?string $state): string => $state ? __('audit.auditable_types.'.$state) : '—')
                    ->toggleable(),
                TextColumn::make('auditable_id')
                    ->label(__('audit.fields.auditable_id'))
                    ->placeholder('—')
                    ->toggleable(),
                TextColumn::make('ip_address')
                    ->label(__('audit.fields.ip_address'))
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->label(__('audit.fields.created_at'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('tenant_id')
                    ->label(__('audit.filters.tenant'))
                    ->relationship('tenant', 'name')
                    ->visible(fn (): bool => auth()->user()?->isSuperAdmin() ?? false),
                SelectFilter::make('user_id')
                    ->label(__('audit.filters.user'))
                    ->relationship('user', 'email'),
                SelectFilter::make('action')
                    ->label(__('audit.filters.action'))
                    ->options(collect(AuditAction::cases())->mapWithKeys(fn (AuditAction $a) => [$a->value => __('audit.actions.'.$a->value)])->all()),
                SelectFilter::make('auditable_type')
                    ->label(__('audit.filters.auditable_type'))
                    ->options(fn (): array => AuditLog::query()
                        ->whereNotNull('auditable_type')
                        ->when(
                            ! (auth()->user()?->isSuperAdmin() ?? false),
                            fn (Builder $q) => $q->where('tenant_id', auth()->user()?->tenant_id),
                        )
                        ->distinct()
                        ->orderBy('auditable_type')
                        ->pluck('auditable_type', 'auditable_type')
                        ->mapWithKeys(fn (string $t): array => [$t => __('audit.auditable_types.'.$t)])
                        ->all()),
                Filter::make('created_at_period')
                    ->label(__('audit.filters.period'))
                    ->form([
                        \Filament\Forms\Components\DatePicker::make('from')
                            ->label(__('audit.filters.from'))
                            ->native(false),
                        \Filament\Forms\Components\DatePicker::make('until')
                            ->label(__('audit.filters.until'))
                            ->native(false),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        $from = filled($data['from'] ?? null) ? Carbon::parse($data['from'])->startOfDay() : null;
                        $until = filled($data['until'] ?? null) ? Carbon::parse($data['until'])->endOfDay() : null;

                        return $query
                            ->when($from, fn (Builder $q) => $q->where('created_at', '>=', $from))
                            ->when($until, fn (Builder $q) => $q->where('created_at', '<=', $until));
                    }),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([])
            ->toolbarActions([]);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        $user = auth()->user();
        if ($user && ! $user->isSuperAdmin()) {
            $query->where('tenant_id', $user->tenant_id);
        }

        return $query;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageAuditLogs::route('/'),
        ];
    }
}

