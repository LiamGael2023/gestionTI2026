<?php
/**
 * modules/chatbot/views/index.php
 * Dashboard ChaviBot — mismo estilo que el dashboard de Inventario TI
 */

if (!isset($_SESSION['usuario_id'])) {
    header('Location: ' . (defined('BASE_URL') ? BASE_URL : '') . '/login');
    exit;
}

$conn          = Conexion::conectar();
$nombreUsuario = htmlspecialchars($_SESSION['usuario_nombre'] ?? 'Usuario');
$rolUsuario    = strtolower($_SESSION['usuario_rol'] ?? 'usuario');
$esAdmin       = in_array($rolUsuario, ['admin', 'tecnico', 'administrador']);

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

$totalEjemplos   = cbVal($conn, "SELECT COUNT(*) FROM [chaviBot].[RAG] WHERE activo=1");
$totalConsultas  = cbVal($conn, "SELECT COUNT(*) FROM [chaviBot].[Historial]");
$consultasHoy    = cbVal($conn, "SELECT COUNT(*) FROM [chaviBot].[Historial] WHERE CAST(fechaCreacion AS DATE)=CAST(GETDATE() AS DATE)");
$usuariosActivos = cbVal($conn, "SELECT COUNT(DISTINCT idUsuario) FROM [chaviBot].[Historial] WHERE fechaCreacion>=DATEADD(day,-7,GETDATE()) AND idUsuario IS NOT NULL");
$consultasWeb    = cbVal($conn, "SELECT COUNT(*) FROM [chaviBot].[Historial] WHERE canal='web'");
$consultasWA     = cbVal($conn, "SELECT COUNT(*) FROM [chaviBot].[Historial] WHERE canal='whatsapp'");

