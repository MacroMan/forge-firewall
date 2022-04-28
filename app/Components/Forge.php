<?php

namespace App\Components;

use Illuminate\Support\Facades\Cache;
use Laravel\Forge\Forge as LaravelForge;

class Forge {

    /**
     * Get an instance of Laravel Forge
     *
     * @return LaravelForge
     */
    public static function make(): LaravelForge
    {
        $instance = Cache::get('forge_instance');

        if (!$instance) {
            $instance = new LaravelForge(env('FORGE_TOKEN'));

            Cache::put('forge_instance', $instance);
        }

        return $instance;
    }
}
