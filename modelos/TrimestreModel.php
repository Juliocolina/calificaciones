<?php
class TrimestreModel {
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    public function obtenerTodos(): array {
        $sql = "SELECT * FROM trimestres ORDER BY fecha_inicio DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerPorId(int $id): ?array {
        $sql = "SELECT * FROM trimestres WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    public function existeTrimestre(string $nombre, int $excluirId = null): bool {
        if ($excluirId) {
            $sql = "SELECT id FROM trimestres WHERE nombre = ? AND id != ?";
            $params = [$nombre, $excluirId];
        } else {
            $sql = "SELECT id FROM trimestres WHERE nombre = ?";
            $params = [$nombre];
        }
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return (bool)$stmt->fetch();
    }

    public function crearTrimestre(string $nombre, string $fecha_inicio, string $fecha_fin, ?string $descripcion): bool {
        $sql = "INSERT INTO trimestres (nombre, fecha_inicio, fecha_fin, descripcion) VALUES (?, ?, ?, ?)";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([$nombre, $fecha_inicio, $fecha_fin, $descripcion]);
    }

    public function actualizarTrimestre(int $id, string $nombre, string $fecha_inicio, string $fecha_fin, ?string $descripcion): bool {
        $sql = "UPDATE trimestres SET nombre = ?, fecha_inicio = ?, fecha_fin = ?, descripcion = ? WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([$nombre, $fecha_inicio, $fecha_fin, $descripcion, $id]);
    }

    public function eliminarTrimestre(int $id): bool {
        $sql = "DELETE FROM trimestres WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->rowCount() > 0;
    }
}