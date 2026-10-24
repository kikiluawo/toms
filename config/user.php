<?php

return [
    // Peran menentukan jangkauan data (data_scope) yang tersimpan di company_user.
    'roles' => [
        'ceo' => ['label' => 'CEO', 'scope' => 'group'],
        'cfo' => ['label' => 'CFO', 'scope' => 'group'],
        'coo' => ['label' => 'COO', 'scope' => 'group'],
        'manager' => ['label' => 'MANAGER', 'scope' => 'company'],
        'staf' => ['label' => 'STAF', 'scope' => 'branch'],
        'pic' => ['label' => 'PIC', 'scope' => 'branch'],
    ],

    'scopes' => [
        'system' => 'Semua PT + pengaturan sistem',
        'group' => 'Seluruh PT',
        'company' => 'Semua lokasi di PT ini',
        'branch' => 'Lokasi yang ditugaskan saja',
    ],

    'all_companies' => 'all',
];
