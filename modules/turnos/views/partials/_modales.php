
<!-- MODAL: DESCRIPCIÓN DEL TURNO                   -->
<div class="modal fade" id="modalDescripcion" tabindex="-1" role="dialog"
     aria-labelledby="modalDescripcionLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">

      <div class="modal-header">
        <h5 class="modal-title" id="modalDescripcionLabel">Descripción del turno</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>

      <div class="modal-body">
        <div id="sugerenciasObservacion" class="mb-2"></div>
        <textarea id="descripcionTurno" class="form-control" rows="3"
                  placeholder="Ingrese descripción"></textarea>
      </div>

      <div class="modal-footer">
        <button type="button" id="guardarDescripcion" class="btn btn-success">Guardar</button>
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
      </div>

    </div>
  </div>
</div>



<!-- MODAL: HORARIO MENSUAL                           -->
<div class="modal fade" id="modalHorarioMes" tabindex="-1" role="dialog"
     aria-labelledby="modalHorarioMesLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl" role="document">
    <div class="modal-content">

      <div class="modal-header">
        <h5 class="modal-title" id="modalHorarioMesLabel">Asignar Turno</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>

      <div class="modal-body">

        <!-- Filtros de mes y año -->
        <div class="filtros mb-2">

          <label>Mes:</label>
          <select id="mesModal" class="form-control form-control-sm d-inline w-auto">
            <option value="1">Enero</option>
            <option value="2">Febrero</option>
            <option value="3">Marzo</option>
            <option value="4">Abril</option>
            <option value="5">Mayo</option>
            <option value="6">Junio</option>
            <option value="7">Julio</option>
            <option value="8">Agosto</option>
            <option value="9">Septiembre</option>
            <option value="10">Octubre</option>
            <option value="11">Noviembre</option>
            <option value="12">Diciembre</option>
          </select>

          <label>Año:</label>
          <input type="number" id="anioModal" value="<?= $anio ?>"
                 class="form-control form-control-sm d-inline w-auto"
                 min="2000" max="<?= date('Y') + 5 ?>">

          <button type="button" id="btnActualizarModal" class="btn btn-primary btn-sm">
            Actualizar
          </button>

          <button id="btnDescargarPDF" class="btn btn-info">
            <i class="fa fa-file-pdf"></i> Descargar Reporte
          </button>

          <button id="btnDescargarExcel" class="btn btn-success">
            <i class="fa fa-file-excel"></i> Descargar Excel
          </button>

          <div class="filtro-item">
            <label>Tipo Marcación</label>
            <select name="marcacion" id="marcacion" class="form-control">
              <?php foreach ($marcaciones as $marc): ?>
                <option value="<?= $marc["Id_Marcacion_Tipo"] ?>"
                  <?= ($marcacionturno == $marc["Id_Marcacion_Tipo"]) ? 'selected' : '' ?>>
                  <?= $marc["MarcTipo_Descripcion"] ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="filtro-item">
            <button type="button" id="btnAgregarObservacion"
                    class="btn btn-warning btn-sm mt-2" style="display:none;">
              <i class="fa fa-pencil"></i> Agregar Observación
            </button>
          </div>

        </div>

        <!-- Tabla horario -->
        <div id="reportePDF">
          <h5 id="tituloReporte" class="text-center mb-3"></h5>
          <div id="leyendaMarcacion" class="mb-2"></div>
          <div class="tabla-scroll">
            <table class="table table-bordered" id="tablaHorarioModal">
              <thead></thead>
              <tbody></tbody>
            </table>
          </div>
        </div>

      </div><!-- /.modal-body -->

      <div class="modal-footer">
        <button class="btn btn-success" id="guardarHorarioModal">Guardar Turno</button>
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
      </div>

    </div>
  </div>
</div>