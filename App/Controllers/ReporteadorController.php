<?php

namespace App\Controllers;

use Phalcon\Mvc\Controller;
use Phalcon\Http\Request;  // Asegúrate de importar la clase Request
use Phalcon\Http\Response; // Asegúrate de importar la clase Response
use App\Library\FuncionesGlobales;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Exception;


class ReporteadorController extends BaseController
{
    protected $rutas;
    protected $url_api;

    public function initialize(){
        $config         = $this->getDI();
        $this->rutas    = $config->get('rutas');
        $config         = $config->get('config');
        $this->url_api  = $config['BASEAPI'];
    }

    public function IndexAction(){

        if ($this->request->isAjax()){
            $accion = $_POST['accion'];
            if ($accion == 'fill_profesionales'){
                $route      = $this->url_api.$this->rutas['ctprofesionales']['show'];
                $arr_info   = FuncionesGlobales::RequestApi('GET',$route,$_POST);

                $response = new Response();
                $response->setJsonContent($arr_info);
                $response->setStatusCode(200, 'OK');
                return $response;
            }

            if ($accion == 'fill_combo'){
                $route      = $this->url_api.$this->rutas['ctpacientes']['fill_combo'];
                $arr_info   = FuncionesGlobales::RequestApi('GET',$route,$_POST);

                $response = new Response();
                $response->setJsonContent($arr_info);
                $response->setStatusCode(200, 'OK');
                return $response;
            }

            if ($accion == 'reportes'){
                set_time_limit(3000);
                $route_file     = array();
                $tipo_reporte   = $_POST['tipo_reporte'];

                switch ($tipo_reporte) {
                    case 'general_citas':
                        $route_file = $this->reporte_general_citas();
                        break;
                    case 'general_ingresos':
                        $route_file = $this->reporte_general_ingresos();
                        break;
                }

                if ($route_file['status_code'] > 399){
                    $response = new Response();
                    $response->setJsonContent($route_file['error_msg']);
                    $response->setStatusCode($route_file['status_code'], 'ERROR');
                    return $response;
                }

                $response = new Response();
                $response->setJsonContent($route_file);
                $response->setStatusCode(200, 'OK');
                return $response;
            }
        }

        $route                  = $this->url_api.$this->rutas['ctlocaciones']['show'];
        $arr_locaciones= FuncionesGlobales::RequestApi('GET',$route,array('onlyallowed' => 1));

        $this->view->arr_locaciones     = $arr_locaciones; 
    }


