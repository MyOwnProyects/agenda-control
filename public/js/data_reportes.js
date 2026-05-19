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
                    columna     : 'Edad',
                    descripcion : 'Edad al momento de asistir a la cita'
                },
                2   : {
                    columna     : 'Profesional',
                    descripcion : 'Nombre del profesional'
                },
                3   : {
                    columna     : 'Servicios',
                    descripcion : 'Información de o los servicios y sus respectivos costos'
                },
                4   : {
                    columna     : 'Fechas',
                    descripcion : 'Fecha y hora de la cita, así como su fecha de captura'
                },
                5   : {
                    columna     : 'Estatus',
                    descripcion : 'Cita pagada, activa/inactiva y/o pagada'
                }
            }
        },
        general_ingresos: {
            titulo      : 'Reporte de ingresos contable',
            descripcion : 'Reporte que consolida todos los ingresos monetarios registrados en el sistema dentro de un rango de fechas de pago, mostrando el total ingresado y su distribución, independientemente de la fecha en que ocurrió la cita.',
            datos_tabla : {
                0: {
                    columna     : 'Hoja 1 - Indicador',
                    descripcion : 'Totales por metodo de pago'
                },
                1: {
                    columna     : 'Hoja 1 - Valor',
                    descripcion : 'Cantidad monetaria por metodo de pago'
                },
                2: {
                    columna     : 'Hoja 2 - Fecha de pago',
                    descripcion : 'Día en que se registro el pago en el sistema'
                },
                3: {
                    columna     : 'Hoja 2 - Total por transferencias',
                    descripcion : 'Suma de todas las citas pagadas por transferencia'
                },
                4: {
                    columna     : 'Hoja 2 - Total por efectivo',
                    descripcion : 'Suma de todas las citas pagadas en efectivo'
                },
                5: {
                    columna     : 'Hoja 2 - Total pagado',
                    descripcion : 'Suma de todos los ingresos registrados en ese día'
                },
                6: {
                    columna     : 'Hoja 3 - Tipo',
                    descripcion : 'Indica si el registro es un abono o un movimiento que se realizó al abono en cuestión.'
                },
                7: {
                    columna     : 'Hoja 3 - Fecha',
                    descripcion : 'Fecha en que el paciente realizó el pago.'
                },
                8: {
                    columna     : 'Hoja 3 - Monto',
                    descripcion : 'Cantidad monetaria que el paciente pagó.'
                },
                9: {
                    columna     : 'Hoja 3 - Monto disponible',
                    descripcion : 'Saldo restante del abono después de aplicar los movimientos registrados en el período.'
                }
            }
        },
        mensajes_enviados   : {
            titulo      : 'Reporte de mensajes enviados por Whatsapp',
            descripcion : 'Reporte donde se muestra el mensaje que generó el sistema utilizando las plantillas de Whatssapp con la información de la cita',
            datos_tabla : {
                0   : {
                    columna     : 'Nombre de plantilla',
                    descripcion : 'Nombre de la plantilla utilizada para generar el mensaje'
                },
                1   : {
                    columna     : 'Usuario',
                    descripcion : 'Usuario que realizó el envio de mensaje'
                },
                2   : {
                    columna     : 'Fecha de envio',
                    descripcion : 'Fecha en la que el usuario dio clic para enviar el mensaje'
                },
                3   : {
                    columna     : 'Mensaje',
                    descripcion : 'Mensaje generado ya con los datos de la cita del paciente'
                }
            }
        }
    };

    return info_reporte[reporte];
}