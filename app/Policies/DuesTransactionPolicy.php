<?php

namespace App\Policies;

use App\User;
use App\DuesTransaction;
use Illuminate\Auth\Access\HandlesAuthorization;

class DuesTransactionPolicy
{
    use HandlesAuthorization;

    public function view(User $user, DuesTransaction $resource)
    {
        return $user->can('read-dues-transactions');
    }

    public function viewAny(User $user)
    {
        return $user->can('read-dues-transactions');
    }

    public function create(User $user)
    {
        return false;
    }

    public function update(User $user, DuesTransaction $resource)
    {
        return false;
    }

    public function delete(User $user, DuesTransaction $resource)
    {
        return false;
    }

    public function restore(User $user, DuesTransaction $resource)
    {
        return false;
    }

    public function forceDelete(User $user, DuesTransaction $resource)
    {
        return false;
    }
}
