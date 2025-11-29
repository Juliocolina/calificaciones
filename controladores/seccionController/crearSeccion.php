<?php
require_once __DIR__ . '/../../controladores/hellpers/auth.php';
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../modelos/SeccionModel.php';

verificarSesion();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirigir('error', 'Método no permitido.', 'secciones/crearSeccion.php');
    exit;
}

$conn = conectar();
if (!$conn) {
    redirigir('error', 'No se pudo establecer conexión con la BD.', 'secciones/crearSeccion.php');
    exit;
}

$seccionModel = new SeccionModel($conn);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $oferta_academica_id = intval($_POST['oferta_academica_id'] ?? 0);
    $materia_id = intval($_POST['materia_id'] ?? 0);
    $profesor_id = intval($_POST['profesor_id'] ?? 0);
    $cupo_maximo = intval($_POST['cupo_maximo'] ?? 30);
    
    if ($oferta_academica_id <= 0 || $materia_id <= 0 || $profesor_id <= 0) {
        redirigir('error', 'Todos los campos son obligatorios.', 'secciones/crearSeccion.php');
        exit;
    }
    
    try {
        if (!$seccionModel->validarOfertaAbierta($oferta_academica_id)) {
            redirigir('error', 'La oferta académica debe estar abierta.', 'secciones/crearSeccion.php');
            exit;
        }
        
        if (!$seccionModel->validarMateriaEnOferta($materia_id, $oferta_academica_id)) {
            redirigir('error', 'La materia no pertenece al PNF de esta oferta.', 'secciones/crearSeccion.php');
            exit;
        }
        
        if ($seccionModel->existeSeccion($oferta_academica_id, $materia_id, $profesor_id)) {
            redirigir('error', 'Ya existe una sección para esta combinación.', 'secciones/crearSeccion.php');
            exit;
        }
        
        $codigo_seccion = $seccionModel->generarCodigoSeccion($oferta_academica_id, $materia_id);
        
        if ($seccionModel->crearSeccion($oferta_academica_id, $materia_id, $profesor_id, $cupo_maximo, $codigo_seccion)) {
            redirigir('exito', 'Sección creada exitosamente.', 'secciones/verSecciones.php');
        } else {
            redirigir('error', 'No se pudo crear la sección.', 'secciones/crearSeccion.php');
        }
        
    } catch (PDOException $e) {
        redirigir('error', 'Error al crear sección: ' . $e->getMessage(), 'secciones/crearSeccion.php');
    }
}

exit;