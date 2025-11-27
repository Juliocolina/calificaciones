<?php
class PeriodosHelper {
    private static $periodos_cache = null;
    
    public static function getPeriodosActivos($pdo) {
        if (self::$periodos_cache === null) {
            $stmt = $pdo->prepare("
                SELECT nombre as codigo, nombre, fecha_inicio, fecha_fin
                FROM trimestres 
                ORDER BY fecha_inicio DESC
            ");
            $stmt->execute();
            self::$periodos_cache = $stmt->fetchAll();
        }
        return self::$periodos_cache;
    }
    
    public static function clearCache() {
        self::$periodos_cache = null;
    }
}