<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use App\ShareableContent;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    public function __construct()
    {
        $gift = \Cache::remember('infographs.gift', minutes(2), function() {
            return \App\Infograph\Infograph::gifts()->inRandomOrder()->first();
        });
        
    	\View::share('gift', $gift);
    }

    public function updateStatusFor(ShareableContent $model)
    {
        $model->updateStatus(request('attribute'));

        $name = strtolower(preg_replace('/(?<=\\w)(?=[A-Z])/'," $1", class_basename($model)));

        $attr = request('attribute') ?? 'published_at';
    	$field = str_replace('_at', '', $attr);
        $message = $model->$attr ? 'The '.$name.' is <u>' . $field . '</u>' : 'The '.$name.' is no longer <u>' . $field . '</u>';

        return view('components.alerts.alert', [
            'color' => 'green',
            'message' => '<i class="fas fa-check-circle mr-2"></i>' . $message,
            'temporary' => true,
            'dismissible' => true,
            'floating' => 'top'
        ])->render();
    }
}
