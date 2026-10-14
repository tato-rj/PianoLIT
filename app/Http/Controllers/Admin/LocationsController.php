<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Location;

class LocationsController extends Controller
{
    public function index()
    {
        if (request()->ajax())
            return \App\Activity\ExcludedAccounts::owned(Location::query())->datatable();

        return view('admin.pages.users.locations.index');
    }

    public function loadMap(Request $request)
    {
        $country = $request->country;
        
        $field = $country ? 'regionName' : 'countryName';

        $base = \App\Activity\ExcludedAccounts::owned(Location::query());
        $countryExists = (clone $base)->byCountry($country)->exists();

        $query = $countryExists ? (clone $base)->byCountry($country) : $base;

        $region = $countryExists ? (clone $base)->byCountry($country)->first()->countryCode : null;

        $locationsAnalytics = $query->select($field, \DB::raw('count(*) as total'))
                                ->groupBy($field)
                                ->get();

        $array = [['', 'Users']];

        foreach($locationsAnalytics as $location) {
            array_push($array, [$location->$field, $location->total]);
        }

        return ['region' => $region, 'array' => $array];
    }
}
