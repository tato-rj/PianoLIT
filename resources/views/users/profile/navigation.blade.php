@php($downloadsPage = request()->routeIs('users.purchases'))
@include('components.navigation.section-navigation', ['id' => 'profile-navigation', 'label' => 'Profile sections', 'trackSections' => ! $downloadsPage, 'items' => [
    ['url' => $downloadsPage ? route('users.profile').'#profile' : '#profile', 'label' => 'Profile', 'icon' => 'user-round'],
    ['url' => $downloadsPage ? route('users.profile').'#security' : '#security', 'label' => 'Security & linked accounts', 'icon' => 'shield-check'],
    ['url' => $downloadsPage ? route('users.profile').'#email-preferences' : '#email-preferences', 'label' => 'Email preferences', 'icon' => 'mails'],
    ['url' => route('users.purchases'), 'label' => 'My downloads', 'icon' => 'download', 'current' => $downloadsPage],
    ['url' => $downloadsPage ? route('users.profile').'#delete-account' : '#delete-account', 'label' => 'Delete account', 'icon' => 'trash-2'],
]])
