<!-- Gestión de Usuarios -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0 fw-bold">👥 Gestión de Usuarios y Jerarquías</h4>
    <?php if (in_array($this->session->getUser()['role'], ['admin', 'supervisor_general', 'supervisor'])): ?>
        <button class="btn btn-lex-primary" onclick="abrirModalUsuario()">+ Nuevo Usuario</button>
    <?php endif; ?>
</div>

<!-- Filtros (Solo para Admin/Supervisor General) -->
<?php if (in_array($this->session->getUser()['role'], ['admin', 'supervisor_general'])): ?>
<div class="card bg-dark border-secondary p-3 mb-4">
    <form method="GET" action="index.php" class="row g-2">
        <input type="hidden" name="action" value="usuarios">
        <div class="col-md-3">
            <select name="rol" class="form-select bg-dark text-white border-secondary">
                <option value="">Todos los roles</option>
                <option value="gestor" <?= ($_GET['rol'] ?? '') === 'gestor' ? 'selected' : '' ?>>Gestores</option>
                <option value="supervisor" <?= ($_GET['rol'] ?? '') === 'supervisor' ? 'selected' : '' ?>>Supervisores</option>
                <option value="supervisor_general" <?= ($_GET['rol'] ?? '') === 'supervisor_general' ? 'selected' : '' ?>>Sup. General</option>
                <?php if (in_array($this->session->getUser()['role'], ['admin'])): ?>
                <option value="admin" <?= ($_GET['rol'] ?? '') === 'admin' ? 'selected' : '' ?>>Admin</option>
                <?php endif;?>
            </select>
        </div>
        <div class="col-md-3">
            <input type="text" name="q" class="form-control bg-dark text-white border-secondary" placeholder="Buscar nombre o usuario" value="<?= htmlspecialchars($_GET['q'] ?? '') ?>">
        </div>
        <div class="col-md-2">
            <button type="submit" class="btn btn-outline-light w-100">🔍 Filtrar</button>
        </div>
    </form>
</div>
<?php endif; ?>

<div class="table-responsive">
    <table class="table table-dark table-hover align-middle mb-0">
        <thead>
            <tr>
                <th>Nombre</th>
                <th>Usuario</th>
                <th>Rol</th>
                <th>Supervisor Asignado</th>
                <th>Estado</th>
                <th class="text-center">Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($usuarios as $u):
                if ($u['rol']=='admin' && $this->session->getUser()['role']!=='admin'){
                    continue;
                }
                $supNombre = $u['supervisor_id'] ? ($this->db->query("SELECT nombre FROM usuarios WHERE id = {$u['supervisor_id']}")->fetchColumn() ?: '-') : '-';
            ?>
            <tr>
                <td class="fw-medium"><?= htmlspecialchars($u['nombre']) ?></td>
                <td><?= htmlspecialchars($u['usuario']) ?></td>
                <td><span class="badge bg-secondary"><?= ucfirst($u['rol']) ?></span></td>
                <td><?= htmlspecialchars($supNombre) ?></td>
                <td>
                    <span class="badge <?= $u['activo'] ? 'bg-success' : 'bg-danger' ?> cursor-pointer" style="cursor:pointer" onclick="toggleActivo(<?= $u['id'] ?>)">
                        <?= $u['activo'] ? '✅ Activo' : '❌ Inactivo' ?>
                    </span>
                </td>
                <td class="text-center">
                    <button class="btn btn-sm btn-outline-warning" onclick='editarUsuario(<?= json_encode($u) ?>)'>✏️ Editar</button>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if(empty($usuarios)): ?>
                <tr><td colspan="6" class="text-center text-secondary py-4">No se encontraron usuarios.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- MODAL USUARIO -->
