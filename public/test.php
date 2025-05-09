
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Proyecto con jQuery y Bootstrap</title>

    <!-- Bootstrap CSS (CDN) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- jQuery (CDN) -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
</head>
<body>
<style>
    /* Estilos encapsulados dentro de .div_header_days_container */
    .div_header_days_container {
        font-family: Arial, sans-serif;
    }
    .div_header_days_container .agenda-container {
        display: flex;
        flex-direction: column;
        border: 1px solid #ddd;
        margin: 20px;
        position: relative; /* Necesario para posicionar el div flotante */
    }
    .div_header_days_container .agenda-row {
        display: flex;
        border-bottom: 1px solid #ddd;
        position: relative; /* Necesario para posicionar el div flotante */
    }
    .div_header_days_container .agenda-time {
        flex: 0 0 100px;
        padding: 10px;
        background-color: #f8f9fa;
        border-right: 1px solid #ddd;
        text-align: center;
    }
    .div_header_days_container .agenda-service {
        flex: 1;
        padding: 10px;
        border-right: 1px solid #ddd;
        position: relative; /* Necesario para posicionar el div flotante */
        transition: border-color 0.3s ease; /* Transición suave para el hover */
    }
    .div_header_days_container .agenda-service:last-child {
        border-right: none;
    }
    .div_header_days_container .agenda-hours:hover {
        border: 1px solid #007bff; /* Borde azul de Bootstrap al hacer hover */
    }
    .div_header_days_container .agenda-header {
        font-weight: bold;
        background-color: #e9ecef;
    }
    .div_header_filters .navigation {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin: 20px;
    }
    .div_header_filters .navigation .left-section {
        display: flex;
        align-items: center;
        gap: 10px; /* Espacio entre los botones */
        width: 400px; /* Ancho fijo para left-section */
    }
    .div_header_filters .navigation .right-section {
        display: flex;
        align-items: center;
        gap: 10px; /* Espacio entre los botones */
    }
    .div_header_filters .navigation button {
        padding: 10px 20px;
        font-size: 16px;
        cursor: pointer;
    }
    .div_header_filters .filters {
        display: flex;
        gap: 20px; /* Espacio entre los select */
        margin: 20px;
    }
    .div_header_filters .filters select {
        padding: 10px;
        font-size: 16px;
        border: 1px solid #ddd;
        border-radius: 5px;
    }
    .div_header_days_container .evento-flotante {
        position: absolute;
        background-color: rgba(0, 123, 255, 0.2);
        border: 1px solid #007bff;
        width: 100%; /* CAMBIO: usar todo el ancho */
        left: 0; /* CAMBIO: alinear a la izquierda */
        z-index: 10;
        box-sizing: border-box;
    }

    /*  ESTILO PARA DIV DE SEMANAS  */
    .div_header_week_container .week-container {
        display: grid;
        grid-template-columns: repeat(8, 120px);
        grid-auto-rows: 70px;
        gap: 1px;
        background-color: #f0f0f0;
        padding: 1px;
        position: relative;
        width: fit-content; /* Ajusta el ancho al contenido */
        margin: 0 auto; /* Centra el contenedor */
    }

    .div_header_week_container .grid-item {
        background-color: white;
        border: 1px solid #ddd;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 5px;
        box-sizing: border-box;
    }
    .div_header_week_container .grid-item.header {
        background-color: #f8f9fa;
        font-weight: bold;
    }
    .div_header_week_container .grid-item.hour {
        background-color: #f8f9fa;
        font-weight: bold;
    }
    .div_header_week_container .floating-box {
        position: absolute;
        background-color: rgba(0, 123, 255, 0.3);
        border: 2px solid #007bff;
        box-sizing: border-box;
        pointer-events: none;
        cursor: pointer;
    }
    .div_header_week_container .grid-hours:hover {
        border: 1px solid #007bff; /* Borde azul de Bootstrap al hacer hover */
    }

    .hide_container{
        display: none!important;
    }

    /*  ESTILO CALENDARIO POR MES*/
    .div_header_month_container {
        background: white;
        border-radius: 10px;
        box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        width: 60%;
        max-width: 800px;
        text-align: center;
        padding: 20px;
        display: flex;
        flex-direction: column;
        align-items: center; /* Asegura que el contenido esté centrado */
        margin: 0 auto; /* Centra horizontalmente */
    }


    .div_header_month_container .header button {
        background: none;
        border: none;
        color: white;
        font-size: 1.5em;
        cursor: pointer;
    }

    .div_header_month_container .days {
        display: grid;
        grid-template-columns: repeat(7, 1fr);
        gap: 0;
        padding: 10px;
        background-color: #f0f0f0;
        font-weight: bold;
    }

    .div_header_month_container .calendar_month_dates {
        display: grid;
        grid-template-columns: repeat(7, 1fr);
        gap: 0;
        padding: 10px;
    }

    .div_header_month_container .calendar_month_dates div {
        padding: 15px;
        text-align: center;
        cursor: pointer;
        font-size: 1.2em;
    }

    .div_header_month_container .calendar_month_dates div.other-month {
        color: #aaa;
    }

    .div_header_month_container .calendar_month_dates div:hover {
        border: 1px solid #007bff; /* Borde azul de Bootstrap al hacer hover */
    }

    .div_header_month_container .marked-day {
        background-color: #FFD700;
        color: black;
    }

    .div_header_month_container .current-day {
        border: 1px solid #007bff;
        background-color: #007bff;
        font-weight: bold;
        color: white;
    }

    .div_header_month_container .disabled {
        background-color: #ccc !important;
        color: #666 !important;
        cursor: not-allowed;
        pointer-events: none;
        border: 1px solid #ccc;
    }

    .div_header_month_container .days,
    .div_header_month_container .calendar_month_dates {
        width: 100%;
        display: grid;
        grid-template-columns: repeat(7, 1fr);
        justify-content: center;
    }
