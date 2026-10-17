<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\WebAppAccessOverview;
use App\Services\WebApp\AccessPolicy;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AccessLimitsController extends Controller
{
    public function index(WebAppAccessOverview $overview, AccessPolicy $policy)
    {
        return view('admin.pages.settings.access-limits', [
            'sections' => $overview->sections($policy), 'revision' => $policy->snapshot()['revision'],
        ]);
    }

    public function update(Request $request, AccessPolicy $policy)
    {
        $features = AccessPolicy::features();
        $validation = ['revision' => 'required|integer|min:0|max:4294967294', 'rules' => ['required', 'array:'.implode(',', array_keys($features))]];
        foreach ($features as $scope => $definitions) {
            $validation['rules.'.$scope] = ['required', 'array:'.implode(',', array_keys($definitions))];
            foreach ($definitions as $feature => $definition) {
                $validation['rules.'.$scope.'.'.$feature] = ['required', 'array:'.implode(',', AccessPolicy::ROLES)];
                foreach (AccessPolicy::ROLES as $role) {
                    $options = $role === 'guest' && !empty($definition['account']) ? ['blocked'] : $definition['options'];
                    $validation['rules.'.$scope.'.'.$feature.'.'.$role] = ['required', Rule::in($options)];
                }
            }
        }
        $data = $request->validate($validation);
        if (!$policy->save($data['rules'], (int) $data['revision'], auth('admin')->id())) {
            return redirect()->route('admin.access-limits.index')->with('error', 'Access limits changed in another tab. The latest settings are shown; review them before saving again.');
        }
        return redirect()->route('admin.access-limits.index')->with('status', 'Access limits saved. The web app will use them on its next request.');
    }
}
