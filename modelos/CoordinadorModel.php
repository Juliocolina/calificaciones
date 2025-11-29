<?php
class CoordinadorModel {
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    public function obtenerTodos(): array {
        $sql = "SELECT c.*, a.nombre as nombre_aldea FROM coordinadores c 
                LEFT JOIN aldeas a ON c.aldea_id = a.id 
                ORDER BY c.nombre";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerPorId(int $id): ?array {
        $sql = "SELECT c.*, a.nombre as nombre_aldea FROM coordinadores c 
                LEFT JOIN aldeas a ON c.aldea_id = a.id 
                WHERE c.id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    public function existeCoordinador(string $cedula, int $excluirId = null): bool {
        // La tabla coordinadores no tiene cedula directamente, verificar en usuarios
        if ($excluirId) {
            $sql = "SELECT c.id FROM coordinadores c 
                    INNER JOIN usuarios u ON c.usuario_id = u.id 
                    WHERE u.cedula = ? AND c.id != ?";
            $params = [$cedula, $excluirId];
        } else {
            $sql = "SELECT c.id FROM coordinadores c 
                    INNER JOIN usuarios u ON c.usuario_id = u.id 
                    WHERE u.cedula = ?";
            $params = [$cedula];
        }
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return (bool)$stmt->fetch();
    }

    public function crearCoordinador(string $nombre, string $apellido, string $cedula, string $telefono, int $aldea_id, string $fecha_inicio, ?string $fecha_fin, ?string $descripcion): bool {
        $sql = "INSERT INTO coordinadores (nombre, apellido, cedula, telefono, aldea_id, fecha_inicio_gestion, fecha_fin_gestion, descripcion) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([$nombre, $apellido, $cedula, $telefono, $aldea_id, $fecha_inicio, $fecha_fin, $descripcion]);
    }

    public function actualizarCoordinador(int $id, string $nombre, string $apellido, string $cedula, string $telefono, int $aldea_id, string $fecha_inicio, ?string $fecha_fin, ?string $descripcion): bool {
        $sql = "UPDATE coordinadores SET nombre = ?, apellido = ?, cedula = ?, telefono = ?, aldea_id = ?, fecha_inicio_gestion = ?, fecha_fin_gestion = ?, descripcion = ? WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([$nombre, $apellido, $cedula, $telefono, $aldea_id, $fecha_inicio, $fecha_fin, $descripcion, $id]);
    }

    public function eliminarCoordinador(int $id): bool {
        $sql = "DELETE FROM coordinadores WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->rowCount() > 0;
    }
}