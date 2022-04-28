<?php

namespace App\Traits;

trait IPHelper
{
    public function getCurrentIP(): string
    {
        return shell_exec("dig +short myip.opendns.com @resolver1.opendns.com");
    }
}