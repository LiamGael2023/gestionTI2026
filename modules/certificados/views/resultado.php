<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Resultado</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container mt-5 text-center">
<?php if ($resultado['ok']): ?>
    <div class="alert alert-success">
        ✅ Trámite enviado correctamente<br>
        <b><?= htmlspecialchars($resultado['zip']) ?></b>
    </div>
<?php else: ?>
    <div class="alert alert-danger">
        ❌ Error: <?= htmlspecialchars($resultado['error']) ?>
    </div>
<?php endif; ?>

<a href="index.php" class="btn btn-secondary mt-3">⬅ Volver</a>
</div>

</body>
</html>
