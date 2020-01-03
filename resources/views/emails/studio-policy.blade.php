@component('mail::message')
# Hi {{$policy->user->first_name}}!

Thank you for creating your Studio Policy with us. To download your policy just click on the button below.

@component('mail::button', ['url' => route('users.studio-policies.show', $policy->id)])
Download my Studio Policy
@endcomponent

Thanks<br>
Elena from {{ config('app.name') }}
@endcomponent

