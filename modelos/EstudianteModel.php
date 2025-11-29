<?php
class EstudianteModel {
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    public function obtenerTodos(): array {
        $sql = "SELECT e.*, a.nombre as nombre_aldea FROM estudiantes e 
                LEFT JOIN aldeas a ON e.aldea_id = a.id 
                ORDER BY e.apellido, e.nombre";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerPorId(int $id): ?array {
        $sql = "SELECT e.*, a.nombre as nombre_aldea FROM estudiantes e 
                LEFT JOIN aldeas a ON e.aldea_id = a.id 
                WHERE e.id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    public function existeEstudiante(string $cedula, int $excluirId = null): bool {
        if ($excluirId) {
            $sql = "SELECT id FROM estudiantes WHERE cedula = ? AND id != ?";
            $params = [$cedula, $excluirId];
        } else {
            $sql = "SELECT id FROM estudiantes WHERE cedula = ?";
            $params = [$cedula];
        }
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return (bool)$stmt->fetch();
    }

    public function crearEstudiante(string $nombre, string $apellido, string $cedula, string $telefono, int $aldea_id, string $codigo_estudiante): bool {
        $sql = "INSERT INTO estudiantes (nombre, apellido, cedula, telefono, aldea_id, codigo_estudiante) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([$nombre, $apellido, $cedula, $telefono, $aldea_id, $codigo_estudiante]);
    }

    public function actualizarEstudiante(int $id, string $nombre, string $apellido, string $cedula, string $telefono, int $aldea_id): bool {
        $sql = "UPDATE estudiantes SET nombre = ?, apellido = ?, cedula = ?, telefono = ?, aldea_id = ? WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([$nombre, $apellido, $cedula, $telefono, $aldea_id, $id]);
    }

    public function eliminarEstudiante(int $id): bool {
        $sql = "DELETE FROM estudiantes WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->rowCount() > 0;
    }

    public function generarCodigoEstudiante(): string {
        $year = date('Y');
        $sql = "SELECT COUNT(*) + 1 as siguiente FROM estudiantes WHERE YEAR(fecha_registro) = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$year]);
        $numero = $stmt->fetch()['siguiente'];
        return $year . str_pad($numero, 4, '0', STR_PAD_LEFT);
    }
}