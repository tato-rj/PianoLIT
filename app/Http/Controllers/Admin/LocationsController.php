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
            return Location::datatable();

        return view('admin.pages.users.locations.index');
    }

    public function loadMap(Request $request)
    {
        $country = $request->country;
        
        $field = $country ? 'regionName' : 'countryName';

        $countryExists = Location::byCountry($country)->exists();

        $query = $countryExists ? Location::byCountry($country) : new Location;

        $region = $countryExists ? Location::byCountry($country)->first()->countryCode : null;

        $locationsStats = $query->select($field, \DB::raw('count(*) as total'))
                                ->groupBy($field)
                                ->get();

        $array = [['', 'Users']];

        foreach($locationsStats as $location) {
            array_push($array, [$location->$field, $location->total]);
        }

        return ['region' => $region, 'array' => $array];
    }
}
