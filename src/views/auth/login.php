<!DOCTYPE html>
<html lang="es" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LEX 360 | Acceso</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { 
            background-color: #0b0e14; 
            min-height: 100vh; 
            display: flex; 
            align-items: center; 
            justify-content: center;
            font-family: 'Segoe UI', system-ui, sans-serif;
        }
        .login-card { 
            max-width: 420px; 
            width: 100%; 
            border: 1px solid #2a2f3d; 
            border-radius: 16px; 
            background: #141822; 
            box-shadow: 0 12px 40px rgba(0,0,0,0.6); 
            padding: 2.5rem;
        }
        .brand-icon { 
            width: 56px; 
            height: 56px; 
            background: linear-gradient(135deg, #4e73df, #2c4a99); 
            border-radius: 14px; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            margin: 0 auto 1.2rem;
        }
        .brand-icon svg { stroke: #fff; width: 28px; height: 28px; }
        .form-control { 
            background-color: #1c212e; 
            border-color: #2a2f3d; 
            color: #e4e6eb;
        }
        .form-control:focus { 
            background-color: #222838; 
            border-color: #4e73df; 
            box-shadow: 0 0 0 0.25rem rgba(78,115,223,0.25);
            color: #fff;
        }
        .btn-lex { 
            background: linear-gradient(135deg, #4e73df, #3658b3); 
            border: none; 
            font-weight: 600; 
            letter-spacing: 0.3px;
            transition: all 0.2s;
        }
        .btn-lex:hover { 
            background: linear-gradient(135deg, #3658b3, #2a4694); 
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(78,115,223,0.3);
        }
        .footer-copy { color: #5a6075; font-size: 0.8rem; }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="text-center mb-4">
            <div class="brand-icon">
                <!-- Ícono placeholder: Usuario + Lupa (reemplazar por tu SVG/base64 final) -->
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="11" cy="11" r="8"></circle>
                    <path d="m21 21-4.35-4.35"></path>
                    <path d="M11 8v6"></path>
                    <path d="M8 11h6"></path>
                </svg>
            </div>
            <h2 class="fw-bold text-white mb-1">LEX 360</h2>
            <p class="text-secondary small mb-0">Sistema de Gestión de Cartera</p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger d-flex align-items-center py-2 small" role="alert">
                <span class="me-2">⚠️</span> <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form action="?action=do_login" method="POST" autocomplete="off">
            <div class="mb-3">
                <label for="usuario" class="form-label small text-secondary">Usuario</label>
                <input type="text" class="form-control" id="usuario" name="usuario" required autofocus>
            </div>
            <div class="mb-4">
                <label for="password" class="form-label small text-secondary">Contraseña</label>
                <input type="password" class="form-control" id="password" name="password" required>
            </div>
            <button type="submit" class="btn btn-primary btn-lex w-100 py-2">
                Iniciar Sesión
            </button>
        </form>

        <div class="text-center mt-4">
            <p class="footer-copy mb-0">© <?= date('Y') ?> Compuq Tech | v1.0</p>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>