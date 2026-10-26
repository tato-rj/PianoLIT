<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Support\AdminPagination;

class NotificationsController extends Controller
{
    public function panel()
    {
        $query = auth('admin')->user()->unreadNotifications();
        $count = (clone $query)->count();
        $notifications = $count ? $query->limit(AdminPagination::PER_PAGE)->get(['id', 'data', 'created_at']) : collect();

        return response()->json([
            'unreadCount' => $count,
            'html' => view('admin.components.notifications.list', ['unreadNotifications' => $notifications])->render(),
        ]);
    }

    public function read(Request $request)
    {
        $data = $request->validate(['ids' => 'sometimes|array', 'ids.*' => 'required|uuid']);
        $query = auth('admin')->user()->unreadNotifications();
        if (isset($data['ids'])) $query->whereIn('id', $data['ids']);
        $query->update(['read_at' => now()]);

        if ($request->wantsJson())
            return $this->panel();

        return redirect()->to($request->input('url', route('admin.home')));
    }

    public function unread(Request $request)
    {
        $data = $request->validate(['ids' => 'sometimes|array', 'ids.*' => 'required|uuid']);
        if (isset($data['ids'])) {
            auth('admin')->user()->notifications()->whereNotNull('read_at')
                ->whereIn('id', $data['ids'])->update(['read_at' => null]);
        }

        if ($request->wantsJson())
            return $this->panel();

        return redirect()->to($request->input('url', route('admin.home')));
    }
}
