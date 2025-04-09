<?php 

return [
    'ctusuarios'    => [
        'show'              => "/ctusuarios/show",
        'get_info_usuario'  => "/ctusuarios/get_info_usuario",
        'create'            => "/ctusuarios/create",
        'change_status'     => "/ctusuarios/change_status",
        'delete'            => "/ctusuarios/delete",
        'update'            => "/ctusuarios/update",
        'count'             => "/ctusuarios/count"
    ],
    'cttipo_usuarios'   => array(
        'show'      => '/cttipo_usuarios/show',
        'create'    => '/cttipo_usuarios/create',
        'change_estatus'    => '/cttipo_usuarios/change_estatus',
        'update'            => '/cttipo_usuarios/update',
        'count'             => "/cttipo_usuarios/count"
    ),
    'ctpermisos'   => array(
        'show'  => '/ctpermisos/show'
    ),
    'tbbitacora_movimientos'   => array(
        'create'    => '/tbbitacora_movimientos/create',
        'count'     => "/tbbitacora_movimientos/count",
        'show'      => "/tbbitacora_movimientos/count"
    ),
    'ctservicios'    => [
        'show'              => "/ctservicios/show",
        'create'            => "/ctservicios/create",
        'delete'            => "/ctservicios/delete",
        'update'            => "/ctservicios/update",
        'count'             => "/ctservicios/count"
    ],
    'ctlocaciones'  => [
        'show'              => "/ctlocaciones/show",
        'create'            => "/ctlocaciones/create",
        'delete'            => "/ctlocaciones/delete",
        'update'            => "/ctlocaciones/update",
        'count'             => "/ctlocaciones/count",
        'get_opening_hours'     => "/tbhorarios_atencion/get_opening_hours",
        'save_opening_hours'    => "/tbhorarios_atencion/save_opening_hours",
    ],
    'ctprofesionales'   => [
        'show'      => "/ctprofesionales/show",
        'create'    => "/ctprofesionales/create",
        'delete'    => "/ctprofesionales/delete",
        'update'    => "/ctprofesionales/update",
        'count'     => "/ctprofesionales/count",
        'change_status'                 => "/ctprofesionales/change_status",
        'get_schedule_availability'     => '/ctprofesionales/get_schedule_availability',
        'save_schedule_availability'    => '/ctprofesionales/save_schedule_availability',
    ],
    'tbhorarios_atencion'   => array(
        'get_opening_hours'     => '/tbhorarios_atencion/get_opening_hours',
        'save_opening_hours'    => '/tbhorarios_atencion/save_opening_hours',
    ),
    'ctpacientes'   => [
        'show'      => "/ctpacientes/show",
        'create'    => "/ctpacientes/create",
        'delete'    => "/ctpacientes/delete",
        'update'    => "/ctpacientes/update",
        'count'     => "/ctpacientes/count",
        'change_status'     => "/ctpacientes/change_status",
        'save_program_date' => "/ctpacientes/save_program_date",
        'get_program_date'  => "/ctpacientes/get_program_date",
        'delete_program_date'   => "/ctpacientes/delete_program_date",
    ],
    'ctvariables_sistema'   => array(
        'show'  => '/ctvariables_sistema/show'
    ),
    'tbapertura_agenda' => array(
        'show'  => '/tbapertura_agenda/show',
        'save'  => '/tbapertura_agenda/save',
    ),

];