    //  REPORTE GENERAL DE CITAS
    public function reporte_general_citas(){
        try{

            $route      = $this->url_api.$this->rutas['reportes']['general_citas'];
            $arr_rows   = FuncionesGlobales::RequestApi('GET',$route,$_POST);

            if (isset($arr_rows['status_code']) && $arr_rows['status_code'] > 399){
                throw new Exception($arr_rows['error'],$arr_rows['status_code']);
            }

            $info_usuario   = $this->session->get('nombre').' '.$this->session->get('primer_apellido');

            // 1. Crear nueva hoja de cálculo
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Resumen general de citas');

            $sheet->mergeCells('A1:E1');
            $sheet->setCellValue('A1', 'REPORTE GENERAL DE CITAS');

            $sheet->mergeCells('A2:E2');
            $sheet->setCellValue('A2', 'Sistema de Control de citas');
            $sheet->setCellValue('A4', 'Fecha de impresión: ');
            $sheet->setCellValue('B4', date('d/m/Y'));

            $sheet->setCellValue('A5', 'Hora de impresión: ');
            $sheet->setCellValue('B5', date('H:i:s'));

            $sheet->setCellValue('A6', 'Rango de fechas: ');
            $sheet->setCellValue('B6', FuncionesGlobales::formatearFecha($_POST['rango_fechas']['fecha_inicio']) .' al '.FuncionesGlobales::formatearFecha($_POST['rango_fechas']['fecha_termino']));

            $sheet->setCellValue('A7', 'Generado por:');
            $sheet->setCellValue('B7', $info_usuario);

            // == ESTILOS ==

            // Título principal
            $sheet->getStyle('A1')->applyFromArray([
                'font' => [
                    'bold'  => true,
                    'size'  => 16,
                    'color' => ['argb' => 'FFFFFFFF'],
                ],
                'fill' => [
                    'fillType'   => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FF1F4E79'],
                ],
                'alignment' => [
                    'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                    'vertical'   => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                ],
            ]);

            // Subtítulo
            $sheet->getStyle('A2')->applyFromArray([
                'font' => [
                    'bold'  => true,
                    'size'  => 12,
                    'color' => ['argb' => 'FFFFFFFF'],
                ],
                'fill' => [
                    'fillType'   => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FF2E75B6'],
                ],
                'alignment' => [
                    'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                ],
            ]);

            // Etiquetas de info
            $sheet->getStyle('A4:A7')->applyFromArray([
                'font' => ['bold' => true],
                'fill' => [
                    'fillType'   => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FFD6E4F0'],
                ],
            ]);

            $sheet->setCellValue('A9', 'FECHA DE LA CITA');
            $sheet->setCellValue('B9', 'DÍA');
            $sheet->setCellValue('C9', 'HORA INICIO');
            $sheet->setCellValue('D9', 'HORA TERMINO');
            $sheet->setCellValue('E9', 'SERVICIO(S)');
            $sheet->setCellValue('F9', 'ESTATUS');
            $sheet->setCellValue('G9', 'PROFESIONAL');
            $sheet->setCellValue('H9', 'PACIENTE');
            $sheet->setCellValue('I9', 'FECHA DE NACIMIENTO');
            $sheet->setCellValue('J9', 'EDAD');
            $sheet->setCellValue('K9', 'CELULAR');
            $sheet->setCellValue('L9', 'FECHA DE CAPTURA');
            $sheet->setCellValue('M9', 'ESTATUS DE ASISTENCIA');
            $sheet->setCellValue('N9', 'TOTAL');
            $sheet->setCellValue('O9', 'PAGADA');

            $sheet->getStyle('A9:O9')->applyFromArray([
                'font' => [
                    'bold'  => true,
                    'color' => ['argb' => 'FFFFFFFF'],
                ],
                'fill' => [
                    'fillType'   => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FF000000'],
                ],
            ]);

            $sheet->getStyle('J11:J'.(count($arr_rows) + 11))
            ->getNumberFormat()
            ->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_TEXT);

            // 4. Llenar datos
            $columna    = 10;
            foreach ($arr_rows as $row) {
                $sheet->setCellValue('A'.$columna, $row['fecha_cita']);
                $sheet->setCellValue('B'.$columna, $row['label_dia']);
                $sheet->setCellValue('C'.$columna, $row['hora_inicio']);
                $sheet->setCellValue('D'.$columna, $row['hora_termino']);
                $sheet->setCellValue('E'.$columna, $row['num_servicios_costo']);
                $sheet->setCellValue('F'.$columna, $row['estatus']);
                $sheet->setCellValue('G'.$columna, $row['nombre_profesional']);
                $sheet->setCellValue('H'.$columna, $row['nombre'].' '.$row['primer_apellido'].' '.$row['segundo_apellido']);
                $sheet->setCellValue('I'.$columna, $row['fecha_nacimiento']);
                //$sheet->setCellValue('J'.$columna, $row['edad_actual']);
                $sheet->setCellValueExplicit(
                    'J'.$columna,
                    $row['edad_actual'],
                    \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING
                );
                $sheet->setCellValue('K'.$columna, $row['celular']);
                $sheet->setCellValue('L'.$columna, $row['fecha_captura']);
                $sheet->setCellValue('M'.$columna, $row['label_asistencia']);
                $sheet->setCellValue('N'.$columna, '$'.$row['total']);
                $sheet->setCellValue('O'.$columna, $row['pagada']);

                $color_argb = 'FF' . ltrim($row['codigo_color'], '#');

                $sheet->getStyle('E'.$columna)->applyFromArray([
                    'fill' => [
                        'fillType'   => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                        'startColor' => ['argb' => $color_argb],
                    ],
                ]);

                $columna++;
            }

            // // 3. Estilos básicos (Negrita en la cabecera)
            // $sheet->getStyle('A1:C1')->getFont()->setBold(true);

            // // 4. Ajustar ancho de columna automáticamente
            foreach (range('A', 'O') as $col) {
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }

            $path_file = FuncionesGlobales::get_path_file('reportes');

            // 5. Guardar archivo
            $writer     = new Xlsx($spreadsheet);
            $fecha_archivo  = $this->cadena_fecha();
            $file_name      = 'reporte_general_citas_'.$fecha_archivo.'.xlsx';
            $writer->save($path_file.$file_name);

            return [
                'path_file'     => FuncionesGlobales::get_url_download('reportes',$file_name),
                'error_msg'     => '',
                'status_code'   => 200
            ];

        } catch(\Exception $e){
            return [
                'path_file'     => '',
                'error_msg'     => $e->getMessage(),
                'status_code'   => $e->getCode()
            ];
        }
    }

    //  REPORTE GENERAL DE CITAS
    public function reporte_general_ingresos(){
        try{

            $route      = $this->url_api.$this->rutas['reportes']['general_ingresos'];
            $arr_rows   = FuncionesGlobales::RequestApi('GET',$route,$_POST);

            if (isset($arr_rows['status_code']) && $arr_rows['status_code'] > 399){
                throw new Exception($arr_rows['error'],$arr_rows['status_code']);
            }

            $info_usuario   = $this->session->get('nombre').' '.$this->session->get('primer_apellido');

            // 1. Crear nueva hoja de cálculo
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Resumen general de ingresos');

            $sheet->mergeCells('A1:E1');
            $sheet->setCellValue('A1', 'REPORTE GENERAL DE INGRESOS');

            $sheet->mergeCells('A2:E2');
            $sheet->setCellValue('A2', 'Sistema de Control de citas');
            $sheet->setCellValue('A4', 'Fecha de impresión: ');
            $sheet->setCellValue('B4', date('d/m/Y'));

            $sheet->setCellValue('A5', 'Hora de impresión: ');
            $sheet->setCellValue('B5', date('H:i:s'));

            $sheet->setCellValue('A6', 'Rango de fechas: ');
            $sheet->setCellValue('B6', FuncionesGlobales::formatearFecha($_POST['rango_fechas']['fecha_inicio']) .' al '.FuncionesGlobales::formatearFecha($_POST['rango_fechas']['fecha_termino']));

            $sheet->setCellValue('A7', 'Generado por:');
            $sheet->setCellValue('B7', $info_usuario);

            // == ESTILOS ==

            // Título principal
            $sheet->getStyle('A1')->applyFromArray([
                'font' => [
                    'bold'  => true,
                    'size'  => 16,
                    'color' => ['argb' => 'FFFFFFFF'],
                ],
                'fill' => [
                    'fillType'   => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FF1F4E79'],
                ],
                'alignment' => [
                    'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                    'vertical'   => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                ],
            ]);

            // Subtítulo
            $sheet->getStyle('A2')->applyFromArray([
                'font' => [
                    'bold'  => true,
                    'size'  => 12,
                    'color' => ['argb' => 'FFFFFFFF'],
                ],
                'fill' => [
                    'fillType'   => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FF2E75B6'],
                ],
                'alignment' => [
                    'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                ],
            ]);

            // Etiquetas de info
            $sheet->getStyle('A4:A7')->applyFromArray([
                'font' => ['bold' => true],
                'fill' => [
                    'fillType'   => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FFD6E4F0'],
                ],
            ]);

            //  INFORMACION DE LA HOJA 1
            $sheet->setCellValue('A9', 'Indicador');
            $sheet->setCellValue('B9', 'Valor');

            $sheet->getStyle('A9:B9')->applyFromArray([
                'font' => [
                    'bold'  => true,
                    'color' => ['argb' => 'FFFFFFFF'], // Letras blancas
                    'name'  => 'Arial',
                    'size'  => 11,
                ],
                'fill' => [
                    'fillType'   => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FF000000'], // Fondo negro
                ],
                'alignment' => [
                    'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                    'vertical'   => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                ],
            ]);

            $sheet->setCellValue('A10', 'Total de pagos en efectivo');
            $sheet->setCellValue('B10', '$'.FuncionesGlobales::formatoMonetario($arr_rows['hoja_1']['total_efectivo']));

            $sheet->setCellValue('A11', 'Total de pagos por transferencia');
            $sheet->setCellValue('B11', '$'.FuncionesGlobales::formatoMonetario($arr_rows['hoja_1']['total_transferencia']));

            $sheet->setCellValue('A12', 'Total');
            $sheet->setCellValue('B12', '$'.FuncionesGlobales::formatoMonetario($arr_rows['hoja_1']['total_pagos']));

            $sheet->getStyle('A9:B12')->applyFromArray([
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                        'color'       => ['argb' => 'FF000000'],
                    ],
                ],
            ]);

            $sheet2 = $spreadsheet->createSheet();

            $sheet2->setTitle('DETALLE DE INFORMACIÓN');

            $sheet2->mergeCells('A1:E1');
            $sheet2->setCellValue('A1', 'REPORTE GENERAL DE INGRESOS');

            $sheet2->mergeCells('A2:E2');
            $sheet2->setCellValue('A2', 'Sistema de Control de citas');
            $sheet2->setCellValue('A4', 'Fecha de impresión: ');
            $sheet2->setCellValue('B4', date('d/m/Y'));

            $sheet2->setCellValue('A5', 'Hora de impresión: ');
            $sheet2->setCellValue('B5', date('H:i:s'));

            $sheet2->setCellValue('A6', 'Rango de fechas: ');
            $sheet2->setCellValue('B6', FuncionesGlobales::formatearFecha($_POST['rango_fechas']['fecha_inicio']) .' al '.FuncionesGlobales::formatearFecha($_POST['rango_fechas']['fecha_termino']));

            $sheet2->setCellValue('A7', 'Generado por:');
            $sheet2->setCellValue('B7', $info_usuario);

            // == ESTILOS ==

            // Título principal
            $sheet2->getStyle('A1')->applyFromArray([
                'font' => [
                    'bold'  => true,
                    'size'  => 16,
                    'color' => ['argb' => 'FFFFFFFF'],
                ],
                'fill' => [
                    'fillType'   => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FF1F4E79'],
                ],
                'alignment' => [
                    'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                    'vertical'   => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                ],
            ]);

            // Subtítulo
            $sheet2->getStyle('A2')->applyFromArray([
                'font' => [
                    'bold'  => true,
                    'size'  => 12,
                    'color' => ['argb' => 'FFFFFFFF'],
                ],
                'fill' => [
                    'fillType'   => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FF2E75B6'],
                ],
                'alignment' => [
                    'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                ],
            ]);

            // Etiquetas de info
            $sheet2->getStyle('A4:A7')->applyFromArray([
                'font' => ['bold' => true],
                'fill' => [
                    'fillType'   => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FFD6E4F0'],
                ],
            ]);
        
            foreach (range('A', 'E') as $col) {
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }

            $sheet2->setTitle('DETALLE DE INFORMACIÓN');

            $sheet2->mergeCells('A1:E1');
            $sheet2->setCellValue('A1', 'REPORTE GENERAL DE INGRESOS');

            $sheet2->mergeCells('A2:E2');
            $sheet2->setCellValue('A2', 'Sistema de Control de citas');
            $sheet2->setCellValue('A4', 'Fecha de impresión: ');
            $sheet2->setCellValue('B4', date('d/m/Y'));

            $sheet2->setCellValue('A5', 'Hora de impresión: ');
            $sheet->setCellValue('B5', date('H:i:s'));

            $sheet2->setCellValue('A6', 'Rango de fechas: ');
            $sheet2->setCellValue('B6', FuncionesGlobales::formatearFecha($_POST['rango_fechas']['fecha_inicio']) .' al '.FuncionesGlobales::formatearFecha($_POST['rango_fechas']['fecha_termino']));

            $sheet2->setCellValue('A7', 'Generado por:');
            $sheet2->setCellValue('B7', $info_usuario);

            // == ESTILOS ==

            // Título principal
            $sheet2->getStyle('A1')->applyFromArray([
                'font' => [
                    'bold'  => true,
                    'size'  => 16,
                    'color' => ['argb' => 'FFFFFFFF'],
                ],
                'fill' => [
                    'fillType'   => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FF1F4E79'],
                ],
                'alignment' => [
                    'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                    'vertical'   => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                ],
            ]);

            // Subtítulo
            $sheet2->getStyle('A2')->applyFromArray([
                'font' => [
                    'bold'  => true,
                    'size'  => 12,
                    'color' => ['argb' => 'FFFFFFFF'],
                ],
                'fill' => [
                    'fillType'   => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FF2E75B6'],
                ],
                'alignment' => [
                    'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                ],
            ]);

            // Etiquetas de info
            $sheet2->getStyle('A4:A7')->applyFromArray([
                'font' => ['bold' => true],
                'fill' => [
                    'fillType'   => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FFD6E4F0'],
                ],
            ]);
            

            $sheet2->setCellValue('A9', 'FECHA DE PAGO');
            $sheet2->setCellValue('B9', 'TOTAL POR TRANSFERENCIAS');
            $sheet2->setCellValue('C9', 'TOTAL POR EFECTIVO');
            $sheet2->setCellValue('D9', 'TOTAL PAGADO');

            $sheet2->getStyle('A9:D9')->applyFromArray([
                'font' => [
                    'bold'  => true,
                    'color' => ['argb' => 'FFFFFFFF'],
                ],
                'fill' => [
                    'fillType'   => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FF000000'],
                ],
            ]);

            // 4. Llenar datos
            $columna    = 10;
            foreach ($arr_rows['hoja_2'] as $row) {
                $sheet2->setCellValue('A'.$columna, $row['fecha_pago']);
                $sheet2->setCellValue('B'.$columna, '$'.FuncionesGlobales::formatoMonetario($row['total_transferencia']));
                $sheet2->setCellValue('C'.$columna, '$'.FuncionesGlobales::formatoMonetario($row['total_efectivo']));
                $sheet2->setCellValue('D'.$columna, '$'.FuncionesGlobales::formatoMonetario($row['total_pagos']));

                $columna++;
            }

            foreach (range('A', 'E') as $col) {
                $sheet2->getColumnDimension($col)->setAutoSize(true);
            }

            $spreadsheet->setActiveSheetIndex(0);

            $path_file = FuncionesGlobales::get_path_file('reportes');

            // 5. Guardar archivo
            $writer         = new Xlsx($spreadsheet);
            $fecha_archivo  = $this->cadena_fecha();
            $file_name      = 'reporte_general_ingresos_'.$fecha_archivo.'.xlsx';
            $writer->save($path_file.$file_name);

            return [
                'path_file'     => FuncionesGlobales::get_url_download('reportes',$file_name),
                'error_msg'     => '',
                'status_code'   => 200
            ];

        } catch(\Exception $e){
            return [
                'path_file'     => '',
                'error_msg'     => $e->getMessage(),
                'status_code'   => $e->getCode()
            ];
        }
    }

    //  FUNCION PARA GENERAR NOMBRE DE ARCHIVO
    public function cadena_fecha(){
        return date('dmy_His');
    }

}