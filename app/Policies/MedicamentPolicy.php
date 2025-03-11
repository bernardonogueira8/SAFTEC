<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Medicament;
use Illuminate\Auth\Access\HandlesAuthorization;

class MedicamentPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view_any_medicament');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Medicament $medicament): bool
    {
        return $user->can('view_medicament');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('create_medicament');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Medicament $medicament): bool
    {
        return $user->can('update_medicament');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Medicament $medicament): bool
    {
        return $user->can('delete_medicament');
    }

    /**
     * Determine whether the user can bulk delete.
     */
    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_medicament');
    }

    /**
     * Determine whether the user can permanently delete.
     */
    public function forceDelete(User $user, Medicament $medicament): bool
    {
        return $user->can('force_delete_medicament');
    }

    /**
     * Determine whether the user can permanently bulk delete.
     */
    public function forceDeleteAny(User $user): bool
    {
        return $user->can('force_delete_any_medicament');
    }

    /**
     * Determine whether the user can restore.
     */
    public function restore(User $user, Medicament $medicament): bool
    {
        return $user->can('restore_medicament');
    }

    /**
     * Determine whether the user can bulk restore.
     */
    public function restoreAny(User $user): bool
    {
        return $user->can('restore_any_medicament');
    }

    /**
     * Determine whether the user can replicate.
     */
    public function replicate(User $user, Medicament $medicament): bool
    {
        return $user->can('replicate_medicament');
    }

    /**
     * Determine whether the user can reorder.
     */
    public function reorder(User $user): bool
    {
        return $user->can('reorder_medicament');
    }
}
