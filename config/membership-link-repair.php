<?php

// A fixed, reviewed data repair. Provider identities must still match when run.
// No account, receipt, password, payment method or provider state is recreated.
return [
    'version' => '2026-10-15-legacy-links-v1',
    'cases' => [
        ['user' => 894, 'keep' => 149, 'links' => [
            53 => ['type' => 'apple', 'source' => 86, 'chain' => '100000683942936', 'status' => 'expired'],
            149 => ['type' => 'stripe', 'source' => 26, 'account' => 'swiss', 'customer' => 'cus_LD44Py3hHe7slG', 'subscription' => 'sub_1KWdwSGQzApYr7LG9x77MV7a', 'status' => 'canceled'],
        ]],
        ['user' => 2778, 'keep' => 189, 'links' => [
            189 => ['type' => 'apple', 'source' => 177, 'chain' => '20001160395595', 'status' => 'expired'],
            215 => ['type' => 'stripe', 'source' => 59, 'account' => 'swiss', 'customer' => 'cus_NgqzvTLQ1Wmqn7', 'subscription' => 'sub_1MvTHFGQzApYr7LGEndh0edL', 'status' => 'canceled'],
        ]],
        ['user' => 3830, 'keep' => 295, 'links' => [
            285 => ['type' => 'apple', 'source' => 224, 'chain' => '120002401712280', 'status' => 'expired'],
            295 => ['type' => 'stripe', 'source' => 102, 'account' => 'us', 'customer' => 'cus_R5h4enZlR6jpat', 'subscription' => 'sub_1QDVgKA3roHoLcB1IqI98cwr', 'status' => 'active'],
        ]],
        ['user' => 4636, 'keep' => 347, 'links' => [
            347 => ['type' => 'apple', 'source' => 259, 'chain' => '280003008369182', 'status' => 'expired'],
            348 => ['type' => 'apple', 'source' => 260, 'chain' => '280003008369182', 'status' => 'expired'],
        ]],
    ],
];
