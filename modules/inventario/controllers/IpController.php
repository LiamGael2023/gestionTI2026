<?php
require_once __DIR__ . "/../models/IpModel.php";

class IpController
{
    /* ────────────────────────────────────────────
       SERVER-SIDE PROCESSING para DataTables
    ──────────────────────────────────────────── */
    static public function ctrServerSide(
        int    $start,
        int    $length,
        string $search,
        string $orderField,
        string $orderDir
    ) {
        return IpModel::mdlServerSide($start, $length, $search, $orderField, $orderDir);
    }

    /* ────────────────────────────────────────────
       IP INDIVIDUAL
    ──────────────────────────────────────────── */
    static public function ctrCrearIp()
    {
        if (!isset($_POST["nuevaIpAddress"])) return null;

        if (!filter_var(trim($_POST["nuevaIpAddress"]), FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return ["resultado" => "error", "mensaje" => "La dirección IP no tiene un formato IPv4 válido."];
        }

        $datos = [
            "ipAddress"   => trim($_POST["nuevaIpAddress"]),
            "mascara"     => trim($_POST["nuevaMascara"]      ?? ''),
            "cidrOrigen"  => null,
            "idUbicacion" => !empty($_POST["nuevoIdUbicacionIp"])
                                ? intval($_POST["nuevoIdUbicacionIp"]) : null,
            "estado"      => self::validarEstado($_POST["nuevoEstadoIp"] ?? ''),
            "idUsuario"   => $_SESSION["usuario_id"],
        ];

        return IpModel::mdlCrearIp($datos);
    }

    /* ────────────────────────────────────────────
       RANGO CIDR
    ──────────────────────────────────────────── */
    static public function ctrCrearRangoCidr()
    {
        if (!isset($_POST["nuevoCidr"])) return null;

        $cidr = trim($_POST["nuevoCidr"]);

        if (!preg_match('/^(\d{1,3}\.){3}\d{1,3}\/\d{1,2}$/', $cidr)) {
            return ["resultado" => "error", "mensaje" => "El formato CIDR no es válido. Ejemplo: 192.168.1.0/24"];
        }

        $rango = self::cidrToRangePublic($cidr);
        if (!$rango) {
            return ["resultado" => "error", "mensaje" => "Rango CIDR no válido. Use prefijos entre /8 y /30."];
        }
        if ($rango['total'] > 1022) {
            return ["resultado" => "error", "mensaje" => "El rango supera 1,022 IPs. Use prefijo /22 o mayor."];
        }

        $idUbicacion = !empty($_POST["nuevoIdUbicacionCidr"]) ? intval($_POST["nuevoIdUbicacionCidr"]) : null;
        if (!$idUbicacion) {
            return ["resultado" => "error", "mensaje" => "Debe seleccionar una ubicación."];
        }

        return IpModel::mdlCrearRangoCidr(
            $rango['hosts'],
            $rango['mascara'],
            $cidr,
            $idUbicacion,
            self::validarEstado($_POST["nuevoEstadoCidr"] ?? ''),
            $_SESSION["usuario_id"]
        );
    }

    /* ────────────────────────────────────────────
       VERIFICAR DUPLICADOS (preview)
    ──────────────────────────────────────────── */
    static public function ctrVerificarDuplicadosCidr(array $hosts)
    {
        return IpModel::mdlVerificarDuplicadosCidr($hosts);
    }

    /* ────────────────────────────────────────────
       EDITAR
    ──────────────────────────────────────────── */
    static public function ctrEditarIp()
    {
        if (!isset($_POST["editarIpAddress"])) return null;

        if (empty($_POST["editarIdIp"])) {
            return ["resultado" => "error", "mensaje" => "ID de IP no recibido."];
        }
        if (!filter_var(trim($_POST["editarIpAddress"]), FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return ["resultado" => "error", "mensaje" => "La dirección IP no tiene un formato IPv4 válido."];
        }

        $datos = [
            "idIp"        => intval($_POST["editarIdIp"]),
            "ipAddress"   => trim($_POST["editarIpAddress"]),
            "mascara"     => trim($_POST["editarMascara"]         ?? ''),
            "idUbicacion" => !empty($_POST["editarIdUbicacionIp"])
                                ? intval($_POST["editarIdUbicacionIp"]) : null,
            "estado"      => self::validarEstado($_POST["editarEstadoIp"] ?? ''),
            "idUsuario"   => $_SESSION["usuario_id"],
        ];

        return IpModel::mdlEditarIp($datos);
    }

    /* ────────────────────────────────────────────
       MOSTRAR (por ID para modal editar)
    ──────────────────────────────────────────── */
    static public function ctrMostrarIp($item, $valor)
    {
        return IpModel::mdlMostrarIp($item, $valor);
    }

    /* ────────────────────────────────────────────
       HELPER PÚBLICO: CIDR → array de hosts
    ──────────────────────────────────────────── */
    static public function cidrToRangePublic($cidr)
    {
        return self::cidrToRange($cidr);
    }

    /* ────────────────────────────────────────────
       HELPERS PRIVADOS
    ──────────────────────────────────────────── */
    private static function validarEstado($estado)
    {
        return in_array($estado, ['disponible', 'asignada', 'reservada']) ? $estado : 'disponible';
    }

    private static function cidrToRange($cidr)
    {
        if (!preg_match('/^(\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3})\/(\d{1,2})$/', $cidr, $m)) return null;
        $prefix = intval($m[2]);
        if ($prefix < 8 || $prefix > 30) return null;
        if (!filter_var($m[1], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) return null;

        $ipLong    = ip2long($m[1]);
        $mask      = ~0 << (32 - $prefix);
        $netLong   = $ipLong & $mask;
        $broadLong = $netLong | (~$mask & 0xFFFFFFFF);

        $hosts = [];
        for ($i = $netLong + 1; $i < $broadLong; $i++) {
            $hosts[] = long2ip($i);
        }

        return [
            'mascara' => long2ip($mask & 0xFFFFFFFF),
            'hosts'   => $hosts,
            'total'   => count($hosts),
        ];
    }
}
