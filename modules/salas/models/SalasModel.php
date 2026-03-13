<?php
/**
 * SalasModel.php  -  Facade / Punto de entrada unico del modulo.
 *
 * Delega cada responsabilidad a su repositorio especializado manteniendo
 * la API publica intacta: el controlador y el ajax_handler no requieren cambios.
 *
 * Principios SOLID aplicados:
 *   SRP  - cada repositorio tiene una responsabilidad unica.
 *   OCP  - se puede extender anadiendo repositorios sin modificar este facade.
 *   DIP  - el controlador depende de esta abstraccion, no de implementaciones.
 *
 * Proyecto Especial Chavimochic (PECH) - GestionTI v1.0
 */

require_once __DIR__ . '/BaseModel.php';
require_once __DIR__ . '/SedesRepository.php';
require_once __DIR__ . '/SalasRepository.php';
require_once __DIR__ . '/EquiposRepository.php';
require_once __DIR__ . '/DisponibilidadRepository.php';
require_once __DIR__ . '/ReservasRepository.php';
require_once __DIR__ . '/AutorizacionRepository.php';
require_once __DIR__ . '/EstadisticasRepository.php';

class SalasModel extends BaseModel
{
    private $sedesRepo;
    private $salasRepo;
    private $equiposRepo;
    private $disponibilidadRepo;
    private $reservasRepo;
    private $autorizacionRepo;
    private $estadisticasRepo;

    public function __construct($db)
    {
        parent::__construct($db);
        $this->sedesRepo          = new SedesRepository($db);
        $this->salasRepo          = new SalasRepository($db);
        $this->equiposRepo        = new EquiposRepository($db);
        $this->disponibilidadRepo = new DisponibilidadRepository($db);
        $this->reservasRepo       = new ReservasRepository($db);
        $this->autorizacionRepo   = new AutorizacionRepository($db);
        $this->estadisticasRepo   = new EstadisticasRepository($db);
    }

    // =========================================================================
    // SEDES - delega a SedesRepository
    // =========================================================================

    public function getSedes(): array                         { return $this->sedesRepo->getSedes(); }
    public function getAllSedes(): array                      { return $this->sedesRepo->getAllSedes(); }
    public function getSedeById(int $id): ?array              { return $this->sedesRepo->getSedeById($id); }
    public function guardarSede(array $datos)                 { return $this->sedesRepo->guardarSede($datos); }
    public function toggleSede(int $id, int $activo): bool    { return $this->sedesRepo->toggleSede($id, $activo); }

    // =========================================================================
    // SALAS - delega a SalasRepository
    // =========================================================================

    public function getSalasBySede(int $id_sede): array       { return $this->salasRepo->getSalasBySede($id_sede); }
    public function getAllSalas(): array                      { return $this->salasRepo->getAllSalas(); }
    public function getSalaById(int $id): ?array              { return $this->salasRepo->getSalaById($id); }
    public function guardarSala(array $datos)                 { return $this->salasRepo->guardarSala($datos); }
    public function toggleSala(int $id, int $activo): bool    { return $this->salasRepo->toggleSala($id, $activo); }
    public function guardarFotoSala(int $id, string $ruta): bool { return $this->salasRepo->guardarFotoSala($id, $ruta); }

    // =========================================================================
    // EQUIPOS - delega a EquiposRepository
    // =========================================================================

    public function getEquiposBySala(int $id_sala): array     { return $this->equiposRepo->getEquiposBySala($id_sala); }
    public function getAllEquipos(): array                    { return $this->equiposRepo->getAllEquipos(); }
    public function getEquipoById(int $id): ?array            { return $this->equiposRepo->getEquipoById($id); }
    public function guardarEquipo(array $datos)               { return $this->equiposRepo->guardarEquipo($datos); }
    public function toggleEquipo(int $id, int $activo): bool  { return $this->equiposRepo->toggleEquipo($id, $activo); }

    // =========================================================================
    // DISPONIBILIDAD Y CALENDARIO - delega a DisponibilidadRepository
    // =========================================================================

    public function verificarDisponibilidad(
        int    $id_sala,
        string $fecha,
        string $hora_inicio,
        string $hora_fin,
        int    $excluir_id = 0
    ): array {
        return $this->disponibilidadRepo->verificarDisponibilidad(
            $id_sala, $fecha, $hora_inicio, $hora_fin, $excluir_id
        );
    }

    public function getEventosCalendar(int $id_sala, string $fecha_inicio, string $fecha_fin): array
    {
        return $this->disponibilidadRepo->getEventosCalendar($id_sala, $fecha_inicio, $fecha_fin);
    }

    public function getEventosCronograma(
        string $fecha_inicio,
        string $fecha_fin,
        ?int   $id_sede = null,
        ?int   $id_sala = null
    ): array {
        return $this->disponibilidadRepo->getEventosCronograma($fecha_inicio, $fecha_fin, $id_sede, $id_sala);
    }

    // =========================================================================
    // RESERVAS (SOLICITANTE) - delega a ReservasRepository
    // =========================================================================

    public function crearReserva(array $datos)                                           { return $this->reservasRepo->crearReserva($datos); }
    public function getMisReservas(int $id_usuario): array                               { return $this->reservasRepo->getMisReservas($id_usuario); }
    public function getReservaDetalle(int $id_reserva, int $id_usuario = 0): ?array      { return $this->reservasRepo->getReservaDetalle($id_reserva, $id_usuario); }
    public function editarReserva(int $id_reserva, array $datos, int $id_usuario): array { return $this->reservasRepo->editarReserva($id_reserva, $datos, $id_usuario); }
    public function cancelarReserva(int $id_reserva, int $id_usuario): array             { return $this->reservasRepo->cancelarReserva($id_reserva, $id_usuario); }

    // =========================================================================
    // AUTORIZACION - delega a AutorizacionRepository
    // =========================================================================

    public function getReservasPendientes(): array                                              { return $this->autorizacionRepo->getReservasPendientes(); }
    public function aprobarReserva(int $id_reserva, int $id_autorizador): array                { return $this->autorizacionRepo->aprobarReserva($id_reserva, $id_autorizador); }
    public function rechazarReserva(int $id_reserva, int $id_autorizador, string $obs = ''): array { return $this->autorizacionRepo->rechazarReserva($id_reserva, $id_autorizador, $obs); }
    public function cancelarReservasVencidas(): int                                             { return $this->autorizacionRepo->cancelarReservasVencidas(); }
    public function getHistorial(array $filtros = []): array                                    { return $this->autorizacionRepo->getHistorial($filtros); }
    public function getHistorialByReserva(int $id_reserva): array                              { return $this->autorizacionRepo->getHistorialByReserva($id_reserva); }

    // =========================================================================
    // ESTADISTICAS - delega a EstadisticasRepository
    // =========================================================================

    public function getEstadisticasSolicitante(int $id_usuario): array { return $this->estadisticasRepo->getEstadisticasSolicitante($id_usuario); }
    public function getEstadisticasGlobales(): array                   { return $this->estadisticasRepo->getEstadisticasGlobales(); }
}