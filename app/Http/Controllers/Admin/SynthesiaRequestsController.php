<?php

namespace App\Http\Controllers\Admin;

use App\Events\SynthesiaRequests\RequestPublished;
use App\Http\Controllers\Controller;
use App\Services\Admin\SynthesiaRequestsTable;
use App\SynthesiaRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SynthesiaRequestsController extends Controller
{
    public function index(Request $request, SynthesiaRequestsTable $table)
    {
        return view('admin.pages.synthesia-requests.index', $table->get($request));
    }

    public function legacyIndex(Request $request)
    {
        $query = (string) $request->server('QUERY_STRING', '');
        return redirect()->to(route('admin.synthesia-requests.index').($query !== '' ? '?'.$query : ''), 301);
    }

    public function publish(Request $request, SynthesiaRequest $synthesiaRequest, SynthesiaRequestsTable $table)
    {
        $filters = $table->filters(Request::create('/', 'GET', $request->query()));
        // A repeated/stale confirmation cannot change the date or send another email.
        $published = DB::transaction(function () use ($synthesiaRequest) {
            $locked = SynthesiaRequest::whereKey($synthesiaRequest->id)->lockForUpdate()->firstOrFail();
            if ($locked->isPublished()) return null;
            $locked->update(['published_at' => now()]);
            return $locked;
        });
        if ($published) event(new RequestPublished($published));
        return $table->redirect($filters)->with('status', $published ? 'The Synthesia request has been published!' : 'This Synthesia request is already published.');
    }

    public function destroy(Request $request, SynthesiaRequest $synthesiaRequest, SynthesiaRequestsTable $table)
    {
        $filters = $table->filters(Request::create('/', 'GET', $request->query()));
        $synthesiaRequest->delete();
        return $table->redirect($filters)->with('status', 'The Synthesia request has been deleted.');
    }
}
