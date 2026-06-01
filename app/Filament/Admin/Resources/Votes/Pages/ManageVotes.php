<?php

namespace App\Filament\Admin\Resources\Votes\Pages;

use App\Actions\Votes\PersistVoteAction;
use App\Enums\VoteStatus;
use App\Enums\VoteType;
use App\Filament\Admin\Resources\Votes\VoteResource;
use App\Models\Vote;
use App\Support\Filament\RemapValidationToMountedAction;
use Filament\Actions\Contracts\HasActions;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Support\Enums\Width;

class ManageVotes extends ManageRecords
{
    protected static string $resource = VoteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label(__('actions.create'))
                ->modalWidth(Width::FiveExtraLarge)
                ->modalDescription(__('votes.hints.add_options_after_save'))
                ->using(function (array $data, HasActions&HasSchemas $livewire): Vote {
                    return RemapValidationToMountedAction::run(function () use ($data): Vote {
                        $data['status'] = VoteStatus::Draft->value;
                        $data['type'] ??= VoteType::Open->value;

                        return app(PersistVoteAction::class)->create(auth()->user(), $data);
                    }, $livewire);
                }),
        ];
    }
}
