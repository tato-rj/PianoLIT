<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class RedirectsController extends Controller
{
    public function support(Request $request)
    {
        $query = (string) $request->server('QUERY_STRING', '');

        return redirect()->away(route('contact').($query !== '' ? '?'.$query : ''), 301);
    }

    public function learn(Request $request)
    {
        $origin = parse_url(route('crashcourses.index'));
        $learn = $origin['scheme'].'://'.$origin['host'].(isset($origin['port']) ? ':'.$origin['port'] : '');
        $path = preg_replace('#^/(?:resources/infographs|infographs)(?=/|\?|$)#', '/infographics', $request->getRequestUri());
        $path = preg_replace('#^/resources/great-pianists(?=/|\?|$)#', '/great-pianists', $path);
        $path = preg_replace('#^/tools/(?=(?:circle-of-fifths|chord-finder|scales|arpeggios)(?:/|\?|$))#', '/', $path);
        abort_if($request->getSchemeAndHttpHost() === $learn && $request->getRequestUri() === $path, 404);

        return redirect()->away($learn.$path, 301);
    }

    public function shop(Request $request)
    {
        $shop = rtrim(route('shop.home'), '/');
        abort_if($request->getHost() === parse_url($shop, PHP_URL_HOST), 404);

        return redirect()->away($shop.$request->getRequestUri(), 301);
    }

    public function blog(Request $request)
    {
        $suffix = substr($request->getRequestUri(), strlen('/blog'));

        return redirect()->away(rtrim(route('posts.index'), '/').$suffix, 301);
    }

    public function admin(Request $request)
    {
        $suffix = substr($request->getRequestUri(), strlen('/admin'));

        return redirect()->away(rtrim(route('admin.home'), '/').$suffix, 301);
    }

    public function youtube()
    {
    	return redirect(config('services.channels.youtube'));
    }

    public function ios()
    {
    	return redirect(config('app.stores.ios'));
    }
}
