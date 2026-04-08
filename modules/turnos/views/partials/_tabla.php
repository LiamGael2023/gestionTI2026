<!-- CARD TABLA -->
<div class="card">
  <div class="card-body">
    <div class="table-responsive">
      <table id="tablaTrabajadores" class="table table-bordered table-striped">
        <thead>
          <tr>
            <th><input type="checkbox" id="checkAll"></th>
            <th>T. Trabajador</th>
            <th>Nombre</th>
            <th>Componente</th>
            <th>Meta</th>
            <th>Horario</th>
            <th>Turno</th>
            <th>Eliminar</th>
          </tr>
        </thead>
        <tbody>

          <?php if (!empty($trabajadores)): ?>
            <?php foreach ($trabajadores as $row): ?>
              <tr
                data-trabajador="<?= $row['Id_Trabajador'] ?>"
                data-componente="<?= $row['Id_Componente'] ?>"
                data-meta="<?= $row['Id_Meta'] ?>"
                data-anio="<?= $anio ?>"
                data-tipotrabajador="<?= $row['Id_Trabajador_Tipo'] ?>"
                data-horario="<?= $row['Id_Horario'] ?? '' ?>"
                data-fechainicio="<?= $row['FechaInicioTurno'] ?? '' ?>"
                data-fechafin="<?= $row['FechaFinTurno'] ?? '' ?>"
              >
                <td><input type="checkbox" class="checkItem"></td>
                 <td><?= $row['TrabTipo_Abreviacion'] ?></td>
                <td><?= $row['Trab_Nombres_Full'] ?></td>
                <td><?= $row['Comp_Descripcion'] ?></td>
                <td><?= $row['Meta_Descripcion'] ?></td>
               

                <td class="horarioSeleccionado">
                  <?php if (!empty($row['Id_Horario'])): ?>
                    <span class="badge badge-success"><?= $row['Horario'] ?? '' ?></span>
                  <?php else: ?>
                    <span class="badge badge-light">Sin horario</span>
                  <?php endif; ?>
                </td>

                <td class="turnoAsignado" id="turno-<?= $row['Id_Trabajador'] ?>">
                  <?php if (!empty($row['Turno'])): ?>
                    <span class="badge badge-info">Turno asignado</span>
                  <?php else: ?>
                    <span class="badge badge-light">Sin turno</span>
                  <?php endif; ?>
                </td>

                <td>
                  <button type="button" class="btnEliminarTurno btn btn-danger btn-sm">
                    Eliminar
                  </button>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
           <tr>
              <td></td>
              <td></td>
              <td></td>
              <td class="text-center">No se encontraron registros</td>
              <td></td>
              <td></td>
              <td></td>
              <td></td>
            </tr>

          <?php endif; ?>

        </tbody>
      </table>
    </div>
  </div>
</div>