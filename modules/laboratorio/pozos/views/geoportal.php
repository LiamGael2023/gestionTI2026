<?php
$valle = $_GET['valle'] ?? '';
$catastroModel = new CatastroPozoModel($conn);
$valles = $catastroModel->obtenerValles();
?>
<div class="page-header d-print-none">
    <div class="container-xl">
        <nav aria-label="breadcrumb" class="mb-3">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="?module=laboratorio">Laboratorio</a></li>
                <li class="breadcrumb-item"><a href="?module=laboratorio&action=pozos">Pozos</a></li>
                <li class="breadcrumb-item active">Geoportal</li>
            </ol>
        </nav>
        <div class="row g-2 align-items-center mb-2">
            <div class="col">
                <div class="page-pretitle">Monitoreo de Pozos</div>
                <h2 class="page-title"><i class="ti ti-map-2 me-2"></i>Geoportal</h2>
            </div>
            <div class="col-auto ms-auto">
                <div class="d-flex gap-2">
                    <select class="form-select" id="filtro-valle-mapa" style="width:200px;">
                        <option value="">Todos los valles</option>
                        <?php foreach ($valles as $v): ?>
                        <option value="<?php echo htmlspecialchars($v, ENT_QUOTES, 'UTF-8'); ?>" <?php echo $valle === $v ? 'selected' : ''; ?>><?php echo htmlspecialchars($v, ENT_QUOTES, 'UTF-8'); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <a href="?module=laboratorio&action=pozos&subaction=index" class="btn btn-outline-secondary">
                        <i class="ti ti-arrow-left me-1"></i> Volver
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="page-body">
    <div class="container-fluid px-2">
        <div class="card">
            <div class="card-body p-0">
                <div id="mapa-pozos" style="width:100%;height:650px;"></div>
            </div>
        </div>
    </div>
</div>

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
#mapa-pozos { border-radius: 6px; }
.pozo-popup h6 { margin: 0 0 6px 0; font-weight: 600; }
.pozo-popup p { margin: 2px 0; font-size: 0.85rem; }
.pozo-popup .badge { margin-right: 4px; }
</style>

<script>
var mapa = L.map('mapa-pozos').setView([-8.5, -78.5], 7);

L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '&copy; OpenStreetMap',
    maxZoom: 19
}).addTo(mapa);

var markersLayer = L.layerGroup().addTo(mapa);

function cargarPozos(valle) {
    var url = 'modules/laboratorio/pozos/controllers/PozoAPI.php?action=geoportal_pozos';
    if (valle) {
        url += '&valle=' + encodeURIComponent(valle);
    }

    $.getJSON(url, function (resp) {
        if (!resp.success || !resp.data || !resp.data.length) {
            Swal.fire({ icon: 'info', title: 'Sin pozos', text: 'No se encontraron pozos.', confirmButtonColor: '#009540' });
            return;
        }

        markersLayer.clearLayers();
        var bounds = [];

        resp.data.forEach(function (p) {
            if (p.lat == null || p.lng == null) return;

            var lat = parseFloat(p.lat);
            var lng = parseFloat(p.lng);

            var marker = L.marker([lat, lng]).addTo(markersLayer);
            bounds.push([lat, lng]);

            var idP = esc(p.Id_Pozo);
            var v  = esc(p.valle);
            var u  = esc(p.ubicacion);
            var t  = esc(p.tipopozo);

            var popupHtml = '<div class="pozo-popup">'
                + '<h6>' + idP + '</h6>'
                + '<p><b>Valle:</b> ' + v + '</p>'
                + '<p><b>Ubicacion:</b> ' + u + '</p>'
                + '<p><b>Tipo:</b> ' + t + '</p>'
                + '<a href="?module=laboratorio&action=pozos&subaction=historial_pozo&id_pozo=' + encodeURIComponent(p.Id_Pozo) + '" class="btn btn-sm btn-primary mt-1">Ver Historial</a>'
                + '</div>';
            marker.bindPopup(popupHtml);
        });

        if (bounds.length > 0) {
            mapa.fitBounds(bounds, { padding: [30, 30] });
        }
    })
    .fail(function () {
        Swal.fire({ icon: 'error', title: 'Error', text: 'No se pudieron cargar los pozos.', confirmButtonColor: '#009540' });
    });
}

function esc(str) {
    if (!str) return '';
    return str.replace(/&/g,'&amp;').replace(/"/g,'&quot;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}

$('#filtro-valle-mapa').on('change', function () {
    cargarPozos($(this).val());
});

cargarPozos('<?php echo htmlspecialchars($valle, ENT_QUOTES, 'UTF-8'); ?>');
</script>
