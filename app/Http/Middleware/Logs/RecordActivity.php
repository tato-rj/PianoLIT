<?php

namespace App\Http\Middleware\Logs;

use App\Activity\Recorder;
use Closure;

class RecordActivity
{
    public function handle($request, Closure $next)
    {
        $recorder = new Recorder;
        if (!$recorder->eligible($request) || $request->attributes->get('activity.recording')) return $next($request);
        $request->attributes->set('activity.recording', true);
        $request->attributes->set('activity.actor', auth('web')->id());
        $started = microtime(true);
        $page = null;
        try {
            $request->attributes->set('activity.visitor', $recorder->visitor($request));
            $page = $recorder->context($request);
            if ($request->isMethod('GET') && !$request->ajax() && !$request->expectsJson()
                && (!$request->hasHeader('Accept') || strpos($request->header('Accept'), 'text/html') !== false)) {
                $page = $recorder->openPage($request);
                $request->attributes->set('activity.page', $page);
            }
        } catch (\Throwable $exception) {
            report($exception);
        }
        try {
            $response = $next($request);
        } catch (\Throwable $exception) {
            $status = $exception instanceof \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface ? $exception->getStatusCode()
                : ($exception instanceof \Illuminate\Validation\ValidationException ? 422 : ($exception instanceof \Illuminate\Database\Eloquent\ModelNotFoundException ? 404 : 500));
            try { $recorder->request($request, new \Symfony\Component\HttpFoundation\Response('', $status), $page, $started); }
            catch (\Throwable $loggingException) { report($loggingException); }
            throw $exception;
        }
        if ($request->attributes->has('activity.page') && (! $response->isSuccessful() || stripos($response->headers->get('Content-Type', ''), 'text/html') === false)) {
            try { \DB::table('activity_pages')->where('id', $page->id)->delete(); }
            catch (\Throwable $exception) { report($exception); }
            $request->attributes->remove('activity.page'); $page = null;
        }
        try {
            $recorder->request($request, $response, $page, $started);
        } catch (\Throwable $exception) {
            // Analytics must not turn a successful account action into an error.
            report($exception);
        }
        return $response;
    }
}
