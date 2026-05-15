<!DOCTYPE html>
<html lang="es" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
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
</style>
<?= $extraStyles ?? '' ?>
</head>
<body>