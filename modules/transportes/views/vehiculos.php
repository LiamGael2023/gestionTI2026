<style>
  /* === Centrado visual del campo seleccionado === */
  .select2-selection.select2-selection--single {
    height: 38px !important;
    /* igual al input Bootstrap */
    display: flex !important;
    align-items: center !important;
    padding: 0 0.75rem !important;
    position: relative;
  }

  /* Alinea el texto y avatar correctamente */
  .select2-selection__rendered {
    display: flex !important;
    align-items: center !important;
    gap: 6px;
    line-height: normal !important;
    padding-right: 24px !important;
    /* deja espacio para la flecha */
  }

  /* Corrige la posición de la flecha ▼ */
  .select2-selection__arrow {
    position: absolute !important;
    right: 10px !important;
    top: 50% !important;
    transform: translateY(-50%) !important;
    height: auto !important;
    width: 12px !important;
    pointer-events: none;
    /* evita interferencias al hacer clic */
  }

  /* Corrige el botón de limpiar (X) */
  .select2-selection__clear {
    position: absolute !important;
    right: 28px !important;
    top: 50% !important;
    transform: translateY(-50%) !important;
    cursor: pointer;
    font-size: 16px !important;
    color: #6c757d !important;
  }

  /* Avatar */
  .avatar {
    border-radius: 10%;
    background-size: cover;
    background-position: center;
    display: inline-block;
  }

  td h6[title]:hover::after {
    content: attr(title);
    position: absolute;
    background-color: #fff;
    color: #000;
    border: 1px solid #ccc;
    padding: 5px;
    box-shadow: 2px 2px 10px rgba(0, 0, 0, 0.1);
    z-index: 9999;
  }

  .btn-tabler {
    font-size: 12px;
    /* Reduce el tamaño del texto si es necesario */
    padding: 5px 10px;
    /* Reduce el espaciado dentro del botón */
  }

  .btn-tabler svg {
    width: 14px;
    /* Ajusta el tamaño del icono */
    height: 14px;
    /* Ajusta el tamaño del icono */
  }
</style>

<style>
  @media (min-width: 1000px) {

    .tablaRegistroVehiculo {
      width: 100% !important;
      table-layout: fixed !important;
      /* obliga a respetar porcentajes */
    }

    .col-id {
      width: 1% !important;
    }

    .col-foto {
      width: 4% !important;
    }

    .col-marca {
      width: 10% !important;
    }

    .col-placa {
      width: 6% !important;
    }

    .col-asignacion {
      width: 6% !important;
    }

    .col-conductor {
      width: 14% !important;
    }

    .col-tipo {
      width: 12% !important;
    }

    .col-modelo {
      width: 13% !important;
    }

    .col-codigo-patrimonial {
      width: 5% !important;
    }

    .col-jefe {
      width: 14% !important;
    }


    .col-acciones {
      width: 15% !important;
    }



    /* Garantiza que las imágenes, SVG o avatares no rompan el ancho */
    .tablaRegistroVehiculo td img,
    .tablaRegistroVehiculo td svg,
    .tablaRegistroVehiculo td .avatar-lightbox {
      max-width: 100%;
      height: auto;
      display: block;
      overflow: hidden;
      text-overflow: ellipsis;
      white-space: nowrap;
    }

    td {
      max-width: 180px;
      /* Ajusta según tu diseño */
      white-space: nowrap;
      /* No permite salto de línea */
      overflow: hidden;
      /* Oculta lo que se desborda */
      text-overflow: ellipsis !important;
      /* Muestra los ... */
    }

    .tablaRegistroVehiculo td:nth-child(3),
    .tablaRegistroVehiculo th:nth-child(3) {
      text-align: center !important;
      vertical-align: middle !important;

    }

    .tablaRegistroVehiculo td:nth-child(6),
    .tablaRegistroVehiculo th:nth-child(6),
    .tablaRegistroVehiculo td:nth-child(7),
    .tablaRegistroVehiculo th:nth-child(7),
    .tablaRegistroVehiculo td:nth-child(8),
    .tablaRegistroVehiculo th:nth-child(8),
    .tablaRegistroVehiculo td:nth-child(10),
    .tablaRegistroVehiculo th:nth-child(10) {
      white-space: normal !important;
      /* Permite salto de línea */
      overflow-wrap: break-word !important;
      /* Salta suavemente si es muy largo */
      word-break: normal !important;
      /* No parte palabras */

    }
  }


    
