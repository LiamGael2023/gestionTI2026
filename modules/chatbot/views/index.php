<?php
/**
 * modules/chatbot/views/index.php
 * Dashboard del módulo ChaviBot — schema correcto: chaviBot
 */

if (!isset($_SESSION['usuario_id'])) {
    header('Location: ' . (defined('BASE_URL') ? BASE_URL : '') . '/login');
    exit;
}

$conn          = Conexion::conectar();
$nombreUsuario = htmlspecialchars($_SESSION['usuario_nombre'] ?? 'Usuario');
$rolUsuario    = strtolower($_SESSION['usuario_rol'] ?? 'usuario');
$esAdmin       = in_array($rolUsuario, ['admin', 'tecnico', 'administrador']);

// ── KPIs desde schema chaviBot (correcto) ─────────────────────────────────
function cbVal($conn, $sql) {
    if (!$conn) return 0;
    $stmt = sqlsrv_query($conn, $sql);
    if (!$stmt) return 0;
    $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_NUMERIC);
    sqlsrv_free_stmt($stmt);
    return $row ? intval($row[0]) : 0;
}
function cbRows($conn, $sql) {
    if (!$conn) return [];
    $stmt = sqlsrv_query($conn, $sql);
    if (!$stmt) return [];
    $rows = [];
    while ($r = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) $rows[] = $r;
    sqlsrv_free_stmt($stmt);
    return $rows;
}

$totalEjemplos  = cbVal($conn, "SELECT COUNT(*) FROM [chaviBot].[RAG] WHERE activo=1");
$totalConsultas = cbVal($conn, "SELECT COUNT(*) FROM [chaviBot].[Historial]");
$consultasHoy   = cbVal($conn, "SELECT COUNT(*) FROM [chaviBot].[Historial] WHERE CAST(fechaCreacion AS DATE)=CAST(GETDATE() AS DATE)");
$usuariosActivos= cbVal($conn, "SELECT COUNT(DISTINCT idUsuario) FROM [chaviBot].[Historial] WHERE fechaCreacion>=DATEADD(day,-7,GETDATE()) AND idUsuario IS NOT NULL");