// Top módulos más consultados (por palabrasClave/schemaObjetivo)
$topModulos = cbRows($conn, "
    SELECT TOP 5 schemaConsultado AS modulo, COUNT(*) AS total
    FROM [chaviBot].[Historial]
    WHERE schemaConsultado IS NOT NULL AND schemaConsultado <> ''
    GROUP BY schemaConsultado ORDER BY total DESC
");

// Últimas consultas
$ultimasConsultas = cbRows($conn, "
    SELECT TOP 8 h.pregunta, FORMAT(h.fechaCreacion,'dd/MM HH:mm') AS fecha,
           h.nombres AS usuario, h.canal, h.tiempoMs
    FROM [chaviBot].[Historial] h ORDER BY h.fechaCreacion DESC
");

// Evolución últimos 7 días
$porDia = cbRows($conn, "
    SELECT FORMAT(fechaCreacion,'dd/MM') AS dia, COUNT(*) AS total
    FROM [chaviBot].[Historial]
    WHERE fechaCreacion >= DATEADD(day,-6,CAST(GETDATE() AS DATE))
    GROUP BY FORMAT(fechaCreacion,'dd/MM'), CAST(fechaCreacion AS DATE)
    ORDER BY CAST(fechaCreacion AS DATE) ASC
");

// RAG por canal
$ragPorCanal = cbRows($conn, "SELECT canal, COUNT(*) AS total FROM [chaviBot].[RAG] GROUP BY canal");

if ($conn) sqlsrv_close($conn);

// URL del chat
$urlChat = (defined('BASE_URL') ? BASE_URL : '') . '/chatbot/chavibot';
?>

<style>
/* ── Dashboard ChaviBot (mismo estilo que Inventario TI) ── */
.dash-kpi {
    border-radius: 12px;
    border: 1px solid var(--tblr-border-color);
    background: #fff;
    padding: 1.25rem 1.5rem;
    display: flex;
    align-items: flex-start;
    gap: 1rem;
    transition: box-shadow .2s, transform .2s;
    height: 100%;
}
.dash-kpi:hover { box-shadow: 0 8px 32px rgba(0,0,0,.08); transform: translateY(-2px); }
.dash-kpi-icon {
    width: 48px; height: 48px;
    border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.4rem; flex-shrink: 0;
}
.dash-kpi-val   { font-size: 1.9rem; font-weight: 800; line-height: 1; color: #1e293b; }
.dash-kpi-label { font-size: .78rem; font-weight: 700; text-transform: uppercase; letter-spacing: .06em; color: #94a3b8; margin-top: .2rem; }
.dash-kpi-sub   { font-size: .75rem; color: #64748b; margin-top: .3rem; }

.dash-progress { height: 6px; border-radius: 99px; background: #e2e8f0; overflow: hidden; margin-top: .5rem; }
.dash-progress-bar { height: 100%; border-radius: 99px; transition: width 1s ease; }

.dash-card { border-radius: 12px; border: 1px solid var(--tblr-border-color); background: #fff; overflow: hidden; }
.dash-card-header {
    display: flex; align-items: center; gap: .5rem;
    padding: .85rem 1.25rem;
    border-bottom: 1px solid var(--tblr-border-color);
    background: #f8fafc;
}
.dash-card-header-title { font-size: .82rem; font-weight: 700; text-transform: uppercase; letter-spacing: .06em; color: #475569; }
.dash-card-body { padding: 1.1rem 1.25rem; }

.timeline-item { display: flex; gap: .75rem; padding: .55rem 0; border-bottom: 1px solid #f1f5f9; }
.timeline-item:last-child { border-bottom: none; }
.timeline-dot { width: 8px; height: 8px; border-radius: 50%; background: #2563eb; flex-shrink: 0; margin-top: .35rem; }

.card-modern:hover { transform: translateY(-5px); box-shadow: 0 15px 40px rgba(0,0,0,.08) !important; }
.card-modern:hover .ti-arrow-right { color: var(--tblr-primary) !important; transform: translateX(4px); }

@keyframes fadeInUp { from { opacity:0; transform:translateY(16px); } to { opacity:1; transform:translateY(0); } }
.dash-animate { animation: fadeInUp .45s ease both; }
.dash-animate:nth-child(1){animation-delay:.05s} .dash-animate:nth-child(2){animation-delay:.10s}
.dash-animate:nth-child(3){animation-delay:.15s} .dash-animate:nth-child(4){animation-delay:.20s}
.dash-animate:nth-child(5){animation-delay:.25s} .dash-animate:nth-child(6){animation-delay:.30s}
</style>

<div class="page-header d-print-none" style="padding-bottom:0">
  <div class="container-xl">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
      <div>
        <h2 class="fw-bold mb-1" style="color:#1e293b">
          <i class="ti ti-robot me-2 text-primary"></i>Dashboard — ChaviBot
        </h2>
        <div class="text-muted small">Asistente de IA · Consultas, entrenamiento y estadísticas</div>
      </div>
      <div class="text-muted small"><i class="ti ti-refresh me-1"></i>Actualizado: <?= date('d/m/Y H:i') ?></div>
    </div>
  </div>
</div>

<div class="page-body">
<div class="container-xl">

<!-- ── ACCESOS RÁPIDOS ───────────────────────────────────────────────── -->
<div class="row g-4 mb-4">

  <div class="col-12 col-md-6 col-lg-3">
    <a href="<?= $urlChat ?>" style="text-decoration:none">
      <div class="card shadow-sm card-modern h-100" style="border-radius:16px;transition:all .2s ease;border:1px solid var(--tblr-border-color)">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-start mb-3">
            <div style="width:50px;height:50px;border-radius:12px;display:flex;align-items:center;justify-content:center" class="bg-primary-lt text-primary">
              <i class="ti ti-messages" style="font-size:24px"></i>
            </div>
            <div class="h2 mb-0 fw-bold text-dark"><?= number_format($totalConsultas) ?></div>
          </div>
          <div class="fw-semibold text-dark">Abrir ChaviBot</div>
          <div class="text-muted small mb-3">Consulta el sistema en lenguaje natural.</div>
          <hr class="my-2">
          <div class="d-flex justify-content-between align-items-center">
            <span class="badge bg-success-lt text-success" style="font-size:.65rem"><?= $consultasHoy ?> consultas hoy</span>
            <i class="ti ti-arrow-right text-muted" style="font-size:20px;transition:all .2s"></i>
          </div>
        </div>
      </div>
    </a>
  </div>

  <?php if ($esAdmin): ?>
  <div class="col-12 col-md-6 col-lg-3">
    <div class="card shadow-sm card-modern h-100" style="border-radius:16px;cursor:pointer;transition:all .2s ease;border:1px solid var(--tblr-border-color)"
         onclick="window.location='<?= $urlChat ?>'; setTimeout(()=>document.getElementById('cb-btn-train')?.click(),800)">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-start mb-3">
          <div style="width:50px;height:50px;border-radius:12px;display:flex;align-items:center;justify-content:center" class="bg-purple-lt text-purple">
            <i class="ti ti-brain" style="font-size:24px"></i>
          </div>
          <div class="h2 mb-0 fw-bold text-dark"><?= $totalEjemplos ?></div>
        </div>
        <div class="fw-semibold text-dark">Entrenar Bot</div>
        <div class="text-muted small mb-3">Agregar ejemplos para mejorar las respuestas.</div>
        <hr class="my-2">
        <div class="d-flex justify-content-between align-items-center">
          <span class="text-uppercase text-muted small fw-semibold">Ejemplos activos</span>
          <i class="ti ti-arrow-right text-muted" style="font-size:20px"></i>
        </div>
      </div>
    </div>
  </div>
  <?php endif; ?>

  <div class="col-12 col-md-6 col-lg-3">
    <div class="card shadow-sm h-100" style="border-radius:16px;border:1px solid var(--tblr-border-color)">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-start mb-3">
          <div style="width:50px;height:50px;border-radius:12px;display:flex;align-items:center;justify-content:center" class="bg-azure-lt text-azure">
            <i class="ti ti-globe" style="font-size:24px"></i>
          </div>
          <div class="h2 mb-0 fw-bold text-dark"><?= number_format($consultasWeb) ?></div>
        </div>
        <div class="fw-semibold text-dark">Web</div>
        <div class="text-muted small mb-3">Consultas realizadas desde el navegador.</div>
        <hr class="my-2">
        <div class="d-flex gap-2">
          <span class="badge bg-azure-lt text-azure" style="font-size:.65rem">Web</span>
        </div>
      </div>
    </div>
  </div>

  <div class="col-12 col-md-6 col-lg-3">
    <div class="card shadow-sm h-100" style="border-radius:16px;border:1px solid var(--tblr-border-color)">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-start mb-3">
          <div style="width:50px;height:50px;border-radius:12px;display:flex;align-items:center;justify-content:center" class="bg-green-lt text-green">
            <i class="ti ti-brand-whatsapp" style="font-size:24px"></i>
          </div>
          <div class="h2 mb-0 fw-bold text-dark"><?= number_format($consultasWA) ?></div>
        </div>
        <div class="fw-semibold text-dark">WhatsApp</div>
        <div class="text-muted small mb-3">Consultas realizadas desde WhatsApp personal.</div>
        <hr class="my-2">
        <div class="d-flex gap-2">
          <span class="badge bg-green-lt text-green" style="font-size:.65rem">Bot activo</span>
        </div>
      </div>
    </div>
  </div>

</div>

<!-- ── KPIs ─────────────────────────────────────────────────────────── -->
<div class="row g-3 mb-4">
  <div class="col-6 col-md-4 col-lg-2 dash-animate">
    <div class="dash-kpi">
      <div>
        <div class="dash-kpi-val"><?= $totalEjemplos ?></div>
        <div class="dash-kpi-label">Ejemplos RAG</div>
        <div class="dash-kpi-sub">Base de conocimiento</div>
      </div>
      <div class="dash-kpi-icon bg-purple-lt text-purple ms-auto"><i class="ti ti-brain"></i></div>
    </div>
  </div>
  <div class="col-6 col-md-4 col-lg-2 dash-animate">
    <div class="dash-kpi">
      <div>
        <div class="dash-kpi-val"><?= number_format($totalConsultas) ?></div>
        <div class="dash-kpi-label">Total Consultas</div>
        <div class="dash-kpi-sub">Desde el inicio</div>
      </div>
      <div class="dash-kpi-icon bg-primary-lt text-primary ms-auto"><i class="ti ti-messages"></i></div>
    </div>
  </div>
  <div class="col-6 col-md-4 col-lg-2 dash-animate">
    <div class="dash-kpi">
      <div>
        <div class="dash-kpi-val"><?= $consultasHoy ?></div>
        <div class="dash-kpi-label">Hoy</div>
        <div class="dash-kpi-sub"><?= date('d/m/Y') ?></div>
      </div>
      <div class="dash-kpi-icon bg-green-lt text-green ms-auto"><i class="ti ti-calendar"></i></div>
    </div>
  </div>
  <div class="col-6 col-md-4 col-lg-2 dash-animate">
    <div class="dash-kpi">
      <div>
        <div class="dash-kpi-val"><?= $usuariosActivos ?></div>
        <div class="dash-kpi-label">Usuarios Activos</div>
        <div class="dash-kpi-sub">Últimos 7 días</div>
      </div>
      <div class="dash-kpi-icon bg-azure-lt text-azure ms-auto"><i class="ti ti-users"></i></div>
    </div>
  </div>
  <div class="col-6 col-md-4 col-lg-2 dash-animate">
    <div class="dash-kpi">
      <div>
        <div class="dash-kpi-val"><?= number_format($consultasWeb) ?></div>
        <div class="dash-kpi-label">Web</div>
        <div class="dash-kpi-sub">Desde navegador</div>
      </div>
      <div class="dash-kpi-icon bg-teal-lt text-teal ms-auto"><i class="ti ti-globe"></i></div>
    </div>
  </div>
  <div class="col-6 col-md-4 col-lg-2 dash-animate">
    <div class="dash-kpi">
      <div>
        <div class="dash-kpi-val"><?= number_format($consultasWA) ?></div>
        <div class="dash-kpi-label">WhatsApp</div>
        <div class="dash-kpi-sub">Bot personal</div>
      </div>
      <div class="dash-kpi-icon bg-green-lt text-green ms-auto"><i class="ti ti-brand-whatsapp"></i></div>
    </div>
  </div>
</div>

<!-- ── GRÁFICOS + TABLA ─────────────────────────────────────────────── -->
<div class="row g-3 mb-4">

  <!-- Evolución 7 días -->
  <div class="col-12 col-lg-5">
    <div class="dash-card h-100">
      <div class="dash-card-header">
        <i class="ti ti-chart-line text-primary" style="font-size:1rem"></i>
        <span class="dash-card-header-title">Consultas — Últimos 7 días</span>
      </div>
      <div class="dash-card-body" style="min-height:220px">
        <canvas id="chartEvo" height="180"></canvas>
      </div>
    </div>
  </div>

  <!-- RAG por canal (donut) -->
  <div class="col-12 col-lg-3">
    <div class="dash-card h-100">
      <div class="dash-card-header">
        <i class="ti ti-chart-donut text-primary" style="font-size:1rem"></i>
        <span class="dash-card-header-title">Ejemplos por Canal</span>
      </div>
      <div class="dash-card-body d-flex flex-column align-items-center justify-content-center gap-2" style="min-height:220px">
        <canvas id="chartCanal" width="160" height="160"></canvas>
        <div class="d-flex gap-3 small mt-1">
          <?php foreach($ragPorCanal as $c): ?>
          <div class="d-flex align-items-center gap-1">
            <div style="width:9px;height:9px;border-radius:50%;background:<?= ['web'=>'#2563eb','whatsapp'=>'#16a34a','ambos'=>'#ca8a04'][$c['canal']] ?? '#64748b' ?>"></div>
            <span class="text-muted"><?= ucfirst($c['canal']) ?> <?= $c['total'] ?></span>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>

  <!-- Últimas consultas -->
  <div class="col-12 col-lg-4">
    <div class="dash-card h-100">
      <div class="dash-card-header">
        <i class="ti ti-clock text-primary" style="font-size:1rem"></i>
        <span class="dash-card-header-title">Últimas Consultas</span>
        <a href="<?= $urlChat ?>" class="ms-auto text-muted small" style="text-decoration:none">Abrir chat <i class="ti ti-arrow-right"></i></a>
      </div>
      <div class="dash-card-body" style="padding:0">
        <?php if(empty($ultimasConsultas)): ?>
        <div class="text-center text-muted py-3 small">Sin consultas aún</div>
        <?php else: ?>
        <?php foreach($ultimasConsultas as $q): ?>
        <div class="timeline-item px-3">
          <div class="timeline-dot mt-1"
               style="background:<?= $q['canal']==='whatsapp'?'#16a34a':'#2563eb' ?>"></div>
          <div class="flex-grow-1">
            <div class="fw-medium small" style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap;max-width:220px"
                 title="<?= htmlspecialchars($q['pregunta']) ?>">
              <?= htmlspecialchars(mb_strimwidth($q['pregunta'],0,45,'…')) ?>
            </div>
            <div class="d-flex gap-2 mt-1">
              <span class="badge <?= $q['canal']==='whatsapp'?'bg-green-lt text-green':'bg-primary-lt text-primary' ?>" style="font-size:.6rem"><?= $q['canal'] ?></span>
              <span class="text-muted" style="font-size:.7rem"><?= $q['fecha'] ?></span>
              <?php if($q['tiempoMs']): ?><span class="text-muted" style="font-size:.7rem">⚡<?= $q['tiempoMs'] ?>ms</span><?php endif; ?>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>
  </div>

</div>

<!-- ── CÓMO APRENDE EL SISTEMA ───────────────────────────────────────── -->
<div class="row g-3 mb-4">
  <div class="col-12">
    <div class="dash-card">
      <div class="dash-card-header">
        <i class="ti ti-school text-primary" style="font-size:1rem"></i>
        <span class="dash-card-header-title">¿Cómo aprende ChaviBot?</span>
      </div>
      <div class="dash-card-body">
        <div class="row g-3">
          <div class="col-12 col-md-4">
            <div class="d-flex gap-3">
              <div style="width:36px;height:36px;border-radius:8px;background:#ede9fe;display:flex;align-items:center;justify-content:center;flex-shrink:0">
                <span style="font-size:16px">1️⃣</span>
              </div>
              <div>
                <div class="fw-semibold small mb-1">Ejemplos RAG</div>
                <div class="text-muted" style="font-size:.8rem">Cada ejemplo tiene una <strong>pregunta modelo</strong>, <strong>palabras clave</strong> y una <strong>consulta SQL</strong> que busca los datos reales. Cuando alguien pregunta algo parecido, el bot encuentra el ejemplo más cercano.</div>
              </div>
            </div>
          </div>
          <div class="col-12 col-md-4">
            <div class="d-flex gap-3">
              <div style="width:36px;height:36px;border-radius:8px;background:#e0f2fe;display:flex;align-items:center;justify-content:center;flex-shrink:0">
                <span style="font-size:16px">2️⃣</span>
              </div>
              <div>
                <div class="fw-semibold small mb-1">Consulta la base de datos</div>
                <div class="text-muted" style="font-size:.8rem">El bot ejecuta el SQL del ejemplo, obtiene los datos <strong>reales y actuales</strong> del sistema. No inventa información — todo viene de la BD.</div>
              </div>
            </div>
          </div>
          <div class="col-12 col-md-4">
            <div class="d-flex gap-3">
              <div style="width:36px;height:36px;border-radius:8px;background:#dcfce7;display:flex;align-items:center;justify-content:center;flex-shrink:0">
                <span style="font-size:16px">3️⃣</span>
              </div>
              <div>
                <div class="fw-semibold small mb-1">Redacta con Ollama (IA local)</div>
                <div class="text-muted" style="font-size:.8rem">Los datos pasan a <strong>llama3.1</strong> (corre en tu laptop). La IA redacta una respuesta clara en español, como si fuera una persona, sin mencionar tablas ni código.</div>
              </div>
            </div>
          </div>
        </div>
        <?php if($esAdmin): ?>
        <div class="alert alert-info mt-3 mb-0" style="border-radius:10px;font-size:.82rem">
          <i class="ti ti-bulb me-2"></i>
          <strong>Tip para admins:</strong> Agrega más ejemplos desde el panel de entrenamiento (ícono 🧠 en el chat). Cuantos más ejemplos, más preguntas entiende el bot. También puedes agregar desde WhatsApp con <code>/entrenar</code>.
        </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

</div><!-- /container-xl -->
</div><!-- /page-body -->

<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const COLORS = ['#2563eb','#0891b2','#16a34a','#ca8a04','#7c3aed','#ea580c'];

    // Evolución 7 días
    <?php
    $dias   = json_encode(array_column($porDia,'dia'));
    $totDia = json_encode(array_map('intval',array_column($porDia,'total')));
    ?>
    const ctxE = document.getElementById('chartEvo');
    if(ctxE) new Chart(ctxE,{type:'line',data:{
        labels:<?= $dias ?>,
        datasets:[{label:'Consultas',data:<?= $totDia ?>,
            borderColor:'#2563eb',backgroundColor:'rgba(37,99,235,.08)',
            borderWidth:2,fill:true,tension:.4,pointBackgroundColor:'#2563eb',pointRadius:4
        }]
    },options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{display:false}},
        scales:{y:{beginAtZero:true,ticks:{stepSize:1,font:{size:11}},grid:{color:'#f1f5f9'}},
                x:{ticks:{font:{size:11}},grid:{display:false}}}}});

    // Canal donut
    <?php
    $cLabels = json_encode(array_map(fn($c)=>ucfirst($c['canal']),$ragPorCanal));
    $cData   = json_encode(array_map(fn($c)=>intval($c['total']),$ragPorCanal));
    ?>
    const ctxC = document.getElementById('chartCanal');
    if(ctxC) new Chart(ctxC,{type:'doughnut',data:{
        labels:<?= $cLabels ?>,
        datasets:[{data:<?= $cData ?>,backgroundColor:COLORS,borderWidth:2,borderColor:'#fff',hoverOffset:4}]
    },options:{responsive:false,cutout:'65%',plugins:{legend:{display:false}}}});

    // Animar barras
    setTimeout(()=>{
        document.querySelectorAll('.dash-progress-bar').forEach(el=>{
            const w=el.style.width;el.style.width='0%';
            setTimeout(()=>el.style.width=w,100);
        });
    },300);
});
</script>
