<?php

return [
    'resources' => [
        'label'                  => 'Registro de atividade',
        'plural_label'           => 'Registro de atividades',
        'navigation_item'        => true,
        'navigation_group'       => null,
        'navigation_icon'        => 'heroicon-o-shield-exclamation',
        'navigation_sort'        => null,
        'default_sort_column'    => 'id',
        'default_sort_direction' => 'desc',
        'navigation_count_badge' => false,
        'resource'               => \Rmsramos\Activitylog\Resources\ActivitylogResource::class,
    ],
    'datetime_format' => 'd/m/Y H:i:s',
];
