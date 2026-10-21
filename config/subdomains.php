<?php

return [
    // Only the installed, root-owned script accepts deployment settings. Never
    // pass application-controlled paths, IPs, templates or executables to sudo.
    'script' => env('SUBDOMAIN_DEPLOY_SCRIPT', '/usr/local/sbin/pianolit-subdomain'),
];
