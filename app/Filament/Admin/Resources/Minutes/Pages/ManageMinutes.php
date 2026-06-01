<?php

namespace App\Filament\Admin\Resources\Minutes\Pages;

use App\Actions\Minutes\CreateMinuteVersionAction;
use App\Actions\Minutes\PersistMinuteAction;
use App\Enums\MinuteStatus;
use App\Filament\Admin\Resources\Minutes\MinuteResource;
use App\Models\Minute;
use App\Support\Filament\RemapValidationToMountedAction;
use Filament\Actions\Action;
use Filament\Actions\Contracts\HasActions;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Support\Enums\Width;

class ManageMinutes extends ManageRecords
{
    protected static string $resource = MinuteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label(__('actions.create'))
                ->modalWidth(Width::FiveExtraLarge)
                ->using(function (array $data, HasActions&HasSchemas $livewire, Action $action): Minute {
                    return RemapValidationToMountedAction::run(function () use ($data): Minute {
                        $data['status'] = MinuteStatus::Draft->value;
                        $minute = app(PersistMinuteAction::class)->create(auth()->user(), $data);

                        // versão inicial (v1) — workflow e versionamento só via Action
                        app(CreateMinuteVersionAction::class)->create(auth()->user(), $minute, [
                            'content' => (string) $data['content'],
                            'changes_summary' => __('minute-versions.initial_version_summary'),
                        ]);

                        return $minute->fresh();
                    }, $livewire, $action);
                }),
        ];
    }
}
