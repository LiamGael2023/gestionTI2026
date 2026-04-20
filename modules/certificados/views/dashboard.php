<div class="container-xl">
    <div class="page-header">
          <!-- HEADER ACCIONES -->
    <div class="card shadow-sm mb-3 border-0">
        <div class="card-body d-flex flex-wrap gap-2 justify-content-between align-items-center">

            <div class="d-flex flex-wrap gap-2">
                <h2 class="page-title">Certificados Dashboard</h2>

   
            </div>

            
          <a href="index.php?module=certificados" class="btn btn-primary btn-sm">
       Panel  </a>
        </div>
    </div>
    
  

    </div>

    <!-- HEADER ACCIONES -->
    <div class="card shadow-sm mb-3 border-0">
        <div class="card-body d-flex flex-wrap gap-2 justify-content-between align-items-center">

            <div class="d-flex flex-wrap gap-2">
                <a href="index.php?module=certificados&action=crear" class="btn btn-primary btn-sm">+ Certificado</a>
                <a href="index.php?module=certificados&action=crearBackup1" class="btn btn-outline-primary btn-sm">Backup</a>
                <a href="index.php?module=certificados&action=verPersonas1" class="btn btn-outline-secondary btn-sm">Personas</a>
                <a href="index.php?module=certificados&action=certificadosPorVencer1" class="btn btn-outline-danger btn-sm">Reportes</a>
                <a href="index.php?module=certificados&action=tramite" class="btn btn-outline-primary btn-sm">Trámite</a>
                <a href="index.php?module=certificados&action=dashboard" class="btn btn-dark btn-sm">Dashboard</a>
            </div>

            

        </div>
    </div>

<!-- DASHBOARD RESUMEN Y PERSONAS REORDENADO 3x2 -->
<div class="row row-deck row-cards">

    <!-- Tarjeta 1 -->
    <div class="col-sm-6 col-lg-4">
        <div class="card">
            <div class="card-body">
                <div class="subheader">Total certificados</div>
                <div class="h1">📄 <?php echo $total; ?></div>
            </div>
        </div>
    </div>

    <!-- Tarjeta 2 -->
    <div class="col-sm-6 col-lg-4">
        <div class="card">
            <div class="card-body">
                <div class="subheader">Certificados Activos</div>
                <div class="h1 text-green">✅ <?php echo $activos; ?></div>
            </div>
        </div>
    </div>

    <!-- Tarjeta 3 -->
    <div class="col-sm-6 col-lg-4">
        <div class="card">
            <div class="card-body">
                <div class="subheader">Certificados Por Vencer</div>
                <div class="h1 text-warning">⚠ <?php echo $porVencer; ?></div>
            </div>
        </div>
    </div>

    <!-- Tarjeta 4 -->
    <div class="col-sm-6 col-lg-4">
        <div class="card">
            <div class="card-body">
                <div class="subheader">Certificados Vencidos</div>
                <div class="h1 text-danger">❌ <?php echo $vencidos; ?></div>
            </div>
        </div>
    </div>

    <!-- Tarjeta 5 -->
    <div class="col-sm-6 col-lg-4">
        <div class="card">
            <div class="card-body">
                <div class="subheader">Total Personas</div>
                <div class="h1">👤 <?php echo $totalPersonas; ?></div>
            </div>
        </div>
    </div>

    <!-- Tarjeta 6 -->
    <div class="col-sm-6 col-lg-4">
        <div class="card">
            <div class="card-body">
                <div class="subheader">Certificados Este Año</div>
                <div class="h1">📆 <?php echo array_sum(array_column($certificadosMes,'total')); ?></div>
            </div>
        </div>
    </div>

