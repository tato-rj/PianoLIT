@if(session()->has('impersonator'))
@alert([
'color' => 'warning', 
'message' => \App\Support\Icon::render('triangle-alert', ['mr' => 2]) . 'You are impersonating ' . possessive(auth()->user()->first_name) . ' account',
'floating' => 'bottom-right'])
@endif