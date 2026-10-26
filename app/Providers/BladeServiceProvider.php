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
        \Blade::include('components.forms.upload.image');
        \Blade::include('components.forms.input');
        \Blade::include('components.forms.file');
        \Blade::include('components.forms.label');
        \Blade::include('components.forms.textarea');
        \Blade::include('components.forms.options');
        \Blade::include('components.forms.select');
        \Blade::include('components.forms.error');
        \Blade::include('components.forms.tinyeditor');
        \Blade::include('components.forms.toggle');
        \Blade::include('components.forms.submit');
        \Blade::include('components.feedback.alert');
        \Blade::include('components.icons.flag');
        \Blade::include('components.media.video');
        \Blade::include('admin.components.datatables.layout', 'datatable');
        \Blade::include('components.navigation.pagination.links', 'pagination');
        \Blade::include('components.navigation.pagination.count', 'paginationCount');
        \Blade::include('admin.components.charts.chart');
        \Blade::include('admin.components.charts.ranking');
        \Blade::include('components.tables.table', 'table');
        \Blade::include('components.icons.fontawesome', 'fa');
        \Blade::include('components.forms.button');
        \Blade::include('components.navigation.pill');
        \Blade::include('components.dialogs.modal');
        \Blade::include('components.navigation.topics');
        \Blade::include('components.media.addthis');
        \Blade::include('components.navigation.tags.tag');
        \Blade::include('components.feedback.progressbar');
        \Blade::include('components.layout.title', 'pagetitle');
        \Blade::include('components.promotions.popups.popup');
        \Blade::include('components.branding.icon', 'brandIcon');
        \Blade::directive('icon', function ($expression) {
            return "<?php echo \\App\\Support\\Icon::render($expression); ?>";
        });
        \Blade::include('components.promotions.app-links.button', 'cta');
        \Blade::include('admin.components.qrcode');
    }
}