</div>
    <!-- GRÁFICOS -->
    <div class="container-fluid mt-4">
        <div class="row">

            <!-- Certificados por Mes -->
            <div class="col-lg-6">
                <div class="card mb-3">
                    <div class="card-body">
                        <div class="subheader">Certificados por Mes</div>
                        <canvas id="chartMes" height="300"></canvas>
                    </div>
                </div>
            </div>

            <!-- Certificados por Gerencia -->
            <div class="col-lg-6">
                <div class="card mb-3">
                    <div class="card-body">
                        <div class="subheader">Certificados por Gerencia</div>
                        <canvas id="chartGerencias" height="300"></canvas>
                    </div>
                </div>
            </div>

            <!-- Certificados por Año -->
            <div class="col-lg-12">
                <div class="card mb-3">
                    <div class="card-body">
                        <div class="subheader">Certificados por Año</div>
                        <canvas id="chartAnio" height="100"></canvas>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
       // --- DATOS PHP ---
   const meses = <?php echo json_encode(array_column($certificadosMes,'mes')); ?>;
const totalMes = <?php echo json_encode(array_column($certificadosMes,'total')); ?>;


new Chart(document.getElementById('chartMes'), {
    type: 'bar',
    data: {
        labels: meses,
        datasets: [{
            label: 'Certificados por Mes',
            data: totalMes,
            backgroundColor: 'rgba(54, 162, 235, 0.6)'
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { display: true } }
    }
});
        // --- DATOS POR GERENCIA ---
        const gerencias = <?php echo json_encode(array_column($gerenciasComparativa,'gerencia_laboral')); ?>;
    const totalGerencias = <?php echo json_encode(array_column($gerenciasComparativa,'total')); ?>;

    // Ordenar de mayor a menor
    const combined = gerencias.map((g,i)=>({gerencia:g,total:totalGerencias[i]}));
    combined.sort((a,b)=>b.total-a.total);

    const sortedGerencias = combined.map(item=>item.gerencia);
    const sortedTotales = combined.map(item=>item.total);

    // Generar colores consistentes y distintos
    function getColors(num){
        return Array.from({length:num},(_,i)=>`hsl(${Math.floor(i*360/num)},70%,50%)`);
    }

  new Chart(document.getElementById('chartGerencias'), {
    type: 'pie',
    data: {
        labels: sortedGerencias,
        datasets: [{
            label: 'Certificados por Gerencia',
            data: sortedTotales,
            backgroundColor: getColors(sortedGerencias.length)
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                position: 'bottom',
                labels: {
                    boxWidth: 20,
                    padding: 10,
                    generateLabels: (chart) => {
                        const data = chart.data;
                        return data.labels.map((label, index) => {
                            const hidden = chart.getDatasetMeta(0).data[index].hidden;
                            return {
                                text: `${label} (${data.datasets[0].data[index]})`,
                                fillStyle: data.datasets[0].backgroundColor[index],
                                hidden: hidden,
                                index: index,
                                // Estilo tachado si está oculto
                                textDecoration: hidden ? 'line-through' : 'none'
                            };
                        });
                    }
                },
                onClick: (e, legendItem, legend) => {
                    const index = legendItem.index;
                    const chart = legend.chart;
                    const meta = chart.getDatasetMeta(0);

                    // Alternar visibilidad del slice
                    meta.data[index].hidden = !meta.data[index].hidden;
                    chart.update();
                }
            }
        }
    }
});
const anios = <?php echo json_encode(array_column($certificadosAnio,'anio')); ?>;
const totalAnios = <?php echo json_encode(array_column($certificadosAnio,'total')); ?>;

new Chart(document.getElementById('chartAnio'), {
    type: 'line',
    data: {
        labels: anios,
        datasets: [{
            label: 'Certificados por Año',
            data: totalAnios,
            fill: false,
            borderColor: 'rgba(99, 109, 255, 0.8)',
            tension: 0.2
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { display: true, position: 'top' } },
        scales: { y: { beginAtZero: true, precision:0 } }
    }
});
    </script>
</div>

<?php include $_SERVER['DOCUMENT_ROOT'] . '/gestionTI/public/footer.php'; ?>