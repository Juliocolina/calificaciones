<?php
class MateriaModel {
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    public function obtenerTodas(): array {
        $sql = "SELECT m.*, p.nombre as nombre_pnf 
                FROM materias m 
                LEFT JOIN pnfs p ON m.pnf_id = p.id 
                ORDER BY m.nombre";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerPorId(int $id): ?array {
        $sql = "SELECT m.*, p.nombre as nombre_pnf 
                FROM materias m 
                LEFT JOIN pnfs p ON m.pnf_id = p.id 
                WHERE m.id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    public function existeMateria(string $nombre, string $codigo, int $excluirId = null): bool {
        if ($excluirId) {
            $sql = "SELECT id FROM materias WHERE (nombre = ? OR codigo = ?) AND id != ?";
            $params = [$nombre, $codigo, $excluirId];
        } else {
            $sql = "SELECT id FROM materias WHERE nombre = ? OR codigo = ?";
            $params = [$nombre, $codigo];
        }
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return (bool)$stmt->fetch();
    }

    public function crearMateria(string $nombre, string $codigo, int $pnf_id, string $duracion, int $creditos, ?string $descripcion): bool {
        $sql = "INSERT INTO materias (nombre, codigo, pnf_id, duracion, creditos, descripcion) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([$nombre, $codigo, $pnf_id, $duracion, $creditos, $descripcion]);
    }

    public function actualizarMateria(int $id, string $nombre, string $codigo, int $pnf_id, string $duracion, int $creditos, ?string $descripcion): bool {
        $sql = "UPDATE materias SET nombre = ?, codigo = ?, pnf_id = ?, duracion = ?, creditos = ?, descripcion = ? WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([$nombre, $codigo, $pnf_id, $duracion, $creditos, $descripcion, $id]);
    }

    public function eliminarMateria(int $id): bool {
        $sql = "DELETE FROM materias WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->rowCount() > 0;
    }

    public function obtenerPorPnf(int $pnf_id): array {
        $sql = "SELECT * FROM materias WHERE pnf_id = ? ORDER BY nombre";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$pnf_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}