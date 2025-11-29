<?php
class PnfModel {
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    public function obtenerTodos(): array {
        $sql = "SELECT * FROM pnfs ORDER BY nombre";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerPorId(int $id): ?array {
        $sql = "SELECT * FROM pnfs WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    public function existePnf(string $nombre, string $codigo, int $excluirId = null): bool {
        if ($excluirId) {
            $sql = "SELECT id FROM pnfs WHERE (nombre = ? OR codigo = ?) AND id != ?";
            $params = [$nombre, $codigo, $excluirId];
        } else {
            $sql = "SELECT id FROM pnfs WHERE nombre = ? OR codigo = ?";
            $params = [$nombre, $codigo];
        }
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return (bool)$stmt->fetch();
    }

    public function crearPnf(string $nombre, string $codigo, int $aldea_id, ?string $descripcion): bool {
        $sql = "INSERT INTO pnfs (nombre, codigo, aldea_id, descripcion) VALUES (?, ?, ?, ?)";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([$nombre, $codigo, $aldea_id, $descripcion]);
    }

    public function actualizarPnf(int $id, string $nombre, string $codigo, int $aldea_id, ?string $descripcion): bool {
        $sql = "UPDATE pnfs SET nombre = ?, codigo = ?, aldea_id = ?, descripcion = ? WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([$nombre, $codigo, $aldea_id, $descripcion, $id]);
    }

    public function eliminarPnf(int $id): bool {
        $sql = "DELETE FROM pnfs WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->rowCount() > 0;
    }
}