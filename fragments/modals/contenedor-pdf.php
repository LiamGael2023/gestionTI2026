<div class="modal fade" id="pdfModal" tabindex="-1" aria-labelledby="pdfModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="pdfModalLabel">Reporte PDF</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <iframe id="pdfViewer" src="" frameborder="0" style="width: 100%; height: 80vh;"></iframe>
            </div>
        </div>
    </div>
</div>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const pdfModal = document.getElementById('pdfModal');

        pdfModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const pdfUrl = button.getAttribute('data-pdf-url');
            const iframe = pdfModal.querySelector('#pdfViewer');
            iframe.src = pdfUrl;
        });

        pdfModal.addEventListener('hidden.bs.modal', function() {
            const iframe = pdfModal.querySelector('#pdfViewer');
            iframe.src = '';
        });
    });
</script>