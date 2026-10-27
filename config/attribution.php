<?php

return [
    // Website acquisition now belongs to Google Analytics. Keep legacy records
    // and withdrawal/cleanup support, without collecting new campaign arrivals.
    'enabled' => false,
    'touch_days' => 30,
    'retention_days' => 90,
    'consent_version' => 'v1',
];
