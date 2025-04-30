<?php

return [
    'models' => [
        'role' => \App\Models\Role::class,
        'permission' => \App\Models\Permission::class,
    ],

    'table_names' => [
        'roles' => 'roles',
        'permissions' => 'permissions',
        'model_has_permissions' => 'model_has_permissions',
        'model_has_roles' => 'model_has_roles',
        'role_has_permissions' => 'role_has_permissions',
    ],

    'column_names' => [
        'role_morph_key' => 'model_id',
    ],
];
