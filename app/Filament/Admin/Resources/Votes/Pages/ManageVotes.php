<?php

namespace App\Filament\Admin\Resources\Votes\Pages;

use App\Actions\Votes\PersistVoteAction;
use App\Enums\VoteStatus;
use App\Enums\VoteType;
use App\Filament\Admin\Resources\Votes\VoteResource;
use App\Models\Vote;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;
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
                ->using(function (array $data): Vote {
                    $data['status'] = VoteStatus::Draft->value;
                    $data['type'] ??= VoteType::Open->value;

                    return app(PersistVoteAction::class)->create(auth()->user(), $data);
                }),
        ];
    }
}

