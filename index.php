<?php
// Redirecionador para pages/sistema.php
header('Location: /estoquemh/pages/sistema.php' . ($_SERVER['QUERY_STRING'] ? '?' . $_SERVER['QUERY_STRING'] : ''));
exit;
?>
