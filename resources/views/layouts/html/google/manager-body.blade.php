@env('production')
@unless(\App\Activity\ExcludedAccounts::request(request()))
<noscript><iframe src="https://www.googletagmanager.com/ns.html?id=GTM-KJW7XGP"
height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
@endunless
@endenv
