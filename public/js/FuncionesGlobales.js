function actionJsonError(error,btn = null){
    try{
        let error_info  = error.status != 401 ? error.responseJSON : error.responseText;
    
        let flag_message    = error_info.includes("message");
        if (flag_message){
            flag_message    = JSON.parse(error_info);
            flag_message    = flag_message['message'];
        } else {
            flag_message    = error.responseText;
        }
        
        if (error.status == 401){
            
            let msg = JSON.parse(error_info);
            showAlert('danger',flag_message);
            setTimeout(() => {
                window.location.href = "/"+msg['route_error'];
            }, (5000));
        } else {
            showAlert('danger',flag_message);
            if (btn != null){
                $(btn).prop('disabled',false);
            }
        }
    }catch(err){
        showAlert('danger','Error desconocido');
        if (btn != null){
            $(btn).prop('disabled',false);
        }
    }
    
}

//  PARA MODIFICAR ACENTOS DE MENSAJE DE ERROR
function decodeUnicode(str) {
    if (typeof str !== 'string') return str;
    try{
        return str.replace(/\\u[\dA-F]{4}/gi, function (match) {
            return String.fromCharCode(parseInt(match.replace(/\\u/g, ''), 16));
        });
    } catch(err){
        return str;
    }
}

/**
 * FUNCION PARA MOSTRAR MENSAJE DE ALERTA FLOTANTE
 * @param {string} type TIPO DE ALERTA
 * @param {String} message MENSAJE A MOSTRAR
 */
function showAlert(type = 'success' , message,timer = null) {
    console.log('message message',message,decodeUnicode(message));
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-fixed`;
    alertDiv.style.display = 'none';
    alertDiv.innerHTML = decodeUnicode(message);

    document.body.appendChild(alertDiv);

    if (timer == null){
        timer   = type == 'danger' ? 5000 : 3000;
    }

    $(alertDiv).fadeIn();
    setTimeout(() => {
        $(alertDiv).fadeOut();
    }, timer);
}

/**
 * FUNCION PARA MOSTRAR MENSAJE DE CONFIRMACION
 * @param {*} message 
 * @returns {Promise}
 */
function modalConfirm(message) {
    return new Promise((resolve) => {
        const confirmModal = $('#confirmModal');

        // Ajustar el z-index dinámico antes de mostrar el modal
        confirmModal.on('show.bs.modal', function () {
            const modals = $('.modal.show'); // Buscar todos los modales visibles
            if (modals.length > 0) {
                // Encontrar el z-index más alto entre los modales visibles
                const highestZIndex = Math.max(...modals.map(function () {
                    return parseInt($(this).css('zIndex'), 10) || 1050; // 1050 es el z-index base de Bootstrap
                }).get());

                // Ajustar el z-index del modal de confirmación
                confirmModal.css('zIndex', highestZIndex + 10);

                // Ajustar el backdrop para que quede detrás del modal de confirmación
                $('.modal-backdrop').css('zIndex', highestZIndex + 5);
            }
        });

        confirmModal.on('hidden.bs.modal', function () {
            // Restaurar el z-index al valor predeterminado al cerrar el modal
            confirmModal.css('zIndex', '');
            $('.modal-backdrop').css('zIndex', '');
        });

        // Mostrar el mensaje y el modal
        confirmModal.find('#message').html(message);
        confirmModal.modal('show');

        // Escuchar clic en "Aceptar"
        $('#confirmDelete').one('click', function () {
            confirmModal.modal('hide');
            resolve(true); // Devuelve true si se acepta
        });

        // Escuchar clic en "Cancelar"
        $('#cancelDelete').one('click', function () {
            confirmModal.modal('hide');
            resolve(false); // Devuelve false si se cancela
        });

        // Escuchar clic en "Cancelar" (botón "X")
        $('#cancelDeleteX').one('click', function () {
            confirmModal.modal('hide');
            resolve(false); // Devuelve false si se cancela
        });
    });
}


/**
 * FUNCION PARA MOSTRAR VENTANA DE CARGANDO
 */
function showBlockCargando(){
    $("#div_cargando").fadeIn();
}

/**
 * FUNCION PARA OCULTAR VENTANA DE CARGANDO
 */
function hideBlockCargando(){
    $("#div_cargando").fadeOut();
}

/**
 * Mostrar el spinner solo si la URL NO contiene "Menu"
 */
$(document).ajaxSend(function (event, jqXHR, settings) {
    const url = settings.url;
    const data = settings.data || "";

    // Si la URL contiene "Menu" o la data contiene "__fromSelect2"
    if (url.includes("Menu") || data.includes("form_select2_pacientes")) {
        return; // 🚫 No mostrar cargando
    }

    showBlockCargando();
});

// Ocultar el spinner cuando todas las peticiones AJAX hayan terminado
$(document).ajaxStop(function () {
    setTimeout(function () {
        hideBlockCargando();
    }, 500); // Pequeño retraso para evitar parpadeo
});

/**
 * FUNCION QUE CONVIERTE LOS SELECT EN SELECT2
 * 
 * @param   {Element}   header  Elemento padre donde se encuentra el select2
 * @param   {string}    find_element    Texto del id del select
 * @param   {boolean}   allowClear      permite o no limpiar el combo, por defecto es true
 * @param   {string}    placeholder     Texto a mostrar, por defecto ya tiene un valor   
 */
function selectToSelect2(header,find_element, allowClear = true , placeholder= 'Seleccione una opción'){
    header.find("#"+find_element).select2({
        placeholder : placeholder,
        allowClear  : allowClear
    });
}

function validarCantidadMonetaria(cantidad) {
    console.log('cantidad');
    console.log(cantidad); 
    // Expresión regular para validar números enteros o con hasta dos decimales
    const regex = /^\d+(\.\d{1,2})?$/;

    // Si la cantidad es un número entero sin decimales, la convertimos a string
    if (typeof cantidad === 'number' && Number.isInteger(cantidad)) {
        cantidad = cantidad.toString();
    }

    // Verificar que la cantidad sea un string y que coincida con la expresión regular
    if (typeof cantidad === 'string' && regex.test(cantidad)) {
        const numero = parseFloat(cantidad);
        // Verificar que no sea negativo
        if (numero >= 0) {
            return true;
        }
    } else if (typeof cantidad === 'number' && !isNaN(cantidad) && cantidad >= 0) {
        // Verificar que no sea negativo y que sea un número
        return true;
    }

    // Si no cumple con las condiciones, retornar false
    return false;
}

function validarEmail(emailField){
    // Define our regular expression.
    const validEmail    =  /^\w+([.-_+]?\w+)*@\w+([.-]?\w+)*(\.\w{2,10})+$/;

    // Using test we can check if the text match the pattern
    if(emailField != '' && emailField != null && !validEmail.test(emailField) ){
        return false;
    }

    return true;
}

// Función para convertir HH:MM a objeto Date
function convertirHoraATiempo(hora) {
    if (hora == null || hora == ''){
        return hora;
    }
    let fechaActual         = new Date();
    let [horas, minutos]    = hora.split(':');
    let fechaHora           = new Date(fechaActual);
    fechaHora.setHours(horas, minutos, 0, 0); // Establecer horas y minutos, segundos y milisegundos a 0
    return fechaHora;
}

//  FUNCION PARA FORMATEAR DINERO
function formatoDinero(numero) {
    
    // Convertir a número por si viene como string
    const num = parseFloat(numero);
    
    // Verificar si es un número válido
    if (isNaN(num)) {
        return "0.00";
    }
    
    // Formatear con separadores de miles y 2 decimales
    return num.toLocaleString('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
}