@if(session()->has('impersonator'))
@alert([
'color' => 'warning', 
'message' => '<i class="fas fa-exclamation-triangle mr-2"></i>You are impersonating ' . possessive(auth()->user()->first_name) . ' account',
'floating' => 'bottom-right'])
@endif