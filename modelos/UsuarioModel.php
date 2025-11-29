<?php
class UsuarioModelSimple {
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    public function obtenerTodos(): array {
        $sql = "SELECT * FROM usuarios ORDER BY cedula";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerPorId(int $id): ?array {
        $sql = "SELECT * FROM usuarios WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    public function existeUsuario(string $cedula, int $excluirId = null): bool {
        if ($excluirId) {
            $sql = "SELECT id FROM usuarios WHERE cedula = ? AND id != ?";
            $params = [$cedula, $excluirId];
        } else {
            $sql = "SELECT id FROM usuarios WHERE cedula = ?";
            $params = [$cedula];
        }
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return (bool)$stmt->fetch();
    }

    public function crearUsuario(string $cedula, string $rol, string $clave): bool {
        $sql = "INSERT INTO usuarios (cedula, rol, clave, activo) VALUES (?, ?, ?, 1)";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([$cedula, $rol, $clave]);
    }

    public function actualizarUsuario(int $id, string $cedula, string $rol): bool {
        $sql = "UPDATE usuarios SET cedula = ?, rol = ? WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([$cedula, $rol, $id]);
    }

    public function actualizarClave(int $id, string $clave): bool {
        $sql = "UPDATE usuarios SET clave = ? WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([$clave, $id]);
    }

    public function eliminarUsuario(int $id): bool {
        $sql = "DELETE FROM usuarios WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->rowCount() > 0;
    }
}