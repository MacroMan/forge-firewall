<?php

namespace App\Traits;

use App\Components\Forge;
use Laravel\Forge\Forge as LaravelForge;

/**
 * @property LaravelForge $forge
 */
trait UsesForge
{
    /**
     * @param string $key
     * @return mixed
     */
    public function __get($key): mixed
    {
        if ($key === 'forge') {
            return Forge::make();
        }

        return $this->getAttribute($key);
    }
}
