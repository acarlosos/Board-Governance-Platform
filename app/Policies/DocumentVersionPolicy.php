<?php

namespace App\Policies;

use App\Models\Document;
use App\Models\DocumentVersion;
use App\Models\User;

class DocumentVersionPolicy
{
    public function viewAny(User $user, Document $document): bool
    {
        return (new DocumentPolicy)->view($user, $document);
    }

    public function view(User $user, DocumentVersion $version): bool
    {
        return (new DocumentPolicy)->view($user, $version->document);
    }

    public function create(User $user, Document $document): bool
    {
        return (new DocumentPolicy)->update($user, $document);
    }

    public function update(User $user, DocumentVersion $version): bool
    {
        return false;
    }

    public function delete(User $user, DocumentVersion $version): bool
    {
        return (new DocumentPolicy)->update($user, $version->document);
    }

    public function restore(User $user, DocumentVersion $version): bool
    {
        return (new DocumentPolicy)->update($user, $version->document);
    }

    public function forceDelete(User $user, DocumentVersion $version): bool
    {
        return (new DocumentPolicy)->update($user, $version->document);
    }
}

