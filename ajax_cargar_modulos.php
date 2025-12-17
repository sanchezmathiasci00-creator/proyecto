<?php
// ajax_cargar_modulos.php
include('conexion.php');

if (isset($_GET['especialidad'])) {
    $especialidad_id = intval($_GET['especialidad']);
    
    $query = $conn->query("SELECT * FROM modulos WHERE id_especialidad = $especialidad_id ORDER BY nm_modulos");
    
    $options = '<option value="">Seleccionar módulo...</option>';
    while ($modulo = $query->fetch_assoc()) {
        $options .= '<option value="' . $modulo['id_modulos'] . '">' . htmlspecialchars($modulo['nm_modulos']) . '</option>';
    }
    
    echo $options;
}
?>