<div class="modal fade" id="modal-usuario" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content bg-dark border-secondary">
            <form id="form-usuario">
                <div class="modal-header border-secondary">
                    <h5 class="modal-title">👤 Datos del Usuario</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="id" id="u-id">
                    <div class="mb-3">
                        <label class="form-label small text-secondary">Nombre Completo *</label>
                        <input type="text" name="nombre" id="u-nombre" class="form-control bg-dark text-white border-secondary" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small text-secondary">Usuario *</label>
                        <input type="text" name="usuario" id="u-usuario" class="form-control bg-dark text-white border-secondary" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small text-secondary">Contraseña <span id="pass-label"></span></label>
                        <input type="password" name="password" id="u-password" class="form-control bg-dark text-white border-secondary">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small text-secondary">Rol *</label>
                        <select name="rol" id="u-rol" class="form-select bg-dark text-white border-secondary" required>
                            <option value="gestor">Gestor</option>
                            <option value="supervisor">Supervisor</option>
                            <option value="supervisor_general">Supervisor General</option>
                            <?php if($this->session->getUser()['role']=='admin'):?>
                            <option value="admin">Administrador</option>
                            <?php endif;?>
                        </select>
                    </div>
                    <div class="mb-3" id="div-supervisor" style="display:none;">
                        <label class="form-label small text-secondary">Asignar a Supervisor *</label>
                        <select name="supervisor_id" id="u-supervisor" class="form-select bg-dark text-white border-secondary">
                            <option value="">-- Sin supervisor --</option>
                            <?php
                            $supervisores = $this->usuarioDao->findSupervisores();
                            foreach($supervisores as $s) echo "<option value='{$s['id']}'>".htmlspecialchars($s['nombre'])."</option>";
                            ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer border-secondary">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-lex-primary">💾 Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- SCRIPT SEGURO -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Esperar a que Bootstrap cargue
    if (typeof bootstrap === 'undefined') {
        console.warn('Esperando Bootstrap...');
        setTimeout(arguments.callee, 100);
        return;
    }

    const modalUsuario = new bootstrap.Modal(document.getElementById('modal-usuario'));
    const rolSelect = document.getElementById('u-rol');
    const divSup = document.getElementById('div-supervisor');
    const passInput = document.getElementById('u-password');
    const passLabel = document.getElementById('pass-label');
    const formUsuario = document.getElementById('form-usuario');

    function actualizarCamposRol() {
        if (!rolSelect || !divSup) return;
        divSup.style.display = (rolSelect.value === 'gestor') ? 'block' : 'none';
    }

    if (rolSelect) rolSelect.addEventListener('change', actualizarCamposRol);

    // Exponer funciones al ámbito global para los onclick del HTML
    window.abrirModalUsuario = function() {
        if (!formUsuario || !passInput || !passLabel) return;
        formUsuario.reset();
        document.getElementById('u-id').value = '';
        passInput.required = true;
        passLabel.textContent = '*';
        actualizarCamposRol();
        modalUsuario.show();
    };

    window.editarUsuario = function(data) {
        if (!formUsuario || !passInput || !passLabel) return;
        document.getElementById('u-id').value = data.id;
        document.getElementById('u-nombre').value = data.nombre;
        document.getElementById('u-usuario').value = data.usuario;
        document.getElementById('u-rol').value = data.rol;
        document.getElementById('u-supervisor').value = data.supervisor_id || '';
        passInput.required = false;
        passLabel.textContent = '(dejar vacío para mantener)';
        actualizarCamposRol();
        modalUsuario.show();
    };

    if (formUsuario) {
        formUsuario.addEventListener('submit', function(e) {
            e.preventDefault();
            const fd = new FormData(e.target);
            fetch('?action=guardar_usuario', { method:'POST', body:fd })
                .then(r=>r.json())
                .then(res => {
                    if(res.success) { modalUsuario.hide(); location.reload(); }
                    else alert('❌ '+res.msg);
                });
        });
    }

    window.toggleActivo = function(id) {
        if(!confirm('¿Cambiar estado de actividad del usuario?')) return;
        const fd = new FormData();
        fd.append('id', id);
        fetch('?action=toggle_usuario', { method:'POST', body:fd })
            .then(r=>r.json())
            .then(res => {
                if(res.success) location.reload();
                else alert('❌ '+res.msg);
            });
    };
});
</script>