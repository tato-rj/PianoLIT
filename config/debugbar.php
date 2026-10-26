<?php

return [
    // Retain the package's existing exclusions and keep credentials/bearer
    // responses out of its request/response storage even in local debug mode.
    'except' => ['telescope*', 'horizon*', 'api/v2', 'api/v2/*'],
];
