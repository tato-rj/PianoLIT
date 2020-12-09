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
        \Blade::include('components.form.file');
        \Blade::include('components.form.label');
        \Blade::include('components.form.textarea');
        \Blade::include('components.form.options');
        \Blade::include('components.form.error');
        \Blade::include('components.form.tinyeditor');
        \Blade::include('components.form.toggle');
        \Blade::include('components.alert');
        \Blade::include('components.datatable.layout', 'datatable');
        \Blade::include('components.datatable.raw', 'datatableRaw');
        \Blade::include('components.pagination.links', 'pagination');
        \Blade::include('components.pagination.count', 'paginationCount');
        \Blade::include('admin.components.charts.chart');
        \Blade::include('admin.components.charts.ranking');
        \Blade::include('components.tables.list');
        \Blade::include('components.tables.full', 'table');
        \Blade::include('components.return');
        \Blade::include('components.fontawesome', 'fa');
        \Blade::include('components.button');
        \Blade::include('components.pill');
        \Blade::include('components.modal');
        \Blade::include('components.topics');
        \Blade::include('components.addthis');
        \Blade::include('components.tags.tag');
        \Blade::include('components.progressbar');
        \Blade::include('components.title', 'pagetitle');
        \Blade::include('components.popups.popup');
        \Blade::include('components.icon');
        \Blade::include('components.cta.button', 'cta');
    }
}
