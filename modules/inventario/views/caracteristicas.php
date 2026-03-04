 <body>
   <div class="page">

     <!-- NAVBAR -->
     <header class="navbar navbar-expand-md navbar-light d-print-none">
       <div class="container-xl">
         <h1 class="navbar-brand">
           <i class="ti ti-package me-2 text-primary"></i>
           Inventario TI
         </h1>
       </div>
     </header>

     <div class="page-wrapper">
       <div class="container-xl">

         <!-- PAGE HEADER -->
         <div class="page-header d-print-none">
           <div class="row align-items-center">
             <div class="col">
               <h2 class="page-title">Configuraciones</h2>
               <div class="text-muted mt-1">
                 Gestión de datos base del sistema de inventario.
               </div>
             </div>

             <div class="col-auto ms-auto">
               <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalAgregarCaracteristica">
                 <i class="ti ti-plus me-1"></i>
                 Agregar Caracteristica
               </button>
             </div>
           </div>
         </div>

         <!-- TABS -->
         <div class="card mb-4">
           <div class="card-header">
             <ul class="nav nav-tabs card-header-tabs">
               <li class="nav-item">
                 <a class="nav-link" href="?module=inventario&action=activos">
                   <i class="ti ti-devices me-1"></i> Activos
                 </a>
               </li>
               <li class="nav-item">
                 <a class="nav-link" href="?module=inventario&action=tipoCaracteristicas">
                   <i class="ti ti-category me-1"></i> Tipo Caracteristicas
                 </a>
               </li>
               <li class="nav-item">
                 <a class="nav-link active" href="?module=inventario&action=caracteristicas">
                   <i class="ti ti-adjustments me-1"></i> Caracteristicas
                 </a>
               </li>
               <li class="nav-item">
                 <a class="nav-link" href="?module=inventario&action=ubicaciones">
                   <i class="ti ti-map-pin me-1"></i> Ubicaciones
                 </a>
               </li>
             </ul>
           </div>

           <!-- TABLE -->
           <div class="card-body p-0">
             <div class="table-responsive">
               <table class="table table-vcenter">
                 <thead>
                   <tr>
                     <th>Nombre</th>
                     <th>Descripción</th>
                     <th>Fecha de Creación</th>
                     <th>Registrado Por</th>
                     <th class="text-end">Acciones</th>
                   </tr>
                 </thead>
                 <tbody>
                   <tr>
                     <td>
                       <i class="ti ti-router text-primary me-2"></i>
                       Marca
                     </td>
                     <td>Lenovo</td>
                     <td>12/05/2023</td>
                     <td>Rommel Jhondeyber Díaz Nureña</td>
                     <td class="text-end">
                       <button class="btn btn-sm btn-icon"
                         data-bs-toggle="modal"
                         data-bs-target="#modalEditarCaracteristica">
                         <i class="ti ti-edit me-1"></i>
                       </button>
                       <a href="#" class="btn btn-sm btn-icon text-danger">
                         <i class="ti ti-trash"></i>
                       </a>
                     </td>
                   </tr>

                   <tr>
                     <td>
                       <i class="ti ti-device-laptop text-purple me-2"></i>
                       Marca
                     </td>
                     <td>Dell</td>
                     <td>15/05/2026</td>
                     <td>Rommel Jhondeyber Díaz Nureña</td>
                     <td class="text-end">
                       <a href="#" class="btn btn-sm btn-icon">
                         <i class="ti ti-edit"></i>
                       </a>
                       <a href="#" class="btn btn-sm btn-icon text-danger">
                         <i class="ti ti-trash"></i>
                       </a>
                     </td>
                   </tr>

                 </tbody>
               </table>
             </div>
           </div>

         </div>

         <!-- INFO CARDS -->
         <div class="row row-deck row-cards">

           <div class="col-md-4">
             <div class="card bg-primary-lt">
               <div class="card-body">
                 <div class="d-flex align-items-center">
                   <i class="ti ti-info-circle text-primary me-2"></i>
                   <strong>Información del Sistema</strong>
                 </div>
                 <p class="text-muted mt-2 mb-0">
                   Las categorías afectan a todos los equipos vinculados.
                 </p>
               </div>
             </div>
           </div>

           <div class="col-md-4">
             <div class="card">
               <div class="card-body">
                 <strong>Últimos Cambios</strong>
                 <ul class="mt-2">
                   <li>Nueva ubicación agregada</li>
                   <li>Categoría editada hace 2 horas</li>
                 </ul>
               </div>
             </div>
           </div>

           <div class="col-md-4">
             <div class="card">
               <div class="card-body">
                 <strong>Resumen</strong>
                 <div class="mt-2">
                   <div>Total Categorías: <strong>12</strong></div>
                   <div>Bajo Uso: <strong class="text-warning">3</strong></div>
                 </div>
               </div>
             </div>
           </div>

         </div>

       </div>
     </div>

   </div>
 </body>

 <!-- Modal Agregar Activo -->
