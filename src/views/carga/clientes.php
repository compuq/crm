<?php if (isset($_SESSION['flash_message'])): ?>
    <div class="alert alert-<?= $_SESSION['flash_type'] ?? 'info' ?> alert-dismissible fade show" role="alert">
        <?= $_SESSION['flash_message'] ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php 
    // Limpiar flash message después de mostrar
    unset($_SESSION['flash_message'], $_SESSION['flash_type']); 
    ?>
<?php endif; ?>
<!-- Carga Masiva de Clientes -->
<div class="card bg-dark border-secondary mb-4">
    <div class="card-header border-secondary d-flex justify-content-between align-items-center">
        <h5 class="mb-0 text-white">📥 Carga Masiva de Clientes</h5>
        <a href="?action=descargar_plantilla&cartera_id=<?= $carteraId ?? '' ?>" 
           class="btn btn-outline-info btn-sm"
           <?= empty($carteraId) ? 'disabled style="pointer-events:none;opacity:0.5;"' : '' ?>>
            📥 Descargar Plantilla XLSX
        </a>
    </div>
    <div class="card-body">
        <p class="text-secondary small mb-3">
            Suba un archivo <strong>Excel (.xlsx)</strong>. La primera fila debe contener los encabezados exactos.
        </p>
        
        <?php if (!empty($mensaje)): ?>
            <div class="alert alert-<?= $tipoMensaje ?> alert-dismissible fade show" role="alert">
                <?= $mensaje ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <form action="?action=importar_clientes" method="POST" enctype="multipart/form-data">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label text-secondary">Seleccionar Cartera *</label>
                    <select name="id_cartera" id="selectCartera" class="form-select bg-dark text-white border-secondary" required>
                        <option value="">-- Seleccione --</option>
                        <?php foreach ($carteras as $c): ?>
                            <option value="<?= $c['id'] ?>" <?= ($carteraId ?? '') == $c['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($c['nombre_cartera']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-md-6">
                    <label class="form-label text-secondary">Asignar a Gestor (Opcional)</label>
                    <select name="id_gestor_asignado" class="form-select bg-dark text-white border-secondary">
                        <option value="">-- Por defecto: Tú (<?= htmlspecialchars($user['name']) ?>) --</option>
                        <?php
                        if (in_array($user['role'], ['admin', 'supervisor_general', 'supervisor'])) {
                            $stmt = $this->db->prepare("
                                SELECT id, nombre, usuario 
                                FROM usuarios 
                                WHERE rol = 'gestor' 
                                AND activo = true 
                                AND (supervisor_id = :uid OR :role IN ('admin', 'supervisor_general'))
                                ORDER BY nombre
                            ");
                            $stmt->execute(['uid' => $user['id'], 'role' => $user['role']]);
                            foreach ($stmt->fetchAll() as $gestor) {
                                echo "<option value='{$gestor['id']}'>{$gestor['nombre']} (@{$gestor['usuario']})</option>";
                            }
                        }
                        ?>
                    </select>
                    <small class="text-secondary d-block mt-1">
                        Si el XLSX tiene columna de gestor, esa tiene prioridad.
                    </small>
                </div>
                
                <div class="col-12">
                    <label class="form-label text-secondary">Archivo Excel (.xlsx) *</label>
                    <input type="file" name="archivo_csv" class="form-control bg-dark text-white border-secondary" accept=".xlsx, .xls" required>
                    <small class="text-secondary">Formato nativo de Excel. No use CSV.</small>
                </div>
                
                <div class="col-12">
                    <button type="submit" class="btn btn-lex-primary px-4">
                        🚀 Iniciar Carga
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
// Actualizar enlace de plantilla al cambiar cartera
document.getElementById('selectCartera')?.addEventListener('change', function() {
    const cid = this.value;
    const btnPlantilla = document.querySelector('.card-header a[href*="descargar_plantilla"]');
    if (btnPlantilla) {
        if (cid) {
            btnPlantilla.href = `?action=descargar_plantilla&cartera_id=${cid}`;
            btnPlantilla.classList.remove('disabled');
            btnPlantilla.style.pointerEvents = 'auto';
            btnPlantilla.style.opacity = '1';
        } else {
            btnPlantilla.href = '#';
            btnPlantilla.classList.add('disabled');
            btnPlantilla.style.pointerEvents = 'none';
            btnPlantilla.style.opacity = '0.5';
        }
    }
});
</script>