<!-- views/carteras/configurar_extras.php -->
<div class="card bg-dark border-secondary mb-4">
    <div class="card-header border-secondary d-flex justify-content-between">
        <h5 class="mb-0 text-white">️ Configurar Campos Extra en Gestión</h5>
        <!-- Botón para abrir modal -->
        <button class="btn btn-sm btn-outline-info" data-bs-toggle="modal" data-bs-target="#modalNuevoCampo">
            ➕ Agregar Campo
        </button>
    </div>
</div>

<!-- ✅ MODAL CON TU FORMULARIO -->
<div class="modal fade" id="modalNuevoCampo" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content bg-dark border-secondary">
            <form action="?action=guardar_campo_extra" method="POST">
                <div class="modal-header border-secondary">
                    <h5 class="modal-title text-white">Nuevo Campo Extra</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="id_cartera" value="<?= $carteraId ?>">
                    
                    <div class="mb-3">
                        <label class="form-label text-secondary">Nombre Técnico (sin espacios)</label>
                        <input type="text" name="nombre_campo" class="form-control bg-dark text-white border-secondary" placeholder="ej: monto_promesa" required>
                        <small class="text-secondary">Se usará internamente y en Excel/JSON</small>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label text-secondary">Etiqueta Visible</label>
                        <input type="text" name="etiqueta" class="form-control bg-dark text-white border-secondary" placeholder="ej: Monto Prometido" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-secondary">Aplicar a:</label>
                        <select name="modulo" class="form-select bg-dark text-white border-secondary">
                            <option value="clientes">👤 Clientes (Carga masiva / Ficha)</option>
                            <option value="gestiones">📞 Gestiones (Modal de llamada)</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer border-secondary">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">💾 Guardar Campo</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Tabla de campos existentes (opcional pero recomendado) -->
<table class="table table-dark table-sm mt-3">
    <thead><tr><th>Módulo</th><th>Etiqueta</th><th>Campo Técnico</th><th>Estado</th><th></th></tr></thead>
    <tbody>
        <?php foreach ($extras as $e): ?>
        <tr>
            <td><?= $e['modulo'] === 'gestiones' ? '📞 Gestiones' : '👤 Clientes' ?></td>
            <td><?= htmlspecialchars($e['etiqueta']) ?></td>
            <td><code><?= htmlspecialchars($e['nombre_campo']) ?></code></td>
            <td><?= $e['activo'] ? '✅ Activo' : ' Inactivo' ?></td>
            <td>
                <a href="?action=toggle_extra&id=<?= $e['id'] ?>&cartera=<?= $carteraId ?>" class="btn btn-xs btn-outline-warning">Toggle</a>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>