</style>
<div class="page-wrapper">

  <div class="page-body">
    <div class="container-xl">
      <div class="row row-cards">

        <div class="col-sm-12 col-lg-2">
          <div class="card">

         
            <style>
              /* Normal (AZUL SOLIDO) */
              .btn-filtro-tipo {
                background: #0d6efd;
                /* azul bootstrap */
                color: white;
                border: 1px solid #0d6efd;
                padding: 6px 14px;
                border-radius: 6px;
                transition: s ease;
                font-weight: 500;
              }

              /* Hover del normal */
              .btn-filtro-tipo:hover {
                background: #0b5ed7;
                border-color: #0b5ed7;
                color: white;
              }

              /* ACTIVO (OUTLINE AZUL) */
              .btn-filtro-tipo.active {
                background: white !important;
                color: #0d6efd !important;
                border: 2px solid #0d6efd !important;
                font-weight: 600;
              }
            </style>


            <style>
              /* Normal: verde sólido */
              .btn-filtro-estado {
                background: #06c669;
                /* Verde */
                color: white;
                border: 1px solid #06c669;
                padding: 6px 14px;
                border-radius: 6px;
                transition: .25s ease;
                font-weight: 500;
              }

              /* Hover → blanco + borde verde + texto verde */
              .btn-filtro-estado:hover {
                background: white !important;
                color: #06c669 !important;
                border: 2px solid #06c669 !important;
              }

              /* ACTIVO → igual que hover pero más marcado */
              .btn-filtro-estado.active {
                background: white !important;
                color: #06c669 !important;
                border: 2px solid #06c669 !important;
                font-weight: 600;
              }
            </style>


            <div class="accordion" id="accordionEstado">
              <div class="accordion-item">

                <!-- ENCABEZADO -->
                <h4 class="accordion-header" id="headingEstado">
                  <button class="accordion-button collapsed"
                    type="button"
                    data-bs-toggle="collapse"
                    data-bs-target="#collapseEstado"
                    aria-expanded="false"
                    aria-controls="collapseEstado">
                    Filtro por Estado
                  </button>
                </h4>

                <!-- CONTENIDO (colapsado por defecto) -->
                <div id="collapseEstado"
                  class="accordion-collapse collapse"
                  aria-labelledby="headingEstado"
                  data-bs-parent="#accordionEstado">

                  <div class="accordion-body">

                    <div class="row justify-content-center gx-2 gy-2">

                      <div class="col-auto">
                        <button class="btn-filtro-estado" data-filtro=null>TODOS</button>
                      </div>

                      <div class="col-auto">
                        <button class="btn-filtro-estado" data-filtro="LIBRE">LIBRES</button>
                      </div>

                      <div class="col-auto">
                        <button class="btn-filtro-estado" data-filtro="ASIGNADO">ASIGNADOS</button>
                      </div>


                    </div>

                  </div>

                </div>

              </div>
            </div>

            <div class="accordion" id="accordionTipo">
              <div class="accordion-item">

                <!-- ENCABEZADO -->
                <h4 class="accordion-header" id="headingTipo">
                  <button class="accordion-button collapsed"
                    type="button"
                    data-bs-toggle="collapse"
                    data-bs-target="#collapseTipo"
                    aria-expanded="false"
                    aria-controls="collapseTipo">
                    Filtro por Tipo
                  </button>
                </h4>

                <!-- CONTENIDO (colapsado por defecto) -->
                <div id="collapseTipo"
                  class="accordion-collapse collapse"
                  aria-labelledby="headingTipo"
                  data-bs-parent="#accordionTipo">

                  <div class="accordion-body">

                    <div class="row justify-content-center gx-2 gy-2">

                      <div class="col-auto">
                        <button class="btn-filtro-tipo" data-filtro="TODOS">TODOS</button>
                      </div>


                      <div class="col-auto">
                        <button class="btn-filtro-tipo" data-filtro="4X4">4X4</button>
                      </div>

                      <div class="col-auto">
                        <button class="btn-filtro-tipo" data-filtro="4X4 PICKUP">4X4 PICKUP</button>
                      </div>

                      <div class="col-auto">
                        <button class="btn-filtro-tipo" data-filtro="CAMIONETA">CAMIONETA</button>
                      </div>


                    </div>

                  </div>

                </div>

              </div>
            </div>


            <!-- =======================
                    SCRIPT
              =========================== -->




            <!-- =======================
                            SCRIPT
                          =========================== -->
            <div class="card-box">
              <div class="container-fluid px-0">
                <div class="row justify-content-center gx-2 gy-2">
                  <button id="btn-restablecer-filtros2" class="btn btn-dark">
                    <i class="bi bi-arrow-counterclockwise"></i> Restablecer
                  </button>

                </div>
              </div>
            </div>
            <div class="card-box">
              <h6 class="text-center mb-3">Reportes</h6>
              <div class="container-fluid px-0">
                <div class="row justify-content-center gx-2 gy-2">
                  <div class="col-auto">
                    <button class="btn btn-red">PDF</button>
                  </div>
                  <div class="col-auto">
                    <button class="btn btn-teal">Excel</button>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="col-sm-12 col-lg-10">
          <div class="card">
            <div class="card-table">
              <div class="card-header">
                <div class="row w-full">
                  <div class="col">
                    <h3 class="card-title mb-0"><strong>Asignación de Vehiculos</strong></h3>
                  </div>
                  <div class="col-md-auto col-sm-12">
                    <div class="ms-auto d-flex flex-wrap btn-list">
                      <a href="#"
                        class="btn btn-primary px-5 mx-2 d-flex align-items-center gap-2"
                        data-bs-toggle="modal"
                        data-bs-target="#modal-report">
                        <svg xmlns="http://www.w3.org/2000/svg"
                          width="20" height="20"
                          viewBox="0 0 24 24"
                          fill="currentColor"
                          class="icon-tabler icon-tabler-library-plus">
                          <path d="M18.333 2a3.667 3.667 0 0 1 3.667 3.667v8.666a3.667 3.667 0 0 1 -3.667 3.667h-8.666a3.667 3.667 0 0 1 -3.667 -3.667v-8.666a3.667 3.667 0 0 1 3.667 -3.667zm-4.333 4a1 1 0 0 0 -1 1v2h-2a1 1 0 0 0 0 2h2v2a1 1 0 0 0 2 0v-2h2a1 1 0 0 0 0 -2h-2v-2a1 1 0 0 0 -1 -1" />
                          <path d="M3.517 6.391a1 1 0 0 1 .99 1.738c-.313 .178 -.506 .51 -.507 .868v10c0 .548 .452 1 1 1h10c.284 0 .405 -.088 .626 -.486a1 1 0 0 1 1.748 .972c-.546 .98 -1.28 1.514 -2.374 1.514h-10c-1.652 0 -3 -1.348 -3 -3v-10.002a3 3 0 0 1 1.517 -2.605" />
                        </svg>
                        REGISTRAR
                      </a>
                      <a data-bs-toggle="modal"
                        data-bs-target="#pdfModal"
                        data-pdf-url="pdf/pdf/reportelistavehiculo.php"
                        class="btn btn-icon btn-x">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                          stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                          class="icon icon-tabler icons-tabler-outline icon-tabler-file-type-pdf">
                          <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                          <path d="M14 3v4a1 1 0 0 0 1 1h4" />
                          <path d="M5 12v-7a2 2 0 0 1 2 -2h7l5 5v4" />
                          <path d="M5 18h1.5a1.5 1.5 0 0 0 0 -3h-1.5v6" />
                          <path d="M17 18h2" />
                          <path d="M20 15h-3v6" />
                          <path d="M11 15v6h1a2 2 0 0 0 2 -2v-2a2 2 0 0 0 -2 -2h-1z" />
                        </svg>
                      </a>


                    </div>
                  </div>
                </div>
              </div>
              <div id="advanced-table">
                <div class="table-responsive">

                  <table id="new-cons"
                    class="display table table-striped table-hover dt-responsive nowrap tablaRegistroVehiculo"
                    style="width: 100%">
                    <thead>
                      <tr>
                        <th class="col-id">ID</th>
                        <th class="col-foto">FOTO</th>
                        <th class="col-marca">MARCA</th>
                        <th class="col-placa">PLACA</th>
                        <th class="col-asignacion">ASIG.</th>
                        <th class="col-conductor">COND.<br>ASIG.</th>
                        <th class="col-tipo">TIPO</th>
                        <th class="col-modelo">MOD.</th>
                        <th class="col-codigo-patrimonial">COD.<br>PAT.</th>
                        <th class="col-jefe">JEFE<br>INM.</th>
                        <th class="col-acciones">Acciones</th>
                      </tr>
                    </thead>

                  </table>

                </div>

              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- END PAGE BODY -->
</div>
