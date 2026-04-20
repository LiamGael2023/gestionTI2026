<form method="POST" action="index.php?module=certificados&action=crear" enctype="multipart/form-data">
  
  <!-- Seleccionar persona -->
  <div class="mb-3">
    <label for="id_persona">Seleccionar Persona</label>
    <select name="id_persona" id="id_persona" class="form-control" required>
      <option value="">--Seleccione--</option>
      <?php foreach($personas as $p): ?>
        <option value="<?= $p['id_persona'] ?>">
          <?= $p['nombres'] . " " . $p['apellidos'] ?> (DNI: <?= $p['dni'] ?>)
        </option>
      <?php endforeach; ?>
    </select>
  </div>

  <!-- Código reloj -->
  <div class="mb-3">
    <label for="codigo_reloj">Código Reloj</label>
    <input type="number" name="codigo_reloj" id="codigo_reloj" class="form-control" required>
  </div>

  <!-- Fecha de emisión -->
  <div class="mb-3">
    <label for="fecha_emision">Fecha de Emisión</label>
    <input type="date" name="fecha_emision" id="fecha_emision" class="form-control" required>
  </div>

  <!-- Duración en años -->
  <div class="mb-3">
    <label for="duracion_anios">Duración (años)</label>
    <input type="number" name="duracion_anios" id="duracion_anios" class="form-control" value="1" required>
  </div>

  <!-- Estado -->
  <div class="mb-3">
    <label for="estado">Estado</label>
    <select name="estado" id="estado" class="form-control" required>
      <option value="activo">Activo</option>
      <option value="vencido">Vencido</option>
    </select>
  </div>

  <!-- Archivo del certificado -->
  <div class="mb-3">
    <label for="archivo">Archivo (.pfx)</label>
    <input type="file" name="archivo" id="archivo" class="form-control" accept=".pfx" required>
  </div>

  <!-- Tipo de certificado -->
  <div class="mb-3">
    <label for="tipo_certificado">Tipo de Certificado</label>
    <select name="tipo_certificado" id="tipo_certificado" class="form-control" required>
      <option value="TOKEN_SOFTWARE">TOKEN_SOFTWARE</option>
      <option value="HARDWARE">HARDWARE</option>
    </select>
  </div>

  <div class="mb-3">
    <button type="submit" class="btn btn-success">Crear Certificado</button>
  </div>

</form>