$ultimasConsultas = cbRows($conn, "
    SELECT TOP 8 h.pregunta, FORMAT(h.fechaCreacion,'dd/MM HH:mm') AS fecha,
                 h.nombres AS usuario, h.canal
    FROM [chaviBot].[Historial] h
    ORDER BY h.fechaCreacion DESC
");

$porCanal = cbRows($conn, "
    SELECT canal, COUNT(*) AS total FROM [chaviBot].[RAG] GROUP BY canal
");

if ($conn) sqlsrv_close($conn);

// ── URL del chat ──────────────────────────────────────────────────────────
$urlChat = (defined('BASE_URL') ? BASE_URL : '') . '/chatbot/chavibot';
?>
<style>
.cb-dash{background:#f1f5f9;min-height:70vh;padding:1.5rem 2rem}
.cb-kpi-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(190px,1fr));gap:1rem;margin-bottom:1.5rem}
.cb-kpi{background:#fff;border-radius:12px;padding:1.2rem 1.4rem;border:1px solid #e2e8f0;display:flex;align-items:center;gap:1rem;box-shadow:0 2px 8px rgba(0,0,0,.04)}
.cb-kpi-icon{width:46px;height:46px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:1.3rem;flex-shrink:0}
.cb-kpi-num{font-size:1.9rem;font-weight:800;color:#1e293b;line-height:1}
.cb-kpi-lbl{font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#94a3b8;margin-top:.1rem}
.cb-grid2{display:grid;grid-template-columns:1fr 1fr;gap:1rem;margin-bottom:1.5rem}
.cb-card{background:#fff;border-radius:12px;border:1px solid #e2e8f0;overflow:hidden;box-shadow:0 2px 6px rgba(0,0,0,.04)}
.cb-card-hd{padding:.8rem 1.2rem;border-bottom:1px solid #f1f5f9;background:#f8fafc;font-size:.75rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#475569;display:flex;align-items:center;gap:.5rem}
.cb-card-bd{padding:1rem 1.2rem}
.cb-table{width:100%;border-collapse:collapse;font-size:.82rem}
.cb-table th{padding:.4rem .7rem;text-align:left;font-size:.68rem;font-weight:700;text-transform:uppercase;color:#94a3b8;border-bottom:1px solid #f1f5f9}
.cb-table td{padding:.5rem .7rem;border-bottom:1px solid #f8fafc}
.cb-table tr:last-child td{border:0}
.cb-badge{display:inline-block;padding:.15rem .5rem;border-radius:20px;font-size:.68rem;font-weight:700}
.cb-badge-web{background:#e0e7ff;color:#4338ca}
.cb-badge-whatsapp{background:#dcfce7;color:#16a34a}
.cb-badge-ambos{background:#fef3c7;color:#d97706}
.cb-bar-wrap{flex:1;background:#f1f5f9;border-radius:99px;height:7px;overflow:hidden}
.cb-bar{height:100%;border-radius:99px}
.cb-hero{background:linear-gradient(135deg,#1e40af,#7c3aed);border-radius:14px;padding:1.4rem 1.8rem;color:#fff;display:flex;align-items:center;justify-content:space-between;gap:1rem;margin-bottom:1.5rem;flex-wrap:wrap}
.cb-hero h3{margin:0 0 .3rem;font-size:1.1rem;font-weight:800}
.cb-hero p{margin:0;opacity:.8;font-size:.83rem}
.cb-hero-btn{background:#fff;color:#4338ca;border:none;padding:.6rem 1.4rem;border-radius:8px;font-weight:700;font-size:.88rem;text-decoration:none;white-space:nowrap;cursor:pointer}
.cb-hero-btn:hover{background:#e0e7ff;color:#4338ca}
@media(max-width:800px){.cb-grid2{grid-template-columns:1fr}.cb-dash{padding:1rem}}
</style>

<div class="cb-dash">

    <!-- Hero -->
    <div class="cb-hero">
        <div>
            <h3>🤖 ¡Hola, <?= htmlspecialchars(explode(' ', $nombreUsuario)[0]) ?>!</h3>
            <p>Consulta el sistema de gestión TI en lenguaje natural. ChaviBot responde al instante.</p>
        </div>
        <a href="<?= $urlChat ?>" class="cb-hero-btn">💬 Abrir ChaviBot</a>
    </div>

    <!-- KPIs -->
    <div class="cb-kpi-grid">
        <div class="cb-kpi">
            <div class="cb-kpi-icon" style="background:#ede9fe;color:#7c3aed">🧠</div>
            <div><div class="cb-kpi-num"><?= $totalEjemplos ?></div><div class="cb-kpi-lbl">Ejemplos RAG</div></div>
        </div>
        <div class="cb-kpi">
            <div class="cb-kpi-icon" style="background:#e0f2fe;color:#0369a1">💬</div>
            <div><div class="cb-kpi-num"><?= number_format($totalConsultas) ?></div><div class="cb-kpi-lbl">Consultas totales</div></div>
        </div>
        <div class="cb-kpi">
            <div class="cb-kpi-icon" style="background:#dcfce7;color:#16a34a">📅</div>
            <div><div class="cb-kpi-num"><?= $consultasHoy ?></div><div class="cb-kpi-lbl">Consultas hoy</div></div>
        </div>
        <div class="cb-kpi">
            <div class="cb-kpi-icon" style="background:#fef3c7;color:#d97706">👥</div>
            <div><div class="cb-kpi-num"><?= $usuariosActivos ?></div><div class="cb-kpi-lbl">Usuarios activos (7d)</div></div>
        </div>
    </div>

    <!-- Grid -->
    <div class="cb-grid2">

        <!-- Últimas consultas -->
        <div class="cb-card">
            <div class="cb-card-hd">💬 Últimas Consultas</div>
            <div class="cb-card-bd" style="padding:0">
                <?php if (empty($ultimasConsultas)): ?>
                <div style="padding:1.5rem;text-align:center;color:#94a3b8;font-size:.84rem">Sin consultas aún</div>
                <?php else: ?>
                <table class="cb-table">
                    <thead><tr><th>Pregunta</th><th>Usuario</th><th>Canal</th><th>Fecha</th></tr></thead>
                    <tbody>
                    <?php foreach ($ultimasConsultas as $c): ?>
                    <tr>
                        <td style="max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"
                            title="<?= htmlspecialchars($c['pregunta']) ?>">
                            <?= htmlspecialchars(mb_strimwidth($c['pregunta'], 0, 50, '…')) ?>
                        </td>
                        <td style="font-size:.76rem;color:#64748b"><?= htmlspecialchars($c['usuario'] ?? '—') ?></td>
                        <td>
                            <?php $cn = strtolower($c['canal'] ?? 'web'); ?>
                            <span class="cb-badge cb-badge-<?= $cn ?>"><?= $cn ?></span>
                        </td>
                        <td style="font-size:.72rem;color:#94a3b8;white-space:nowrap"><?= $c['fecha'] ?></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>
        </div>

        <!-- Canales -->
        <div class="cb-card">
            <div class="cb-card-hd">📊 Ejemplos por Canal</div>
            <div class="cb-card-bd">
                <?php
                $tot = max(1, array_sum(array_column($porCanal, 'total')));
                $colores = ['web'=>'#4338ca','whatsapp'=>'#16a34a','ambos'=>'#d97706'];
                ?>
                <?php if (empty($porCanal)): ?>
                <p style="color:#94a3b8;font-size:.84rem">Sin ejemplos entrenados</p>
                <?php else: ?>
                <?php foreach ($porCanal as $c): ?>
                <?php $pct = round($c['total']/$tot*100); $col=$colores[strtolower($c['canal'])]??'#64748b'; ?>
                <div style="display:flex;align-items:center;gap:.75rem;margin-bottom:.8rem">
                    <span style="font-size:.8rem;font-weight:600;min-width:80px"><?= ucfirst($c['canal']) ?></span>
                    <div class="cb-bar-wrap"><div class="cb-bar" style="width:<?= $pct ?>%;background:<?= $col ?>"></div></div>
                    <span style="font-size:.8rem;font-weight:700;min-width:24px;text-align:right"><?= $c['total'] ?></span>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>

                <?php if ($esAdmin): ?>
                <hr style="margin:1rem 0;border-color:#f1f5f9">
                <a href="<?= $urlChat ?>" style="display:inline-flex;align-items:center;gap:.4rem;padding:.4rem .9rem;background:#ede9fe;color:#7c3aed;border-radius:8px;font-size:.82rem;font-weight:600;text-decoration:none;margin-right:.5rem">
                    🤖 Abrir Chat
                </a>
                <?php endif; ?>
            </div>
        </div>

    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded',()=>{
    document.querySelectorAll('.cb-bar').forEach(b=>{
        const w=b.style.width; b.style.width='0';
        setTimeout(()=>b.style.width=w,300);
    });
});
</script>
