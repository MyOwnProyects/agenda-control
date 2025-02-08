function actionJsonError(error,btn = null){
    try{
        console.log('error');
        console.log(error);
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

/**
 * FUNCION PARA MOSTRAR MENSAJE DE ALERTA FLOTANTE
 * @param {string} type TIPO DE ALERTA
 * @param {String} message MENSAJE A MOSTRAR
 */
function showAlert(type = 'success' , message) {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-fixed`;
    alertDiv.style.display = 'none';
    alertDiv.innerHTML = message;

    document.body.appendChild(alertDiv);

    let timer   = type == 'danger' ? 7000 : 3000;

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
        // Mostrar el modal
        $('#confirmModal').find('#message').html(message);
        $('#confirmModal').modal('show');

        // Escuchar clic en "Aceptar"
        $('#confirmDelete').one('click', function() {
            $('#confirmModal').modal('hide');
            resolve(true); // Devuelve true si se acepta
        });

        // Escuchar clic en "Cancelar"
        $('#cancelDelete').one('click', function() {
            $('#confirmModal').modal('hide');
            resolve(false); // Devuelve false si se cancela
        });

        // Escuchar clic en "Cancelar"
        $('#cancelDeleteX').one('click', function() {
            $('#confirmModal').modal('hide');
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

// Mostrar el spinner cuando comienza cualquier petición AJAX
$(document).ajaxStart(function () {
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