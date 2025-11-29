<?php
class SeccionModel {
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    public function obtenerTodas(): array {
        $sql = "SELECT s.*, oa.tipo_oferta, m.nombre as nombre_materia, 
                       CONCAT(p.nombre, ' ', p.apellido) as nombre_profesor
                FROM secciones s
                LEFT JOIN oferta_academica oa ON s.oferta_academica_id = oa.id
                LEFT JOIN materias m ON s.materia_id = m.id
                LEFT JOIN profesores p ON s.profesor_id = p.id
                ORDER BY s.codigo_seccion";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerPorId(int $id): ?array {
        $sql = "SELECT s.*, oa.tipo_oferta, m.nombre as nombre_materia,
                       CONCAT(p.nombre, ' ', p.apellido) as nombre_profesor
                FROM secciones s
                LEFT JOIN oferta_academica oa ON s.oferta_academica_id = oa.id
                LEFT JOIN materias m ON s.materia_id = m.id
                LEFT JOIN profesores p ON s.profesor_id = p.id
                WHERE s.id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    public function validarOfertaAbierta(int $oferta_id): bool {
        $sql = "SELECT estatus FROM oferta_academica WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$oferta_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result && $result['estatus'] === 'Abierto';
    }

    public function validarMateriaEnOferta(int $materia_id, int $oferta_id): bool {
        $sql = "SELECT m.id FROM materias m 
                JOIN oferta_academica oa ON m.pnf_id = oa.pnf_id 
                WHERE m.id = ? AND oa.id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$materia_id, $oferta_id]);
        return (bool)$stmt->fetch();
    }

    public function existeSeccion(int $oferta_id, int $materia_id, int $profesor_id): bool {
        $sql = "SELECT id FROM secciones WHERE oferta_academica_id = ? AND materia_id = ? AND profesor_id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$oferta_id, $materia_id, $profesor_id]);
        return (bool)$stmt->fetch();
    }

    public function generarCodigoSeccion(int $oferta_id, int $materia_id): string {
        $codigo_base = "SEC-{$oferta_id}-{$materia_id}";
        $contador = 1;
        
        do {
            $codigo_seccion = "{$codigo_base}-{$contador}";
            $sql = "SELECT COUNT(*) FROM secciones WHERE codigo_seccion = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$codigo_seccion]);
            $existe = $stmt->fetchColumn() > 0;
            $contador++;
        } while ($existe);
        
        return $codigo_seccion;
    }

    public function crearSeccion(int $oferta_id, int $materia_id, int $profesor_id, int $cupo_maximo, string $codigo_seccion): bool {
        $sql = "INSERT INTO secciones (oferta_academica_id, materia_id, profesor_id, cupo_maximo, codigo_seccion) 
                VALUES (?, ?, ?, ?, ?)";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([$oferta_id, $materia_id, $profesor_id, $cupo_maximo, $codigo_seccion]);
    }

    public function eliminarSeccion(int $id): bool {
        $sql = "DELETE FROM secciones WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->rowCount() > 0;
    }
}