<?php
class TrayectoModel {
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    public function obtenerTodos(): array {
        $sql = "SELECT * FROM trayectos ORDER BY nombre";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerPorId(int $id): ?array {
        $sql = "SELECT * FROM trayectos WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    public function existeTrayecto(string $slug, int $excluirId = null): bool {
        if ($excluirId) {
            $sql = "SELECT id FROM trayectos WHERE slug = ? AND id != ?";
            $params = [$slug, $excluirId];
        } else {
            $sql = "SELECT id FROM trayectos WHERE slug = ?";
            $params = [$slug];
        }
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return (bool)$stmt->fetch();
    }

    public function crearTrayecto(string $nombre, string $slug, ?string $descripcion): bool {
        $sql = "INSERT INTO trayectos (nombre, slug, descripcion) VALUES (?, ?, ?)";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([$nombre, $slug, $descripcion]);
    }

    public function actualizarTrayecto(int $id, string $nombre, string $slug, ?string $descripcion): bool {
        $sql = "UPDATE trayectos SET nombre = ?, slug = ?, descripcion = ? WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([$nombre, $slug, $descripcion, $id]);
    }

    public function eliminarTrayecto(int $id): bool {
        $sql = "DELETE FROM trayectos WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->rowCount() > 0;
    }
}