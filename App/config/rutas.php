<?php 

return [
    'ctusuarios'    => [
        'show'              => "/ctusuarios/show",
        'get_info_usuario'  => "/ctusuarios/get_info_usuario",
        'create'            => "/ctusuarios/create",
        'change_status'     => "/ctusuarios/change_status",
        'delete'            => "/ctusuarios/delete",
        'update'            => "/ctusuarios/update",
        'count'             => "/ctusuarios/count",
        'change_password'   => '/ctusuarios/change_password'
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
        'show'      => "/tbbitacora_movimientos/show"
    ),
    'ctservicios'    => [
        'show'              => "/ctservicios/show",
        'create'            => "/ctservicios/create",
        'delete'            => "/ctservicios/delete",
        'update'            => "/ctservicios/update",
        'count'             => "/ctservicios/count",
        'change_status'     => "/ctservicios/change_status",
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
        'fill_combo'            => '/ctpacientes/fill_combo',
        'save_express'          => '/ctpacientes/save_express',
        'save_diagnoses'        => '/ctpacientes/save_diagnoses',
        'get_digital_record'    => '/ctpacientes/get_digital_record',
        'delete_file'           => '/ctpacientes/delete_file',
        'save_file'             => '/ctpacientes/save_file',
        'show_file'             => '/ctpacientes/show_file',
        'save_subareas_focus'   => '/ctpacientes/save_subareas_focus',
        'save_exploracion_fisica'   => '/ctpacientes/save_exploracion_fisica',
        'show_exploracion_fisica'   => '/ctpacientes/show_exploracion_fisica',
        'show_motivo_consulta'      => '/ctpacientes/show_motivo_consulta',
        'save_motivo_consulta'      => '/ctpacientes/save_motivo_consulta',
        'get_clinical_data'         => '/ctpacientes/get_clinical_data'
    ],
    'ctvariables_sistema'   => array(
        'show'  => '/ctvariables_sistema/show'
    ),
    'tbapertura_agenda' => array(
        'show'  => '/tbapertura_agenda/show',
        'save'  => '/tbapertura_agenda/save',
    ),
    'tbagenda_citas' => array(
        'count' => '/tbagenda_citas/count',
        'show'  => '/tbagenda_citas/show',
        'cancelar_cita'         => '/tbagenda_citas/cancelar_cita',
        'modificar_asistencia'  => '/tbagenda_citas/modificar_asistencia',
        'save'                  => '/tbagenda_citas/save',
        'get_today'             => '/tbagenda_citas/get_today',
        'capturar_pago'         => '/tbagenda_citas/capturar_pago',
        'cancelar_pago'         => '/tbagenda_citas/cancelar_pago',
    ),
    'ctmotivos_cancelacion_cita'    => array(
        'show'  => '/ctmotivos_cancelacion_cita/show'
    ),
    'cttranstornos_neurodesarrollo' => array(
        'show'  => '/cttranstornos_neurodesarrollo/show'
    ),
    'ctbitacora_acciones'   => array(
        'show'  => '/ctbitacora_acciones/show'
    ),
    'tbnotas'   => array(
        'show'      => "/tbnotas/show",
        'create'    => "/tbnotas/create",
        'delete'    => "/tbnotas/delete",
        'update'    => "/tbnotas/update",
        'count'     => "/tbnotas/count",
    ),
    'ctareas_enfoque'   => array(
        'show'  => '/ctareas_enfoque/show',
    )

];