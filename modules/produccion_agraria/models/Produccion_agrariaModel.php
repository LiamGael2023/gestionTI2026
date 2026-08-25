<?php
/**
 * Produccion_agrariaModel — Stub de compatibilidad con el router dinámico.
 *
 * Este modelo NO contiene lógica de negocio. Cada sub-controlador del módulo
 * (InventarioController, PuntoVentaController, BandejaController, etc.) instancia
 * su propio modelo específico (InventarioModel, PuntoVentaModel, BandejaModel…).
 *
 * Se mantiene únicamente porque Produccion_agrariaController lo instancia antes
 * de redirigir al sub-controlador correspondiente.
 */
class Produccion_agrariaModel {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }
}