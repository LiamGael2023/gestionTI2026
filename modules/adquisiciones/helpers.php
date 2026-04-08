<?php

if (!function_exists('adqEnviarHeaderSeguro')) {
	// Envía un header HTTP solo si aún no se han enviado encabezados
	function adqEnviarHeaderSeguro($header)
	{
		if (!headers_sent()) {
			header($header);
		}
	}
}

if (!function_exists('adqRedirigirSeguro')) {
	// Redirige de forma segura usando header o fallback con JavaScript si ya se enviaron headers
	function adqRedirigirSeguro($url)
	{
		if (!headers_sent()) {
			header('Location: ' . $url);
			exit;
		}

		echo '<script>window.location.href=' . json_encode($url) . ';</script>';
		exit;
	}
}

if (!function_exists('adqNormalizarCodigoMeta')) {
	// Limpia y normaliza un código meta (mayúsculas, sin caracteres inválidos y máximo 4 caracteres)
	function adqNormalizarCodigoMeta($codigoMetaRaw)
	{
		$codigoMeta = strtoupper(trim((string) $codigoMetaRaw));
		if ($codigoMeta === '') {
			return null;
		}

		$codigoMeta = preg_replace('/[^A-Z0-9]/', '', $codigoMeta);
		if ($codigoMeta === '') {
			return null;
		}

		return substr($codigoMeta, 0, 4);
	}
}