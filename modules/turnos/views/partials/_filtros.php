<!-- CARD FILTROS -->
<div class="card mb-3">
  <div class="card-body">
    <form method="GET">
      <div class="d-flex flex-column">
        <div class="d-flex align-items-end" style="gap:15px; width:100%;">

          <!-- Año -->
          <div>
            <label>Año</label>
            <select name="anio" id="anio" class="form-control">
              <?php for ($i = date("Y") - 7; $i <= date("Y") + 2; $i++): ?>
                <option value="<?= $i ?>" <?= ($anio == $i) ? 'selected' : '' ?>>
                  <?= $i ?>
                </option>
              <?php endfor; ?>
            </select>
          </div>

          <!-- Componente -->
          <div class="filtro-item">
            <label>Componente</label>
            <select name="componente" id="componente" class="form-control"
              <?= isset($mapMeta[$idUsuarioLogueado]) ? 'disabled' : '' ?>>
              <?php foreach ($componentes as $comp): ?>
                <option value="<?= $comp["Id_Componente"] ?>"
                  <?= ($componente == $comp["Id_Componente"]) ? 'selected' : '' ?>>
                  <?= $comp["Comp_Descripcion"] ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <!-- Meta -->
          <div class="filtro-item">
            <label>Meta</label>
            <select name="meta" id="meta" class="form-control"
              <?= isset($mapMeta[$idUsuarioLogueado]) ? 'disabled' : '' ?>>
              <?php foreach ($metas as $m): ?>
                <option value="<?= $m["Id_Meta"] ?>"
                  <?= ($meta == $m["Id_Meta"]) ? 'selected' : '' ?>>
                  <?= $m["Meta_Descripcion"] ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <!-- Botones -->
          <div class="filtro-item">
            <button type="submit" class="btn btn-primary">
              <i class="fa fa-search"></i> Buscar
            </button>
          </div>
          <div class="filtro-item">
            <button type="button" id="btnAgregarHorario" class="btn btn-success">
              <i class="fa fa-calendar"></i> Agregar Turno
            </button>
          </div>
          <div class="filtro-item">
            <button type="button" id="btnUsuariosSeleccionados" class="btn btn-success">
              <i class="fa fa-users"></i> Usuarios
            </button>
          </div>

        </div>
      </div>
    </form>
  </div>
</div>