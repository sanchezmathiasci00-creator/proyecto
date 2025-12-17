<?php
// Configurar zona horaria de Perú
date_default_timezone_set('America/Lima');

// Configurar encoding UTF-8
header('Content-Type: text/html; charset=utf-8');

session_start();
if (!isset($_SESSION['usuario'])) {
  header("Location: login.php");
  exit;
}
include("conexion.php");

// Solo permitir a administradores, coordinadores y asistentes
if ($_SESSION['rol'] != 'ADMIN' && $_SESSION['rol'] != 'COORDINADOR' && $_SESSION['rol'] != 'ASISTENTE') {
  header("Location: index.php");
  exit;
}

// Verificar que se haya seleccionado un practicante
if (!isset($_GET['dni_practicante']) || empty($_GET['dni_practicante'])) {
  die("Error: No se ha seleccionado un practicante");
}

$dni_practicante = $_GET['dni_practicante'];
$fecha_desde = isset($_GET['fecha_desde']) ? $_GET['fecha_desde'] : date('Y-m-01');
$fecha_hasta = isset($_GET['fecha_hasta']) ? $_GET['fecha_hasta'] : date('Y-m-d');

// Obtener datos del practicante
$stmt_practicante = $conn->prepare("
    SELECT 
        p.dni,
        p.nombres,
        p.apellidos,
        e.nm_especialidades as especialidad,
        m.nm_modulos as modulo,
        m.horas_requeridas,
        p.semestre,
        t.nm_turnos as turno
    FROM practicantes p
    LEFT JOIN especialidades e ON p.id_especialidades = e.id_especialidades
    LEFT JOIN modulos m ON p.id_modulos = m.id_modulos
    LEFT JOIN turnos t ON p.id_turnos = t.id_turnos
    WHERE p.dni = ?
");
$stmt_practicante->bind_param("i", $dni_practicante);
$stmt_practicante->execute();
$practicante = $stmt_practicante->get_result()->fetch_assoc();

if (!$practicante) {
  die("Error: Practicante no encontrado");
}

// Obtener asistencias del practicante (CON TAREAS)
$sql_asistencias = "
    SELECT 
        a.fecha,
        a.hora_ingreso,
        a.hora_salida,
        a.horas_dia,
        a.estado,
        a.tareas_realizadas,
        m.nm_modulos as modulo
    FROM asistencia a
    INNER JOIN modulos m ON a.id_modulo = m.id_modulos
    WHERE a.dni_practicante = ? 
    AND a.fecha BETWEEN ? AND ?
    ORDER BY a.fecha ASC
";

$stmt_asistencias = $conn->prepare($sql_asistencias);
$stmt_asistencias->bind_param("iss", $dni_practicante, $fecha_desde, $fecha_hasta);
$stmt_asistencias->execute();
$asistencias = $stmt_asistencias->get_result();

// Calcular total de horas
$total_horas = 0;
while ($row = $asistencias->fetch_assoc()) {
    $total_horas += $row['horas_dia'];
}
$asistencias->data_seek(0); // Resetear el puntero del resultado

// Incluir FPDF
require('fpdf/fpdf.php');

class PDF extends FPDF
{
    // Encabezado del reporte (igual al documento)
    function Header()
    {
        // Título principal - GESTIÓN ADMINISTRATIVA
        $this->SetFont('Arial', 'B', 16);
        $this->Cell(0, 10, utf8_decode('GESTIÓN ADMINISTRATIVA'), 0, 1, 'C');
        
        // Subtítulo - PARTE DIARIO DE ASISTENCIA - PRACTICANTES
        $this->SetFont('Arial', 'B', 14);
        $this->Cell(0, 8, utf8_decode('PARTE DIARIO DE ASISTENCIA - PRACTICANTES'), 0, 1, 'C');
        $this->Ln(5);
    }

    // Pie de página
    function Footer()
    {
        $this->SetY(-40);
        $this->SetFont('Arial', '', 10);
        $this->SetTextColor(0);
        
        // Línea para firma del coordinador
        $this->Cell(0, 5, '__________________________________________________', 0, 1, 'C');
        $this->SetFont('Arial', 'B', 10);
        $this->Cell(0, 5, utf8_decode('Mg. Negron Alvarado Elmer Augurio'), 0, 1, 'C');
        $this->SetFont('Arial', '', 10);
        $this->Cell(0, 5, utf8_decode('COORD. DE A. A. GESTIÓN ADMINISTRATIVA'), 0, 1, 'C');
        
        $this->Ln(10);
        
        // Línea para firma de la asistente
        $this->Cell(0, 5, '__________________________________________________', 0, 1, 'C');
        $this->Cell(0, 5, utf8_decode('Yoselin Carla Benites Duran'), 0, 1, 'C');
        $this->Cell(0, 5, utf8_decode('ASISTENTE DE GESTIÓN ADMINISTRATIVA'), 0, 1, 'C');
    }
}

$pdf = new PDF('P', 'mm', 'A4');
$pdf->AddPage();
$pdf->SetFont('Arial', '', 11);
$pdf->SetTextColor(0);

// Información del practicante - Estilo igual al documento
$pdf->SetFont('Arial', '', 11);

// Primera fila: APELLIDOS Y NOMBRES
$pdf->Cell(50, 8, utf8_decode('APELLIDOS Y NOMBRES :'), 0, 0);
$pdf->SetFont('Arial', 'B', 11);
$pdf->Cell(0, 8, utf8_decode($practicante['apellidos'] . ' ' . $practicante['nombres']), 0, 1);
$pdf->SetFont('Arial', '', 11);

$pdf->Ln(2);

// Segunda fila: ESPECIALIDAD y MODULO
$pdf->Cell(40, 8, utf8_decode('ESPECIALIDAD:'), 0, 0);
$pdf->SetFont('Arial', 'B', 11);
$pdf->Cell(70, 8, utf8_decode($practicante['especialidad']), 0, 0);
$pdf->SetFont('Arial', '', 11);
$pdf->Cell(30, 8, utf8_decode('MODULO:'), 0, 0);
$pdf->SetFont('Arial', 'B', 11);
$pdf->Cell(0, 8, utf8_decode($practicante['modulo']), 0, 1);
$pdf->SetFont('Arial', '', 11);

$pdf->Ln(2);

// Tercera fila: SEMESTRE, TOTAL DE HORAS y TURNO
$pdf->Cell(30, 8, utf8_decode('SEMESTRE:'), 0, 0);
$pdf->SetFont('Arial', 'B', 11);
$pdf->Cell(20, 8, $practicante['semestre'], 0, 0);
$pdf->SetFont('Arial', '', 11);
$pdf->Cell(45, 8, utf8_decode('TOTAL DE HORAS:'), 0, 0);
$pdf->SetFont('Arial', 'B', 11);
$pdf->Cell(20, 8, $practicante['horas_requeridas'] ?? '143', 0, 0);
$pdf->SetFont('Arial', '', 11);
$pdf->Cell(20, 8, utf8_decode('TURNO:'), 0, 0);
$pdf->SetFont('Arial', 'B', 11);
$pdf->Cell(0, 8, $practicante['turno'], 0, 1);
$pdf->SetFont('Arial', '', 11);

$pdf->Ln(10);

// Tabla de asistencias
// Encabezado de la tabla
$pdf->SetFillColor(192); // Gris claro para encabezados
$pdf->SetFont('Arial', 'B', 10);

// Encabezados de la tabla (iguales al documento)
$pdf->Cell(25, 8, 'FECHA', 1, 0, 'C', true);
$pdf->Cell(25, 8, 'Hr. INGRESO', 1, 0, 'C', true);
$pdf->Cell(25, 8, 'Hr. SALIDA', 1, 0, 'C', true);
$pdf->Cell(25, 8, utf8_decode('Hrs. POR DÍA'), 1, 0, 'C', true);
$pdf->Cell(60, 8, 'TAREAS REALIZADAS', 1, 0, 'C', true);
$pdf->Cell(30, 8, 'FIRMA', 1, 1, 'C', true);

// Datos de asistencias
$pdf->SetFont('Arial', '', 10);

if ($asistencias->num_rows > 0) {
    while ($asistencia = $asistencias->fetch_assoc()) {
        $pdf->Cell(25, 8, date('d/m/Y', strtotime($asistencia['fecha'])), 1, 0, 'C');
        $pdf->Cell(25, 8, date('H:i', strtotime($asistencia['hora_ingreso'])), 1, 0, 'C');
        $pdf->Cell(25, 8, $asistencia['hora_salida'] ? date('H:i', strtotime($asistencia['hora_salida'])) : '-', 1, 0, 'C');
        
        // Formato de horas como en el documento (ej: 5H, 6H)
        $horas_formato = number_format($asistencia['horas_dia'], 0) . 'H';
        $pdf->Cell(25, 8, $horas_formato, 1, 0, 'C');
        
        // Tareas realizadas
        $tareas_text = $asistencia['tareas_realizadas'];
        if (empty($tareas_text)) {
            $tareas_text = '-';
        }
        $pdf->Cell(60, 8, utf8_decode($tareas_text), 1, 0, 'L');
        
        // Celda para firma (vacía)
        $pdf->Cell(30, 8, '', 1, 1, 'C');
    }
} else {
    $pdf->Cell(0, 8, utf8_decode('No hay asistencias registradas en el período seleccionado'), 1, 1, 'C');
}

// Total de horas al final
$pdf->Ln(10);
$pdf->SetFont('Arial', 'B', 11);
$pdf->Cell(100, 8, utf8_decode('TOTAL DE HORAS ACUMULADAS:'), 0, 0, 'R');
$pdf->Cell(30, 8, number_format($total_horas, 0) . ' HORAS', 0, 1, 'L');

// Generar PDF
$nombre_completo = $practicante['apellidos'] . '_' . $practicante['nombres'];
$nombre_archivo = 'PARTE_ASISTENCIA_' . str_replace(' ', '_', $nombre_completo) . '.pdf';
$pdf->Output('I', $nombre_archivo);

// Cerrar conexiones
$stmt_practicante->close();
$stmt_asistencias->close();
$conn->close();
?>