<div class="modal modal-blur fade" id="modalAgregarCaracteristica" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">

      <!-- Header -->
      <div class="modal-header">
        <h5 class="modal-title d-flex align-items-center gap-3">
          <div class="avatar avatar-sm bg-primary-lt text-primary">
            <i class="ti ti-settings"></i>
          </div>
          Agregar Característica
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <!-- Body -->
      <div class="modal-body">

        <!-- Tipo de Característica -->
        <div class="mb-4">
          <label class="form-label">Tipo de Característica</label>
          <select class="form-select">
            <option selected disabled>Seleccionar tipo...</option>
            <option value="marca">Marca</option>
            <option value="modelo">Modelo</option>
            <option value="pantalla">Tipo de Pantalla</option>
            <option value="pulgadas">Pulgadas</option>
            <option value="capacidad">Capacidad</option>
            <option value="procesador">Procesador</option>
            <option value="ram">Memoria RAM</option>
          </select>
        </div>

        <!-- Valor -->
        <div class="mb-4">
          <label class="form-label">Valor</label>
          <input type="text"
                 class="form-control"
                 placeholder='Ej: Dell, Latitude 5420, LED, 14"'>
        </div>

        <!-- Auditoría -->
        <div>
          <div class="d-flex align-items-center gap-2 text-muted mb-3">
            <i class="ti ti-history"></i>
            <span class="text-uppercase small fw-bold">
              Información de Auditoría
            </span>
          </div>

          <div class="row g-3">

            <div class="col-md-6">
              <div class="card card-sm">
                <div class="card-body">
                  <div class="text-muted small text-uppercase mb-1">
                    Usuario Creación
                  </div>
                  <div class="d-flex align-items-center gap-2">
                    <i class="ti ti-user text-primary"></i>
                    <span class="fw-medium">admin_user</span>
                  </div>
                </div>
              </div>
            </div>

            <div class="col-md-6">
              <div class="card card-sm">
                <div class="card-body">
                  <div class="text-muted small text-uppercase mb-1">
                    Fecha Creación
                  </div>
                  <div class="d-flex align-items-center gap-2">
                    <i class="ti ti-calendar text-primary"></i>
                    <span class="fw-medium">2023-11-15</span>
                  </div>
                </div>
              </div>
            </div>

          </div>
        </div>

      </div>

      <!-- Footer -->
      <div class="modal-footer">
        <button type="button" class="btn btn-link" data-bs-dismiss="modal">
          Cancelar
        </button>

        <button type="button" class="btn btn-primary">
          <i class="ti ti-device-floppy me-1"></i>
          Guardar
        </button>
      </div>

    </div>
  </div>
</div>

 <!-- Modal Editar Característica -->
<div class="modal modal-blur fade" id="modalEditarCaracteristica" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">

      <!-- Header -->
      <div class="modal-header">
        <h5 class="modal-title d-flex align-items-center gap-3">
          <div class="avatar avatar-sm bg-primary-lt text-primary">
            <i class="ti ti-edit"></i>
          </div>
          Editar Característica
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <!-- Body -->
      <div class="modal-body">

        <!-- Tipo de Característica -->
        <div class="mb-4">
          <label class="form-label">Tipo de Característica</label>
          <select class="form-select">
            <option value="marca">Marca</option>
            <option value="modelo" selected>Modelo</option>
            <option value="pantalla">Tipo de Pantalla</option>
            <option value="pulgadas">Pulgadas</option>
            <option value="capacidad">Capacidad</option>
            <option value="procesador">Procesador</option>
            <option value="ram">Memoria RAM</option>
          </select>
        </div>

        <!-- Valor -->
        <div class="mb-4">
          <label class="form-label">Valor</label>
          <input type="text"
                 class="form-control"
                 value="Latitude 5420"
                 placeholder="Ingrese el valor">
        </div>

        <!-- Auditoría -->
        <div>
          <div class="d-flex align-items-center gap-2 text-muted mb-3">
            <i class="ti ti-history"></i>
            <span class="text-uppercase small fw-bold">
              Información de Auditoría
            </span>
          </div>

          <div class="row g-3">

            <!-- Usuario Creación -->
            <div class="col-md-6">
              <div class="card card-sm">
                <div class="card-body">
                  <div class="text-muted small text-uppercase mb-1">
                    Usuario Creación
                  </div>
                  <div class="d-flex align-items-center gap-2">
                    <i class="ti ti-user text-primary"></i>
                    <span class="fw-medium">admin_user</span>
                  </div>
                </div>
              </div>
            </div>

            <!-- Fecha Creación -->
            <div class="col-md-6">
              <div class="card card-sm">
                <div class="card-body">
                  <div class="text-muted small text-uppercase mb-1">
                    Fecha Creación
                  </div>
                  <div class="d-flex align-items-center gap-2">
                    <i class="ti ti-calendar text-primary"></i>
                    <span class="fw-medium">2023-11-15</span>
                  </div>
                </div>
              </div>
            </div>

            <!-- Usuario Modificación -->
            <div class="col-md-6">
              <div class="card card-sm">
                <div class="card-body">
                  <div class="text-muted small text-uppercase mb-1">
                    Usuario Modificación
                  </div>
                  <div class="d-flex align-items-center gap-2">
                    <i class="ti ti-edit text-primary"></i>
                    <span class="fw-medium">editor_pro</span>
                  </div>
                </div>
              </div>
            </div>

            <!-- Fecha Modificación -->
            <div class="col-md-6">
              <div class="card card-sm">
                <div class="card-body">
                  <div class="text-muted small text-uppercase mb-1">
                    Fecha Modificación
                  </div>
                  <div class="d-flex align-items-center gap-2">
                    <i class="ti ti-refresh text-primary"></i>
                    <span class="fw-medium">2024-05-20</span>
                  </div>
                </div>
              </div>
            </div>

          </div>
        </div>

      </div>

      <!-- Footer -->
      <div class="modal-footer">
        <button type="button" class="btn btn-link" data-bs-dismiss="modal">
          Cancelar
        </button>

        <button type="button" class="btn btn-primary">
          <i class="ti ti-device-floppy me-1"></i>
          Guardar Cambios
        </button>
      </div>

    </div>
  </div>
</div>