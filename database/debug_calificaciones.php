<?php
require_once __DIR__ . '/../config/conexion.php';

try {
    $conn = conectar();
    
    echo "<h2>Debug: Calificaciones Tray-1 Trim-2</h2>";
    
    // 1. Buscar la oferta de Tray-1 Trim-2 PNF Administración
    echo "<h3>1. Ofertas Tray-1 Trim-2 PNF Administración</h3>";
    $stmt = $conn->prepare("
        SELECT oa.id, oa.estatus, p.nombre AS pnf, t.nombre AS trayecto, tr.nombre AS trimestre
        FROM oferta_academica oa
        JOIN pnfs p ON oa.pnf_id = p.id
        JOIN trayectos t ON oa.trayecto_id = t.id
        JOIN trimestres tr ON oa.trimestre_id = tr.id
        WHERE p.nombre LIKE '%Administracion%' 
        AND t.nombre LIKE '%I%'
        AND tr.nombre LIKE '%2%'
        ORDER BY oa.id DESC
    ");
    $stmt->execute();
    $ofertas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($ofertas as $oferta) {
        echo "<p>Oferta ID: {$oferta['id']} - {$oferta['pnf']} - {$oferta['trayecto']} - {$oferta['trimestre']} ({$oferta['estatus']})</p>";
    }
    
    if (!empty($ofertas)) {
        $oferta_actual = $ofertas[0]; // Tomar la primera
        $oferta_id = $oferta_actual['id'];
        
        echo "<h3>2. Estudiantes inscritos en Oferta ID: {$oferta_id}</h3>";
        
        // 2. Ver estudiantes inscritos en esta oferta
        $stmt = $conn->prepare("
            SELECT DISTINCT e.id AS estudiante_id, u.cedula, u.nombre, u.apellido,
                   COUNT(i.id) as materias_inscritas
            FROM estudiantes e
            JOIN usuarios u ON e.usuario_id = u.id
            JOIN inscripciones i ON e.id = i.estudiante_id
            JOIN oferta_materias om ON i.oferta_materia_id = om.id
            WHERE om.oferta_academica_id = ?
            GROUP BY e.id, u.cedula, u.nombre, u.apellido
            ORDER BY u.apellido, u.nombre
        ");
        $stmt->execute([$oferta_id]);
        $estudiantes_inscritos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<p><strong>Total estudiantes inscritos:</strong> " . count($estudiantes_inscritos) . "</p>";
        
        foreach ($estudiantes_inscritos as $est) {
            echo "<p>- {$est['cedula']} - {$est['apellido']}, {$est['nombre']} ({$est['materias_inscritas']} materias)</p>";
        }
        
        echo "<h3>3. Materias de la oferta</h3>";
        
        // 3. Ver materias de esta oferta
        $stmt = $conn->prepare("
            SELECT om.id AS oferta_materia_id, m.nombre AS materia_nombre
            FROM oferta_materias om
            JOIN materias m ON om.materia_id = m.id
            WHERE om.oferta_academica_id = ?
        ");
        $stmt->execute([$oferta_id]);
        $materias = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($materias as $mat) {
            echo "<p>- {$mat['materia_nombre']} (ID: {$mat['oferta_materia_id']})</p>";
        }
        
        echo "<h3>4. Estudiantes que deberían aparecer (mismo PNF, Trayecto, Trimestre)</h3>";
        
        // 4. Estudiantes que deberían aparecer según sus datos académicos
        $stmt = $conn->prepare("
            SELECT e.id, u.cedula, u.nombre, u.apellido, e.estado_academico
            FROM estudiantes e
            JOIN usuarios u ON e.usuario_id = u.id
            JOIN oferta_academica oa ON e.pnf_id = oa.pnf_id 
                AND e.trayecto_id = oa.trayecto_id 
                AND e.trimestre_id = oa.trimestre_id
            WHERE oa.id = ? AND e.estado_academico = 'cursando'
            ORDER BY u.apellido, u.nombre
        ");
        $stmt->execute([$oferta_id]);
        $estudiantes_deberian = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<p><strong>Total estudiantes que deberían aparecer:</strong> " . count($estudiantes_deberian) . "</p>";
        
        foreach ($estudiantes_deberian as $est) {
            echo "<p>- {$est['cedula']} - {$est['apellido']}, {$est['nombre']} ({$est['estado_academico']})</p>";
        }
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>