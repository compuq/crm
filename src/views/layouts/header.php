<!DOCTYPE html>
<html lang="es" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="public/images/call.png">
    <title><?= $pageTitle ?? 'LEX 360 CRM' ?></title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    
<style>
    /* ✅ Dejar que Bootstrap controle el fondo global */
    body { 
        font-family: 'Segoe UI', system-ui, sans-serif; 
    }

    /* ✅ Forzar navbar siempre oscura (opcional, estilo dashboard) */
    .navbar-lex { 
        background-color: #141822 !important; 
        border-bottom: 1px solid #2a2f3d; 
    }
    .navbar-lex .nav-link { color: #e4e6eb; }
    
    /* ✅ Botones personalizados */
    .btn-lex-primary { 
        background: linear-gradient(135deg, #4e73df, #3658b3); 
        border: none; color: white; font-weight: 600; 
    }
    .btn-lex-primary:hover { 
        background: linear-gradient(135deg, #3658b3, #2a4694); 
        color: white;
    }

    /* ✅ Cards y Inputs adaptables */
    .card { 
        background-color: var(--bs-card-bg, #1c212e); 
        border-color: var(--bs-border-color, #2a2f3d); 
    }
    
    /* Inputs específicos para modo oscuro (si se desea) */
    [data-bs-theme="dark"] .form-control,
    [data-bs-theme="dark"] .form-select { 
        background-color: #141822; 
        border-color: #2a2f3d; 
        color: #e4e6eb;
    }
/* ===== MODO CLARO FORZADO ===== */

[data-bs-theme="light"] .bg-dark {
    background-color: #fff !important;
}

[data-bs-theme="light"] .text-white,
[data-bs-theme="light"] .text-light {
    color: #212529 !important;
}

[data-bs-theme="light"] .card.bg-dark,
[data-bs-theme="light"] .modal-content.bg-dark {
    background-color: #fff !important;
    color: #212529 !important;
}

[data-bs-theme="light"] .form-control.bg-dark,
[data-bs-theme="light"] .form-select.bg-dark {
    background-color: #fff !important;
    color: #212529 !important;
    border-color: #ced4da !important;
}

[data-bs-theme="light"] .table-dark {
    --bs-table-bg: #fff !important;
    --bs-table-color: #212529 !important;
    --bs-table-border-color: #dee2e6 !important;
}

[data-bs-theme="light"] .table-dark th,
[data-bs-theme="light"] .table-dark td {
    color: #212529 !important;
}   
[data-bs-theme="dark"] .stat-title.text-success {
    color: #75b798 !important;
}

[data-bs-theme="light"] .stat-title.text-success {
    color: #198754 !important;
} 

[data-bs-theme="light"] .table-dark {
    --bs-table-bg: #fff;
    --bs-table-striped-bg: #f8f9fa;
    --bs-table-striped-color: #212529;
    --bs-table-color: #212529;
}

[data-bs-theme="dark"] .mini-card {
    background: linear-gradient(145deg, #10151f, #111827);
}

[data-bs-theme="light"] .mini-card {
    background: linear-gradient(145deg, #ffffff, #f8f9fa);
    border: 1px solid #dee2e6;
}

body {
    transition: background-color 1s ease,
                color 1s ease;
}

.card,
.modal-content,
.table,
.form-control,
.form-select {
    transition: background-color 1s ease,
                color 1s ease,
                border-color 2s ease;
}    

</style>
<?= $extraStyles ?? '' ?>
</head>
<body>