<?php

namespace LibreEHR\Core\Contracts;

use Illuminate\Support\ServiceProvider;

class ModuleBase extends PluginBase
{
    /**
     * Perform post-registration booting of services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }

    /**
     * Register any package services.
     *
     * @return void
     */
    public function register()
    {
        //
    }
}