<?php
$tituloPagina = $comunicado ? (string) ($comunicado['TituloComunicado'] ?? 'Comunicado') : 'Comunicado no encontrado';
$htmlFinal = $comunicado ? trim((string) ($comunicado['HtmlFinal'] ?? '')) : '';
$htmlFinal = preg_replace(
	'/^<table\b[^>]*width="100%"[^>]*>\s*<tr><td[^>]*>\s*(<table\b.*<\/table>)\s*<\/td><\/tr><\/table>$/is',
	'$1',
	$htmlFinal
);
?>
<!doctype html>
<html lang="es">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title><?php echo htmlspecialchars($tituloPagina, ENT_QUOTES, 'UTF-8'); ?></title>
	<style>
		body {
			margin: 0;
			background: #eef2f6;
			color: #0f172a;
			font-family: Arial, Helvetica, sans-serif;
		}

		.comunicado-preview-page {
			min-height: 100vh;
			padding: 32px 12px;
			box-sizing: border-box;
			display: flex;
			justify-content: center;
			align-items: flex-start;
		}

		#comunicado-selectable {
			display: inline-block;
			max-width: 100%;
		}

		.comunicado-preview-error {
			max-width: 720px;
			margin: 60px auto;
			background: #ffffff;
			border: 1px solid #d9dee3;
			border-radius: 10px;
			padding: 24px;
			text-align: center;
		}
	</style>
</head>
<body>
	<div class="comunicado-preview-page">
		<?php if ($htmlFinal !== ''): ?>
			<div id="comunicado-selectable">
				<?php echo $htmlFinal; ?>
			</div>
		<?php else: ?>
			<div class="comunicado-preview-error">
				<h1>Comunicado no disponible</h1>
				<p>No se encontro contenido HTML para visualizar.</p>
			</div>
		<?php endif; ?>
	</div>
	<script>
		document.addEventListener('keydown', function(event) {
			if (!(event.ctrlKey || event.metaKey) || event.key.toLowerCase() !== 'a') {
				return;
			}
			var target = document.getElementById('comunicado-selectable');
			if (!target) {
				return;
			}
			event.preventDefault();
			var range = document.createRange();
			range.selectNodeContents(target);
			var selection = window.getSelection();
			selection.removeAllRanges();
			selection.addRange(range);
		});
	</script>
</body>
</html>
