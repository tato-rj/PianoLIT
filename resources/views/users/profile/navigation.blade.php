@include('components.navigation.section-navigation', ['id' => 'profile-navigation', 'label' => 'Profile sections', 'items' => [
    ['target' => 'profile', 'label' => 'Profile', 'icon' => 'user-round'],
    ['target' => 'security', 'label' => 'Security & linked accounts', 'icon' => 'shield-check'],
    ['target' => 'email-preferences', 'label' => 'Email preferences', 'icon' => 'mails'],
    ['target' => 'delete-account', 'label' => 'Delete account', 'icon' => 'trash-2'],
]])
