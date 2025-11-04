<?php
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../controladores/hellpers/auth.php';

verificarSesion();
$ofertas = [];
$error_message = '';

try {
    $conn = conectar();
    if (!$conn) {
        throw new Exception("Error de conexión a la base de datos.");
    }

    // Obtener rol y aldea del usuario actual
    $usuario_actual = $_SESSION['usuario'];
    $rol_actual = $usuario_actual['rol'];
    $aldea_coordinador = null;
    
    if ($rol_actual === 'coordinador') {
        $stmt_coord = $conn->prepare("SELECT aldea_id FROM coordinadores WHERE usuario_id = ?");
        $stmt_coord->execute([$usuario_actual['id']]);
        $coord_data = $stmt_coord->fetch(PDO::FETCH_ASSOC);
        $aldea_coordinador = $coord_data['aldea_id'] ?? null;
        
        // Si no tiene aldea asignada, no puede ver ofertas
        if (!$aldea_coordinador) {
            $ofertas = [];
            $error_message = "No tiene una aldea asignada. Contacte al administrador.";
            return;
        }
    }

    // Consulta de ofertas académicas con filtro por aldea para coordinadores
    $sql = "
        SELECT 
            oa.id,
            oa.estatus,
            oa.aldea_id,
            p.nombre AS nombre_pnf,
            t.slug AS nombre_trayecto, 
            tr.nombre AS nombre_trimestre,
            a.nombre AS nombre_aldea,
            
            -- La lógica clave para obtener la fecha de inicio REAL de la oferta
            COALESCE(oa.fecha_inicio_excepcion, tr.fecha_inicio) AS fecha_inicio_real,
            
            -- También seleccionamos la fecha de excepción para que la vista la conozca
            oa.fecha_inicio_excepcion
            
        FROM 
            oferta_academica oa
        INNER JOIN 
            pnfs p ON oa.pnf_id = p.id
        INNER JOIN 
            trayectos t ON oa.trayecto_id = t.id
        INNER JOIN 
            trimestres tr ON oa.trimestre_id = tr.id
        LEFT JOIN 
            aldeas a ON oa.aldea_id = a.id";
    
    $params = [];
    
    // Filtrar por aldea si es coordinador
    if ($rol_actual === 'coordinador') {
        $sql .= " WHERE oa.aldea_id = ?";
        $params[] = $aldea_coordinador;
    }
    
    $sql .= " ORDER BY fecha_inicio_real DESC, p.nombre ASC";
    
    $consulta = $conn->prepare($sql);
    $consulta->execute($params);
    $ofertas = $consulta->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    $error_message = "Error al cargar las ofertas académicas: " . htmlspecialchars($e->getMessage());
}

// Las variables $ofertas y $error_message están listas para la vista.
?>