<nav class="navbar navbar-expand-lg navbar-dark navbar-lex px-4 sticky-top">
    <a class="navbar-brand fw-bold d-flex align-items-center gap-2" href="?action=dashboard">
        <div class="brand-icon-sm" style="width:32px;height:32px;background:linear-gradient(135deg,#4e73df,#2c4a99);border-radius:8px;display:flex;align-items:center;justify-content:center;">
            <!-- Icono L -->
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" stroke="#fff" stroke-width="2"><path d="M4 4v10h10"/></svg>
        </div>
        <span>LEX <span class="text-info">360</span></span>
    </a>

    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav">
        <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="mainNav">
        <ul class="navbar-nav me-auto">
            <li class="nav-item"><a class="nav-link" href="?action=dashboard">🏠 Inicio</a></li>

            <!-- ✅ MENÚ DE REPORTES (VISIBLE SOLO PARA ROLES CON PERMISO) -->
            <?php 
            $rol = $user['rol'] ?? $user['role'] ?? '';
            if (in_array($rol, ['admin', 'supervisor', 'supervisor_general','gestor'])): 
            ?>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="navbarDropdownReportes" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        📊 Reportes
                    </a>
                    <ul class="dropdown-menu dropdown-menu-dark" aria-labelledby="navbarDropdownReportes">
                        
                        <!-- Opción 1: Gestión General (Ya existía) -->
                        <li>
                            <a class="dropdown-item" href="?action=reportes_gestiones">
                                📞 Gestión de Llamadas
                            </a>
                        </li>
                        
                        <!-- Opción 2: Pagos (Nuevo) -->
                        <li>
                            <a class="dropdown-item" href="?action=reportes_pagos">
                                💳 Pagos y Pendientes
                            </a>
                        </li>
                        
                        <!-- Opción 3: Promesas y Seguimiento (Nuevo) -->
                        <li>
                            <a class="dropdown-item" href="?action=reportes_promesas">
                                🫱🏼‍🫲🏼 Promesas y Seguimiento
                            </a>
                        </li>
                        
                        <li><hr class="dropdown-divider"></li>
                        
                        <!-- Opción adicional: Panel o Dashboard -->
                        <li>
                            <a class="dropdown-item" href="?action=clientes">
                                🔄 Volver a Gestión
                            </a>
                        </li>
                    </ul>
                </li>
            <?php endif; ?>            

            <!-- MENÚ SEGÚN ROL -->
            <?php if ($user['role'] === 'gestor'): ?>
                <li class="nav-item"><a class="nav-link" href="?action=clientes">👥 Mis Clientes</a></li>
                <li class="nav-item"><a class="nav-link" href="?action=asistencia">🕒 Asistencia</a></li>

                

            <?php elseif ($user['role'] === 'supervisor'): ?>
                <li class="nav-item"><a class="nav-link" href="?action=clientes">👥 Equipo</a></li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">📥 Cargas</a>
                    <ul class="dropdown-menu dropdown-menu-dark">
                        <li><a class="dropdown-item" href="?action=carga_clientes">Cargar Clientes</a></li>
                        <li><a class="dropdown-item" href="?action=carga_gestiones">Cargar Gestiones</a></li>
                    </ul>
                </li>
                <li class="nav-item"><a class="nav-link" href="?action=usuarios">👤 Usuarios</a></li>

            <?php elseif (in_array($user['role'], ['supervisor_general', 'admin'])): ?>
                <li class="nav-item"><a class="nav-link" href="?action=clientes">📞 Operación Global</a></li>
                <li class="nav-item"><a class="nav-link" href="?action=auditoria">📄 Auditoría</a></li>
                <li class="nav-item"><a class="nav-link" href="?action=mis_promesas">🤝 Mis Promesas</a></li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">📥 Cargas</a>
                    <ul class="dropdown-menu dropdown-menu-dark">
                        <li><a class="dropdown-item" href="?action=carga_clientes">Cargar Clientes</a></li>
                        <li><a class="dropdown-item" href="?action=carga_gestiones">Cargar Gestiones</a></li>
                    </ul>
                </li>
                <li class="nav-item"><a class="nav-link" href="?action=validar_pagos">💳 Validar Pagos</a></li>
                <li class="nav-item"><a class="nav-link" href="?action=migrar_clientes">♻️ Trasladar</a></li>
                <li class="nav-item"><a class="nav-link" href="?action=backup">💾 Backup</a></li>
                <?php if ($rol=='admin'):?>
                <li class="nav-item"><a class="nav-link" href="?action=configuracion">️⚙️ Configuración</a></li>
                <?php endif;?>
                <li class="nav-item"><a class="nav-link" href="?action=usuarios">👤 Usuarios</a></li>
            <?php endif; ?>
        </ul>

        <!-- LADO DERECHO: TEMA Y USUARIO -->
        <div class="d-flex align-items-center gap-3">
            <!-- Botón Tema -->
            <button id="themeToggle" class="btn btn-sm btn-outline-secondary" title="Cambiar tema">🌤️</button>
            
            <!-- Dropdown Usuario -->
            <div class="dropdown">
                <button class="btn btn-sm btn-outline-light dropdown-toggle" type="button" data-bs-toggle="dropdown">
                    👤 <?= htmlspecialchars($user['name']) ?>
                </button>
                <ul class="dropdown-menu dropdown-menu-dark dropdown-menu-end">
                    <li><span class="dropdown-item-text small text-secondary">Rol: <?= ucfirst(str_replace('_', ' ', $user['role'])) ?></span></li>
                    <li><hr class="dropdown-divider"></li>
                    <!-- ENLACE AL MODAL -->
                    <li><a class="dropdown-item text-warning" href="#" data-bs-toggle="modal" data-bs-target="#modalCambiarClave">🔑 Cambiar Clave</a></li>
                    <li><a class="dropdown-item text-danger" href="?action=logout">🚪 Cerrar Sesión</a></li>
                </ul>
            </div>
        </div>
    </div>