</style>
<div class="container div_header_filters col-12">
    <h1 class="text-center my-4">Agenda de Servicios</h1>

    <!-- Filtros de Servicios y Pacientes -->
    <div class="filters">
        <select id="select-servicios">
            <option value="">Seleccione un Servicio</option>
            <option value="1">Servicio 1</option>
            <option value="2">Servicio 2</option>
            <option value="3">Servicio 3</option>
        </select>
        <select id="select-pacientes">
            <option value="">Seleccione un Paciente</option>
            <option value="1">Paciente 1</option>
            <option value="2">Paciente 2</option>
            <option value="3">Paciente 3</option>
        </select>
    </div>

    <!-- Sección de navegación -->
    <div class="navigation">
        <!-- Sección izquierda: Botones de Mes, Semana y Día -->
        <div class="left-section">
            <button id="btn-mes-anterior" class="btn btn-secondary"><<<</button>
            <button id="btn-semana-anterior" class="btn btn-secondary"><<</button>
            <button id="btn-anterior" class="btn btn-secondary"><</button>
            <h3 id="fecha-actual"></h3>
            <button id="btn-siguiente" class="btn btn-secondary">></button>
            <button id="btn-semana-siguiente" class="btn btn-secondary">>></button>
            <button id="btn-mes-siguiente" class="btn btn-secondary">>>></button>
            <button id="btn-hoy" class="btn btn-primary">Hoy</button>
        </div>



        <!-- Sección derecha: Botones de Mes/Semana/Día -->
        <div class="right-section">
            <button id="btn-mes" class="btn btn-primary">Mes</button>
            <button id="btn-semana" class="btn btn-primary">Semana</button>
            <button id="btn-dia" class="btn btn-primary">Día</button>
        </div>
    </div>
</div>
</br></br>
<div class="container div_header_days_container container-calendar col-12">
    <!-- Agenda -->
    <div class="agenda-container" id="agenda-container">
        <!-- Header -->
        <div class="agenda-row agenda-header" id="agenda-header">
            <div class="agenda-time">Hora</div>
            <!-- Las columnas de profesionales se generarán dinámicamente aquí -->
        </div>
        <!-- Las filas de horas se generarán dinámicamente aquí -->
    </div>
