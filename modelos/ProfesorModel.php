<?php
class ProfesorModel {
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    public function obtenerTodos(): array {
        $sql = "SELECT p.*, a.nombre as nombre_aldea FROM profesores p 
                LEFT JOIN aldeas a ON p.aldea_id = a.id 
                ORDER BY p.nombre";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerPorId(int $id): ?array {
        $sql = "SELECT p.*, a.nombre as nombre_aldea FROM profesores p 
                LEFT JOIN aldeas a ON p.aldea_id = a.id 
                WHERE p.id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    public function existeProfesor(string $cedula, int $excluirId = null): bool {
        if ($excluirId) {
            $sql = "SELECT id FROM profesores WHERE cedula = ? AND id != ?";
            $params = [$cedula, $excluirId];
        } else {
            $sql = "SELECT id FROM profesores WHERE cedula = ?";
            $params = [$cedula];
        }
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return (bool)$stmt->fetch();
    }

    public function crearProfesor(string $nombre, string $apellido, string $cedula, string $telefono, int $aldea_id, string $especialidad): bool {
        $sql = "INSERT INTO profesores (nombre, apellido, cedula, telefono, aldea_id, especialidad) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([$nombre, $apellido, $cedula, $telefono, $aldea_id, $especialidad]);
    }

    public function actualizarProfesor(int $id, string $nombre, string $apellido, string $cedula, string $telefono, int $aldea_id, string $especialidad): bool {
        $sql = "UPDATE profesores SET nombre = ?, apellido = ?, cedula = ?, telefono = ?, aldea_id = ?, especialidad = ? WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([$nombre, $apellido, $cedula, $telefono, $aldea_id, $especialidad, $id]);
    }

    public function eliminarProfesor(int $id): bool {
        $sql = "DELETE FROM profesores WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->rowCount() > 0;
    }

    public function asignarMateria(int $profesor_id, int $materia_id): bool {
        $sql = "INSERT INTO materia_profesor (profesor_id, materia_id) VALUES (?, ?)";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([$profesor_id, $materia_id]);
    }

    public function quitarMateria(int $profesor_id, int $materia_id): bool {
        $sql = "DELETE FROM materia_profesor WHERE profesor_id = ? AND materia_id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$profesor_id, $materia_id]);
        return $stmt->rowCount() > 0;
    }
}