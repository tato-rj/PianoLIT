<?php

namespace App\Auth\ApiV2;

use Illuminate\Auth\RequestGuard;

class BearerGuard extends RequestGuard
{
    public function setRequest(\Illuminate\Http\Request $request)
    {
        // Laravel 8's RequestGuard otherwise retains its previous principal in
        // a reused container. Revalidate expiry/revocation on every request.
        $this->user = null;
        return parent::setRequest($request);
    }
}
