<?php
include $_SERVER['DOCUMENT_ROOT'] . "/estoquemh/cone.php";

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$sql = "SELECT foto FROM usuarios WHERE id = $id LIMIT 1";
$result = mysqli_query($cone, $sql);

if ($result && mysqli_num_rows($result) > 0) {
    $row = mysqli_fetch_assoc($result);

    if (!empty($row['foto'])) {
        header("Content-Type: image/jpeg");
        echo $row['foto'];
        exit;
    }
}

/* Se não tiver foto, retorna padrão */
header("Content-Type: image/png");
readfile("uploads/default.png");
