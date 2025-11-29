<?php
class AuthModel {
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    public function obtenerUsuarioPorCedula(string $cedula): ?array {
        $sql = "SELECT id, cedula, clave, rol, activo, nombre, apellido, intentos_fallidos, bloqueado_hasta 
                FROM usuarios WHERE cedula = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$cedula]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    public function estaBloqueado(array $usuario): bool {
        if (!$usuario['bloqueado_hasta']) {
            return false;
        }
        return new DateTime() < new DateTime($usuario['bloqueado_hasta']);
    }

    public function getTiempoRestanteBloqueo(string $bloqueado_hasta): string {
        $tiempo_restante = (new DateTime($bloqueado_hasta))->diff(new DateTime());
        return $tiempo_restante->format('%i minutos %s segundos');
    }

    public function resetearIntentosFallidos(int $usuario_id): bool {
        $sql = "UPDATE usuarios SET intentos_fallidos = 0, bloqueado_hasta = NULL WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([$usuario_id]);
    }

    public function incrementarIntentosFallidos(int $usuario_id): int {
        $sql = "UPDATE usuarios SET intentos_fallidos = intentos_fallidos + 1 WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$usuario_id]);
        
        // Obtener el nuevo número de intentos
        $sql = "SELECT intentos_fallidos FROM usuarios WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$usuario_id]);
        return $stmt->fetchColumn();
    }

    public function bloquearUsuario(int $usuario_id, int $minutos = 15): bool {
        $bloqueo_hasta = date('Y-m-d H:i:s', time() + ($minutos * 60));
        $sql = "UPDATE usuarios SET bloqueado_hasta = ? WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([$bloqueo_hasta, $usuario_id]);
    }

    public function verificarClave(string $clave, string $hash): bool {
        return password_verify($clave, $hash);
    }

    public function estaActivo(array $usuario): bool {
        return $usuario['activo'] == 1;
    }
}