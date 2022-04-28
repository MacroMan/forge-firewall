<?php

namespace App\Traits;

use App\User;

trait UsesUser
{
    public function user(): User
    {
        return User::getUser();
    }
}
