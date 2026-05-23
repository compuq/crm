<!-- views/reportes/_paginacion.php -->

<?php 
    if ($totalPages > 1):?>
<div class="d-flex justify-content-between align-items-center mt-4 flex-wrap gap-3 border-top border-secondary pt-3">
    <small class="text-secondary">
        Mostrando <?= count($$dataVar) ?> de <?= $total ?> registros | Página <?= $page ?> de <?= $totalPages ?>
    </small>
    
    <nav aria-label="Paginación">
        <ul class="pagination pagination-sm mb-0">
            <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                <a class="page-link bg-dark text-white border-secondary" href="?<?= http_build_query(array_merge($_GET, ['page' => 1])) ?>">⏮ Inicio</a>
            </li>
            <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                <a class="page-link bg-dark text-white border-secondary" href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>"> Ant.</a>
            </li>
            
            <?php 
                $start = max(1, $page - 2); 
                $end = min($totalPages, $page + 2);
                for ($i = $start; $i <= $end; $i++): 
            ?>
                <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                    <a class="page-link <?= $i == $page ? 'bg-lex-primary border-lex-primary text-white' : 'bg-dark text-white border-secondary' ?>" 
                       href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>"><?= $i ?></a>
                </li>
            <?php endfor; ?>
            
            <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                <a class="page-link bg-dark text-white border-secondary" href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>">Sig. ▶</a>
            </li>
            <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                <a class="page-link bg-dark text-white border-secondary" href="?<?= http_build_query(array_merge($_GET, ['page' => $totalPages])) ?>">Fin ⏭</a>
            </li>
        </ul>
    </nav>

    <div class="input-group input-group-sm" style="width: 150px;">
        <span class="input-group-text bg-dark text-white border-secondary">Ir a:</span>
        <input type="number" id="gotoPage" class="form-control bg-dark text-white border-secondary" min="1" max="<?= $totalPages ?>" value="<?= $page ?>">
        <button class="btn btn-outline-secondary" onclick="window.location.href='?<?= http_build_query(array_merge($_GET, ['page' => '__PAGE__'])) ?>'.replace('__PAGE__', document.getElementById('gotoPage').value)">OK</button>
    </div>
</div>
<?php endif; ?>