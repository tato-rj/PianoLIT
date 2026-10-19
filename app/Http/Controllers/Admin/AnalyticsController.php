<?php

namespace App\Http\Controllers\Admin;

use App\Analytics\Analytics;
use App\Analytics\MembershipReport;
use App\Analytics\PieceReport;
use App\Analytics\FolderReport;
use App\Analytics\ComposerReport;
use App\{User, Piece, Tag, Composer, Country, FavoriteFolder};
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class AnalyticsController extends Controller
{
    public function campaigns(Request $request, \App\Services\CampaignAttributionReport $report)
    {
        return view('admin.pages.analytics.campaigns.index', $report->admin($request));
    }

    public function users()
    {
        if (request()->ajax())
            return (new Analytics)->for('users')->query(request('type'), request()->except('type'))->get();

        return view('admin.pages.analytics.users.index');
    }

    public function pieces(Request $request, PieceReport $report)
    {
        if (request()->ajax())
            return (new Analytics)->for('pieces')->query(request('type'), request()->except('type'))->get();

        return view('admin.pages.analytics.pieces.index', ['report' => $report->get($request)]);
    }

    public function favorites(Request $request, FolderReport $report)
    {
        if (request()->ajax())
            return FavoriteFolder::userCreated()->whereNotIn('user_id', \App\Activity\ExcludedAccounts::ids())->datatable();
        
        return view('admin.pages.analytics.favorites.index', $report->get($request));
    }

    public function memberships(Request $request, MembershipReport $report)
    {
        return view('admin.pages.analytics.memberships.index', $report->get($request));
    }

    public function composers(Request $request, ComposerReport $report)
    {
        return view('admin.pages.analytics.composers.index', $report->get($request));
    }

    public function infographs()
    {
        
    }
}
