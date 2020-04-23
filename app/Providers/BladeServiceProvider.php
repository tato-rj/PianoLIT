<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class BladeServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        \Blade::include('components.form.upload.image');
        \Blade::include('components.form.input');
        \Blade::include('components.form.label');
        \Blade::include('components.form.textarea');
        \Blade::include('components.form.options');
        \Blade::include('components.form.error');
        \Blade::include('components.form.tinyeditor');
        \Blade::include('components.form.toggle');
        \Blade::include('components.alerts.alert');
        \Blade::include('components.datatable.layout', 'datatable');
        \Blade::include('components.datatable.raw', 'datatableRaw');
        \Blade::include('components.pagination');
        \Blade::include('admin.components.charts.chart');
        \Blade::include('admin.components.charts.ranking');
        \Blade::include('components.tables.list');
        \Blade::include('components.tables.full', 'table');
        \Blade::include('components.return');
        \Blade::include('components.fontawesome', 'fa');
        \Blade::include('components.button');
        \Blade::include('components.pill');
        \Blade::include('components.modal');

        \Blade::directive('popup', function ($card) {
            return view('components.overlays.subscribe.model-2');
        });
    }
}
