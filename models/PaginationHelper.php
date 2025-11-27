<?php
class PaginationHelper {
    public static function paginate($total_records, $records_per_page = 20, $current_page = 1) {
        $total_pages = ceil($total_records / $records_per_page);
        $current_page = max(1, min($current_page, $total_pages));
        $offset = ($current_page - 1) * $records_per_page;
        
        return [
            'total_records' => $total_records,
            'total_pages' => $total_pages,
            'current_page' => $current_page,
            'records_per_page' => $records_per_page,
            'offset' => $offset,
            'has_prev' => $current_page > 1,
            'has_next' => $current_page < $total_pages
        ];
    }
    
    public static function renderPagination($pagination, $base_url) {
        if ($pagination['total_pages'] <= 1) return '';
        
        // Determinar el separador correcto para agregar page
        $separator = (strpos($base_url, '?') !== false) ? '&' : '?';
        
        $html = '<nav><ul class="pagination justify-content-center">';
        
        // Anterior
        if ($pagination['has_prev']) {
            $prev_page = $pagination['current_page'] - 1;
            $html .= "<li class='page-item'><a class='page-link' href='{$base_url}{$separator}page={$prev_page}'>Anterior</a></li>";
        }
        
        // Páginas
        $start = max(1, $pagination['current_page'] - 2);
        $end = min($pagination['total_pages'], $pagination['current_page'] + 2);
        
        for ($i = $start; $i <= $end; $i++) {
            $active = ($i == $pagination['current_page']) ? 'active' : '';
            $html .= "<li class='page-item {$active}'><a class='page-link' href='{$base_url}{$separator}page={$i}'>{$i}</a></li>";
        }
        
        // Siguiente
        if ($pagination['has_next']) {
            $next_page = $pagination['current_page'] + 1;
            $html .= "<li class='page-item'><a class='page-link' href='{$base_url}{$separator}page={$next_page}'>Siguiente</a></li>";
        }
        
        $html .= '</ul></nav>';
        return $html;
    }
}