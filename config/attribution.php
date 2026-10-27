<?php

return [
    // Website acquisition now belongs to Google Analytics. Keep legacy records
    // and withdrawal/cleanup support, without collecting new campaign arrivals.
    'enabled' => false,
    // A consented campaign-to-membership link, without a second traffic counter.
    'conversion_links_enabled' => true,
    'touch_days' => 30,
    'retention_days' => 90,
    'consent_version' => 'v1',
];