</nav>

<!-- ========================================== -->
<!-- MODAL CAMBIAR CLAVE (Oculto por defecto)   -->
<!-- ========================================== -->
<div class="modal fade" id="modalCambiarClave" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content bg-dark border-secondary">
            <div class="modal-header border-secondary">
                <h5 class="modal-title text-white">🔑 Cambiar Contraseña</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="formCambiarClave">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small text-secondary">Contraseña Actual *</label>
                        <input type="password" name="actual" class="form-control bg-dark text-white border-secondary" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small text-secondary">Nueva Contraseña *</label>
                        <input type="password" name="nueva" class="form-control bg-dark text-white border-secondary" minlength="6" required>
                    </div>
                    <div class="mb-0">
                        <label class="form-label small text-secondary">Confirmar Nueva *</label>
                        <input type="password" name="confirmar" class="form-control bg-dark text-white border-secondary" minlength="6" required>
                    </div>
                </div>
                <div class="modal-footer border-secondary">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-lex-primary btn-sm">💾 Actualizar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- SCRIPTS DEL NAVBAR -->
<script>
// 1. Toggle Tema Oscuro/Claro
document.getElementById('themeToggle')?.addEventListener('click', () => {
    const html = document.documentElement;
    const current = html.getAttribute('data-bs-theme');
    const newTheme = current === 'dark' ? 'light' : 'dark';
    html.setAttribute('data-bs-theme', newTheme);
    localStorage.setItem('theme', newTheme); // Guardar preferencia
});

// 2. Lógica del Modal de Cambiar Clave
document.getElementById('formCambiarClave')?.addEventListener('submit', function(e) {
    e.preventDefault();
    const btn = this.querySelector('button[type="submit"]');
    const originalText = btn.innerHTML;
    btn.disabled = true; 
    btn.innerHTML = '⏳ Guardando...';

    fetch('?action=cambiar_clave', { method: 'POST', body: new FormData(this) })
        .then(r => r.json())
        .then(res => {
            btn.disabled = false; 
            btn.innerHTML = originalText;
            if (res.success) {
                bootstrap.Modal.getInstance(document.getElementById('modalCambiarClave')).hide();
                alert('✅ ' + res.msg);
                this.reset(); // Limpiar formulario
            } else {
                alert('❌ ' + res.msg);
            }
        })
        .catch(() => {
            btn.disabled = false; 
            btn.innerHTML = originalText;
            alert('❌ Error de conexión');
        });
});
</script>