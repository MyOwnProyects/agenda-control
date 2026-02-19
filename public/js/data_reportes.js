function data_reportes(reporte){
    const info_reporte  = {
        general_citas   : {
            titulo      : 'Reporte general de citas',
            descripcion : 'Este reporte muestra todas las citas registradas en el sistema dentro del rango de fechas seleccionado.'+
                            'Incluye información del paciente, profesional asignado, estatus de la cita y el monto registrado.',
            datos_tabla : {
                0   : {
                    columna     : 'Paciente',
                    descripcion : 'Datos generales del paciente'
                },
                1   : {
                    columna     : 'Profesional',
                    descripcion : 'Nombre del profesional'
                },
                2   : {
                    columna     : 'Servicios',
                    descripcion : 'Información de o los servicios y sus respectivos costos'
                },
                3   : {
                    columna     : 'Fechas',
                    descripcion : 'Fecha y hora de la cita, así como su fecha de captura'
                },
                4   : {
                    columna     : 'Estatus',
                    descripcion : 'Cita pagada, activa/inactiva y/o pagada'
                }
            }
        }
    };

    return info_reporte[reporte];
}