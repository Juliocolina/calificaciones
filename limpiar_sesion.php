<?php
// Limpiar sesión forzadamente
session_start();
session_unset();
session_destroy();

// Limpiar cookies manualmente
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time()-3600, '/');
}

echo "<h2>Sesión limpiada completamente</h2>";
echo "<p>Ahora puedes <a href='index.php'>iniciar sesión</a> normalmente.</p>";
?>