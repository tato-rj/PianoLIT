<?php

return [
    // Absolute lifetime, not extended by reads. Expiry requires password login.
    'token_lifetime_days' => 30,
    'login_attempts_per_email_ip' => 5,
    'login_attempts_per_ip' => 20,
    'requests_per_ip' => 120,
];
