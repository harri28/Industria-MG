<?php
$page_title      = 'Cuentas por Pagar';
$page_breadcrumb = 'Finanzas › Cuentas por Pagar';
require_once __DIR__ . '/../../includes/header.php';
?>

<div style="display:flex;flex-direction:column;align-items:center;justify-content:center;
            min-height:60vh;gap:18px;color:var(--text-muted);text-align:center">
    <i class="fa fa-file-invoice" style="font-size:4rem;opacity:.18;color:var(--primary)"></i>
    <div>
        <div style="font-size:1.25rem;font-weight:700;color:var(--text-primary);margin-bottom:6px">
            Módulo Cuentas por Pagar
        </div>
        <div style="font-size:.9rem">
            Control de obligaciones y pagos a proveedores. Próximamente disponible.
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
