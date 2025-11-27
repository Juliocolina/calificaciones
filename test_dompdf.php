<?php
require_once __DIR__ . '/vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

// Configurar opciones
$options = new Options();
$options->set('defaultFont', 'Arial');
$options->set('isRemoteEnabled', true);

// Crear instancia de Dompdf
$dompdf = new Dompdf($options);

// HTML de prueba
$html = '
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Prueba DomPDF</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .header { text-align: center; color: #2a5298; margin-bottom: 30px; }
        .info { background: #f8f9fa; padding: 15px; border-radius: 5px; }
        .success { color: green; font-weight: bold; }
    </style>
</head>
<body>
    <div class="header">
        <h1> DomPDF Funcionando Correctamente</h1>
        <p>Sistema Misión Sucre - Prueba de PDF</p>
    </div>
    
    <div class="info">
        <h3>Información del Sistema:</h3>
        <p><strong>Fecha:</strong> ' . date('d/m/Y H:i:s') . '</p>
        <p><strong>Versión PHP:</strong> ' . PHP_VERSION . '</p>
        <p><strong>DomPDF:</strong> Instalado y funcionando</p>
        <p class="success">El sistema está listo para generar reportes PDF</p>
    </div>
    
    <hr>
    <p><em>Este es un archivo de prueba. Si puedes ver este PDF, DomPDF está funcionando correctamente.</em></p>
</body>
</html>';

// Cargar HTML
$dompdf->loadHtml($html);

// Configurar tamaño de página
$dompdf->setPaper('A4', 'portrait');

// Renderizar PDF
$dompdf->render();

// Enviar al navegador
$dompdf->stream('prueba_dompdf.pdf', ['Attachment' => false]);
?>