<?php
class ReporteModel {
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    public function obtenerAldeaCoordinador(int $usuario_id): ?int {
        $sql = "SELECT aldea_id FROM coordinadores WHERE usuario_id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$usuario_id]);
        return $stmt->fetchColumn() ?: null;
    }

    public function obtenerInfoReporte(int $aldea_id, int $pnf_id, ?int $trayecto_id = null, ?int $trimestre_id = null): ?array {
        $sql = "SELECT 
                    a.nombre as aldea_nombre, 
                    p.nombre as pnf_nombre,
                    t.nombre as trayecto_nombre,
                    tr.nombre as trimestre_nombre
                FROM aldeas a, pnfs p
                LEFT JOIN trayectos t ON t.id = ?
                LEFT JOIN trimestres tr ON tr.id = ?
                WHERE a.id = ? AND p.id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$trayecto_id, $trimestre_id, $aldea_id, $pnf_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    public function obtenerEstudiantes(int $aldea_id, int $pnf_id, ?int $trayecto_id = null, ?int $trimestre_id = null): array {
        $where_conditions = ["e.aldea_id = ?", "e.pnf_id = ?"];
        $params = [$aldea_id, $pnf_id];
        
        if ($trayecto_id > 0) {
            $where_conditions[] = "e.trayecto_id = ?";
            $params[] = $trayecto_id;
        }
        
        if ($trimestre_id > 0) {
            $where_conditions[] = "e.trimestre_id = ?";
            $params[] = $trimestre_id;
        }
        
        $where_clause = implode(' AND ', $where_conditions);
        
        $sql = "SELECT 
                    u.cedula,
                    u.nombre,
                    u.apellido,
                    e.codigo_estudiante,
                    e.fecha_ingreso,
                    e.estado_academico,
                    t.nombre as trayecto_nombre,
                    tr.nombre as trimestre_nombre
                FROM estudiantes e
                JOIN usuarios u ON e.usuario_id = u.id
                LEFT JOIN trayectos t ON e.trayecto_id = t.id
                LEFT JOIN trimestres tr ON e.trimestre_id = tr.id
                WHERE $where_clause
                ORDER BY u.apellido, u.nombre";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerProfesores(int $aldea_id): array {
        $sql = "SELECT 
                    u.cedula,
                    u.nombre,
                    u.apellido,
                    p.especialidad,
                    p.fecha_ingreso,
                    a.nombre as aldea_nombre
                FROM profesores p
                JOIN usuarios u ON p.usuario_id = u.id
                JOIN aldeas a ON p.aldea_id = a.id
                WHERE p.aldea_id = ?
                ORDER BY u.apellido, u.nombre";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$aldea_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerEstudiantesInscritos(int $seccion_id): array {
        $sql = "SELECT 
                    u.cedula,
                    u.nombre,
                    u.apellido,
                    e.codigo_estudiante,
                    i.fecha_inscripcion,
                    m.nombre as materia_nombre,
                    s.codigo_seccion
                FROM inscripciones i
                JOIN estudiantes e ON i.estudiante_id = e.id
                JOIN usuarios u ON e.usuario_id = u.id
                JOIN secciones s ON i.seccion_id = s.id
                JOIN materias m ON s.materia_id = m.id
                WHERE i.seccion_id = ?
                ORDER BY u.apellido, u.nombre";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$seccion_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerCalificaciones(int $seccion_id): array {
        $sql = "SELECT 
                    u.cedula,
                    u.nombre,
                    u.apellido,
                    e.codigo_estudiante,
                    c.nota_final,
                    c.observaciones,
                    m.nombre as materia_nombre,
                    s.codigo_seccion
                FROM calificaciones c
                JOIN estudiantes e ON c.estudiante_id = e.id
                JOIN usuarios u ON e.usuario_id = u.id
                JOIN secciones s ON c.seccion_id = s.id
                JOIN materias m ON s.materia_id = m.id
                WHERE c.seccion_id = ?
                ORDER BY u.apellido, u.nombre";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$seccion_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}