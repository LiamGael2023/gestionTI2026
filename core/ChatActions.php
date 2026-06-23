<?php
/**
 * ChatActions - Lista centralizada de acciones AJAX del chatbot
 * 
 * Referenciada por:
 *   - index.php (raíz): $acciones_ajax
 *   - Produccion_agrariaController.php: $acciones_ajax_bandeja
 *
 * REGLA: al agregar una nueva tool, solo editar este archivo.
 */
return [
    'chat_enviar',
    'tool_stock',
    'tool_ventas',
    'tool_proformas',
    'tool_vouchers',
    'tool_productos',
    'tool_clientes',
    'tool_mermas',
    'tool_kardex',
    'tool_top_productos',
    'tool_valorizacion',
    'tool_ventas_mes',
    'tool_vouchers_saldo',
    'tool_grafico',
    'tool_resumen',
    'tool_comparativa',
    'tool_buscar',
    'tool_recomendaciones',
    'tool_metricas',
    'tool_detalle_producto',
];
