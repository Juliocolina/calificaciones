<?php
// Modelo: AldeaModel.php

class AldeaModel {
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    // Obtener todas las aldeas
    public function obtenerTodas(): array {
        $sql = "SELECT * FROM aldeas ORDER BY nombre";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Obtener aldea por ID
    public function obtenerPorId(int $id): ?array {
        $sql = "SELECT * FROM aldeas WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    // Verificar si existe aldea por nombre o código
    public function existeAldea(string $nombre, string $codigo, int $excluirId = null): bool {
        if ($excluirId) {
            $sql = "SELECT id FROM aldeas WHERE (nombre = ? OR codigo = ?) AND id != ?";
            $params = [$nombre, $codigo, $excluirId];
        } else {
            $sql = "SELECT id FROM aldeas WHERE nombre = ? OR codigo = ?";
            $params = [$nombre, $codigo];
        }
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return (bool)$stmt->fetch();
    }

    // Crear nueva aldea
    public function crearAldea(string $nombre, string $codigo, string $direccion, string $descripcion): bool {
        $sql = "INSERT INTO aldeas (nombre, codigo, direccion, descripcion) VALUES (?, ?, ?, ?)";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([$nombre, $codigo, $direccion, $descripcion]);
    }

    // Actualizar aldea
    public function actualizarAldea(int $id, string $nombre, string $codigo, string $direccion, ?string $descripcion): bool {
        $sql = "UPDATE aldeas SET nombre = ?, codigo = ?, direccion = ?, descripcion = ? WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([$nombre, $codigo, $direccion, $descripcion, $id]);
    }

    // Eliminar aldea
    public function eliminarAldea(int $id): bool {
        $sql = "DELETE FROM aldeas WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->rowCount() > 0;
    }
}