<?php
// Redireccionar de la raíz (/) a web/index.php con todos los parámetros
header("Location: web/index.php" . ($_SERVER['QUERY_STRING'] ? '?' . $_SERVER['QUERY_STRING'] : ''));
exit;