</div>
<div class="container mt-4 d-flex flex-column align-items-center div_header_week_container hide_container container-calendar col-12">
    <div class="week-container">
        <div class="grid-item header"></div>
        <div class="grid-item header">Lunes</div>
        <div class="grid-item header">Martes</div>
        <div class="grid-item header">Miércoles</div>
        <div class="grid-item header">Jueves</div>
        <div class="grid-item header">Viernes</div>
        <div class="grid-item header">Sábado</div>
        <div class="grid-item header">Domingo</div>
    </div>
</div>
<div class=" div_header_month_container hide_container container-calendar col-12">
    <div class="days">
        <div class="day">Dom</div>
        <div class="day">Lun</div>
        <div class="day">Mar</div>
        <div class="day">Mié</div>
        <div class="day">Jue</div>
        <div class="day">Vie</div>
        <div class="day">Sáb</div>
    </div>
    <div class="calendar_month_dates" id="calendar_month_dates"></div>
</div>
<script>
    const markedDays    = [14, 25]; 
    let cierre_agenda   = new Date(2025, 3, 30); // Año, Mes (0-indexed), Día


    // Array de profesionales (sincronizado con la tabla)
    const profesionales = [
        { id: "1", nombre: "Dr. Juan Pérez" },
        { id: "2", nombre: "Dra. María Gómez" },
        { id: "3", nombre: "Dr. Carlos López" }
    ];

    // Array de horas (de 9:00 a 19:00)
    const horas = [];
    for (let i = 10; i <= 19; i++) {
        horas.push(`${i.toString().padStart(2, '0')}:00`);
    }

    // Array de eventos (ejemplo para cada doctor)
    const eventos = [
        { profesional: "Dr. Juan Pérez", inicio: "10:00", fin: "11:00", descripcion: "Consulta con Paciente 1" },
        { profesional: "Dra. María Gómez", inicio: "11:00", fin: "12:00", descripcion: "Consulta con Paciente 2" },
        { profesional: "Dr. Carlos López", inicio: "17:25", fin: "17:55", descripcion: "Consulta con Paciente 3" }
    ];

    // Variables para manejar la fecha
    let fechaActual = new Date();

    // Función para formatear la fecha como "Día, DD/MM/AAAA"
    function formatearFecha(fecha) {
        const opciones = { weekday: 'long', year: 'numeric', month: '2-digit', day: '2-digit' };
        return fecha.toLocaleDateString('es-ES', opciones);
    }

    // Función para actualizar la fecha mostrada
    function actualizarFecha() {
        $(".div_header_filters #fecha-actual").text(formatearFecha(fechaActual));
    }

    // Función para generar las columnas de profesionales en la agenda
    function generarColumnasProfesionales() {
        const agendaHeader = $(".div_header_days_container #agenda-header");

        // Generar las columnas en el header
        profesionales.forEach(profesional => {
            const columna = $("<div>")
                .addClass("agenda-service")
                .text(profesional.nombre);
            agendaHeader.append(columna);
        });
    }

    // Función para generar las filas de horas
    function generarFilasHoras() {
        const agendaContainer = $(".div_header_days_container #agenda-container");

        horas.forEach((hora, index) => {
            const fila = $("<div>").addClass("agenda-row");

            // Columna de la hora
            const columnaHora = $("<div>")
                .addClass("agenda-time")
                .text(hora);
            fila.append(columnaHora);

            // Celdas de profesionales
            profesionales.forEach(() => {
                const celda = $("<div>").addClass("agenda-service").addClass('agenda-hours');
                fila.append(celda);
            });

            // Añadir la fila al contenedor
            agendaContainer.append(fila);
        });
    }

    // Función para convertir una hora en minutos desde las 00:00
    function convertirHoraAMinutos(hora) {
        const [h, m] = hora.split(":").map(Number);
        return h * 60 + m;
    }

    // Función para generar los eventos flotantes
    function generarEventosFlotantes() {
        eventos.forEach(evento => {
            // Convertir las horas de inicio y fin a minutos
            const inicioMinutos = convertirHoraAMinutos(evento.inicio);
            const finMinutos = convertirHoraAMinutos(evento.fin);

            // Encontrar la fila correspondiente a la hora de inicio
            const filaInicio = $(`.div_header_days_container .agenda-time:contains('${evento.inicio.split(":")[0].padStart(2, '0')}:00')`).closest(".agenda-row");
            if (filaInicio.length) {
                // Encontrar la columna correspondiente al profesional
                const profesionalIndex = profesionales.findIndex(p => p.nombre === evento.profesional);
                if (profesionalIndex !== -1) {
                    const columnaProfesional = filaInicio.find(".agenda-service").eq(profesionalIndex);

                    // Calcular la posición y altura del evento
                    const alturaPorHora = 100; // Cada hora ocupa 100% de altura
                    const alturaPorMinuto = alturaPorHora / 60; // Altura por minuto

                    const top = (inicioMinutos % 60) * alturaPorMinuto; // Posición vertical
                    const altura = (finMinutos - inicioMinutos) * alturaPorMinuto; // Altura del evento

                    // Crear el evento flotante
                    const eventoDiv = $("<div>")
                        .addClass("evento-flotante")
                        .text(evento.descripcion)
                        .css({
                            top: `${top}%`, // Posición vertical
                            height: `${altura}%`, // Altura del evento
                        });
                    columnaProfesional.append(eventoDiv);
                }
            }
        });
    }

    // Función para cambiar el día (anterior o siguiente)
    function cambiarDia(dias, nueva_fecha) {
        let last_month = fechaActual.getMonth();
        let new_month = last_month;

        let nuevaFecha = nueva_fecha ? new Date(nueva_fecha) : new Date(fechaActual);
        
        if (dias !== null) {
            nuevaFecha.setDate(nuevaFecha.getDate() + dias);
        }

        // Si la nueva fecha es mayor a la fecha límite, no se permite el cambio
        if (nuevaFecha > cierre_agenda) {
            alert("No puedes seleccionar una fecha posterior al cierre de la agenda.");
            return;
        }

        fechaActual = nuevaFecha;
        new_month = fechaActual.getMonth();

        actualizarFecha();
        
        if (new_month !== last_month) {
            console.log('nuevo mes');
            renderCalendar();
        }
    }

    function cambiarSemana(semanas) {
        let nuevaFecha = new Date(fechaActual);
        nuevaFecha.setDate(nuevaFecha.getDate() + (7 * semanas));

        // Verifica que la nueva fecha no exceda la fecha límite
        if (nuevaFecha > cierre_agenda) {
            alert("No puedes avanzar más allá del cierre de la agenda.");
            return;
        }

        fechaActual = nuevaFecha;
        actualizarFecha();
        renderCalendar();
    }

    // Función para cambiar de mes
    function cambiarMes(meses) {
        let nuevaFecha = new Date(fechaActual);
        nuevaFecha.setMonth(nuevaFecha.getMonth() + meses);

        // Verifica que la nueva fecha no exceda la fecha límite
        if (nuevaFecha > cierre_agenda) {
            alert("No puedes avanzar más allá del cierre de la agenda.");
            return;
        }

        fechaActual = nuevaFecha;
        actualizarFecha();
        renderCalendar();
    }

    // Función para volver al día actual
    function irAFechaActual() {
        fechaActual = new Date(); // Reiniciar a la fecha de hoy
        actualizarFecha();
        renderCalendar();
    }


    //  funcion para renderizar calendario por mes  
    function renderCalendar() {
        const year = fechaActual.getFullYear();
        const month = fechaActual.getMonth();
        const today = new Date();

        $(".div_header_month_container #monthYear").text(`${fechaActual.toLocaleString('es', { month: 'long' })} ${year}`);
        $(".div_header_month_container #calendar_month_dates").empty();

        const firstDayOfMonth = new Date(year, month, 1).getDay();
        const lastDayOfMonth = new Date(year, month + 1, 0).getDate();
        const prevMonthLastDay = new Date(year, month, 0).getDate();

        for (let i = firstDayOfMonth - 1; i >= 0; i--) {
            let day = prevMonthLastDay - i;
            let classList = "other-month";
            if (new Date(year, month - 1, day).getDay() === 0) classList += " disabled";
            classList   += ' div_month_day';
            let create_date = new Date(year, month - 1, day);
            
            if (create_date > cierre_agenda) continue; // No agregar días después del cierre

            $(".div_header_month_container #calendar_month_dates").append(`<div class="${classList}" data-day="${year}-${rellenar_fecha(month - 1)}-${rellenar_fecha(day)}">${day}</div>`);
        }

        for (let i = 1; i <= lastDayOfMonth; i++) {
            let classList = "";
            let create_date = new Date(year, month, i);

            if (create_date > cierre_agenda) continue; // No agregar días después del cierre

            if (year === today.getFullYear() && month === today.getMonth() && i === today.getDate()) {
                classList += "current-day";
            }
            if (markedDays.includes(i)) {
                classList += " marked-day";
            }
            if (create_date.getDay() === 0) {
                classList += " disabled";
            }
            classList   += ' div_month_day';
            
            $(".div_header_month_container #calendar_month_dates").append(`<div class="${classList}" data-day="${year}-${rellenar_fecha(month)}-${rellenar_fecha(i)}">${i}</div>`);
        }
    }


    function rellenar_fecha(i) {
        let num_return = i.toString(); // Convertimos a string por si es número

        if (num_return.length === 1) { 
            num_return = '0' + num_return; 
        }

        return num_return;
    }

    function obtenerRangoSemana(fecha) {
        // Crear un objeto Date a partir de la fecha proporcionada
        const fechaObj = new Date(fecha);
        
        // Obtener el día de la semana (0 para domingo, 1 para lunes, ..., 6 para sábado)
        const diaSemana = fechaObj.getDay();
        
        // Calcular la fecha del lunes de esa semana
        const lunes = new Date(fechaObj);
        lunes.setDate(fechaObj.getDate() - (diaSemana === 0 ? 6 : diaSemana - 1));
        
        // Calcular la fecha del domingo de esa semana
        const domingo = new Date(fechaObj);
        domingo.setDate(fechaObj.getDate() + (diaSemana === 0 ? 0 : 7 - diaSemana));
        
        // Formatear las fechas en el formato 'YYYY-MM-DD'
        const formatoFecha = (fecha) => {
            const año = fecha.getFullYear();
            const mes = String(fecha.getMonth() + 1).padStart(2, '0');
            const dia = String(fecha.getDate()).padStart(2, '0');
            return `${año}-${mes}-${dia}`;
        };
        
        return {
            inicioSemana: formatoFecha(lunes),
            finSemana: formatoFecha(domingo)
        };
    }


    // Inicializar la agenda
    $(document).ready(function () {
        // Encapsulación de eventos dentro de .div_header_days_container
        const daysContainer = $(".div_header_days_container");
        const divHeaderFilters = $(".div_header_filters");

        // Eventos para los botones de navegación
        divHeaderFilters.find("#btn-anterior").on("click", () => cambiarDia(-1));
        divHeaderFilters.find("#btn-siguiente").on("click", () => cambiarDia(1));

        // Eventos para cambiar semana
        divHeaderFilters.find("#btn-semana-anterior").on("click", () => cambiarSemana(-1));
        divHeaderFilters.find("#btn-semana-siguiente").on("click", () => cambiarSemana(1));

        // Eventos para cambiar mes
        divHeaderFilters.find("#btn-mes-anterior").on("click", () => cambiarMes(-1));
        divHeaderFilters.find("#btn-mes-siguiente").on("click", () => cambiarMes(1));

        // Evento para volver al día actual
        divHeaderFilters.find("#btn-hoy").on("click", () => irAFechaActual());

        // Eventos para los botones de vista (Mes, Semana, Día)
        divHeaderFilters.find("#btn-mes").on("click", () => {
            $(".container-calendar").addClass('hide_container');
            $(".div_header_month_container").removeClass('hide_container');
        });
        divHeaderFilters.find("#btn-semana").on("click", () => {
            //alert("Cambiar a vista de Semana");
            // Aquí puedes implementar la lógica para cambiar a la vista de semana
            $(".container-calendar").addClass('hide_container');
            $(".div_header_week_container").removeClass('hide_container');

        });
        divHeaderFilters.find("#btn-dia").on("click", () => {
            //alert("Cambiar a vista de Día");
            // Aquí puedes implementar la lógica para cambiar a la vista de día
            $(".container-calendar").addClass('hide_container');
            $(".div_header_days_container").removeClass('hide_container');
        });

        // Eventos para los select de Servicios y Pacientes
        divHeaderFilters.find("#select-servicios").on("change", function () {
            const servicioSeleccionado = $(this).val();
            console.log("Servicio seleccionado:", servicioSeleccionado);
            // Aquí puedes implementar la lógica para filtrar por servicio
        });

        divHeaderFilters.find("#select-pacientes").on("change", function () {
            const pacienteSeleccionado = $(this).val();
            console.log("Paciente seleccionado:", pacienteSeleccionado);
            // Aquí puedes implementar la lógica para filtrar por paciente
        });

        // Generar la agenda
        generarColumnasProfesionales(); // Generar columnas de profesionales
        generarFilasHoras(); // Generar filas de horas
        generarEventosFlotantes(); // Generar eventos flotantes
        actualizarFecha(); // Mostrar la fecha actual al cargar la página

        //  CODIGO PARA CALENDARIO POR SEMANA
        const diasSemana = ["Domingo", "Lunes", "Martes", "Miércoles", "Jueves", "Viernes", "Sábado"];

        //const horas = ["15:00", "16:00", "17:00", "18:00", "19:00"];
        const $weekContainer = $(".div_header_week_container .week-container");

        horas.forEach(hora => {
            $weekContainer.append(`<div class="grid-item hour">${hora}</div>`);
            for (let i = 0; i < 7; i++) {
                $weekContainer.append('<div class="grid-item grid-hours"></div>');
            }
        });

        const eventos = [
            { start: "15:30", end: "17:30", day: "Martes" },
            { start: "17:30", end: "19:30", day: "Martes" },
            { start: "16:00", end: "17:00", day: "Jueves" },
            { start: "18:00", end: "19:00", day: "Viernes" }
        ];

        function crearCuadroFlotante(evento) {
            const { start, end, day } = evento;
            const dayIndex = diasSemana.indexOf(day);

            const startHourIndex = horas.findIndex(hora => hora.startsWith(start.split(":")[0]));
            const endHourIndex = horas.findIndex(hora => hora.startsWith(end.split(":")[0]));

            const startOffset = parseFloat(start.split(":")[1]) / 60;
            const endOffset = parseFloat(end.split(":")[1]) / 60;

            const cellHeight = 70;
            const cellWidth = 120;
            const borderSize = 1;

            const top = ((startHourIndex + startOffset) * (cellHeight + borderSize)) + 72;
            const height = (((endHourIndex - startHourIndex) + (endOffset - startOffset)) * cellHeight + ((endHourIndex - startHourIndex) * borderSize));

            const left = ((dayIndex) * (cellWidth + borderSize)) + 2;
            const width = cellWidth;

            const $floatingBox = $('<div class="floating-box"></div>').css({
                top: `${top}px`,
                left: `${left}px`,
                width: `${width - borderSize}px`,
                height: `${height - borderSize}px`,
            });

            $weekContainer.append($floatingBox);
        }

        eventos.forEach(evento => crearCuadroFlotante(evento));

        $(".div_header_month_container #prevMonth").on("click", function() {
            fechaActual.setMonth(fechaActual.getMonth() - 1);
            renderCalendar();
        });

        $(".div_header_month_container #nextMonth").on("click", function() {
            fechaActual.setMonth(fechaActual.getMonth() + 1);
            renderCalendar();
        });

        $(".div_header_month_container").on('click', '.div_month_day', function () {
            let fechaStr = $(this).data('day');
            let partes = fechaStr.split('-');
            let nuevaFecha = new Date(parseInt(partes[0]), parseInt(partes[1]) - 1, parseInt(partes[2]));

            if (nuevaFecha > cierre_agenda) {
                alert("No puedes seleccionar una fecha posterior al cierre de la agenda.");
                return;
            }

            cambiarDia(null, nuevaFecha);
            $("#btn-dia").trigger('click');
        });


        renderCalendar();
    });
</script>
</body>
</html>

