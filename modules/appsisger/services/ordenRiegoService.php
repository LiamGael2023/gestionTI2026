<?php

class OrdenRiegoService {

    public static function mapear($data){

        return [
            "sectorRiego" => $data["Sector"] ?? "",
            "nroRequerimiento" => $data["Periodo"] ?? "",

            "fechaInicioPeriodo" => isset($data["fechaInicio"]) && $data["fechaInicio"] instanceof DateTime
    ? $data["fechaInicio"]->format('Y-m-d')
    : "",

"fechaFinalPeriodo" => isset($data["fechaFin"]) && $data["fechaFin"] instanceof DateTime
    ? $data["fechaFin"]->format('Y-m-d')
    : "",

            "nroOrden" => $data["OrdRie_Numero"] ?? "",
            "nroRecibo" => $data["Rec_Numero"] ?? "",
            "usuario" => $data["Con_Nombres"] ?? "",

            "canalDerivacion" => $data["CanDerivacion_Descripcion"] ?? "",
            "canalRiego" => $data["Can_Descripcion"] ?? "",

            "fechaInicioRiego" => $data["sOrdRie_FechaInicioFecha_Texto"] ?? "",
            "fechaFinRiego" => $data["sOrdRie_FechaFinFecha_Texto"] ?? "",

            "cultivo" => $data["UsoTierra"] ?? "",
            "areaRegar" => $data["AreaRegar"] ?? "",

            "recorrido" => $data["OrdRie_TiempoRecorrido_H"] ?? "",
            "despuesde" => $data["Con_Nombres_Anterior"] ?? "",

            "UC" => $data["AmbOpe_CodigoCatastral"] ?? "",

            "caudalNeto" => $data["UniRot_CaudalNeto"] ?? "",
            "tiempoRiego" => $data["OrdRie_TiempoRiego_H"] ?? "",
            "volumenSolcitado" => $data["EntAgu_VolumenTeorico"] ?? "",

            "horaInicioRiego" => $data["sOrdRie_FechaInicioHora"] ?? "",
            "horaFinalRiego" => $data["sOrdRie_FechaFinHora"] ?? "",

            "fechaemision" => $data["sFechaEmision"] ?? "",
            "comision" => $data["AmbOrgUsu_Descripcion"] ?? ""
        ];
    }

}