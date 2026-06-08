<script>
function exportarTablaExcel(idTabla, prefijo, titulo = '') {

    const tabla = document.getElementById(idTabla);

    if (!tabla) {
        alert('No se encontró la tabla: ' + idTabla);
        return;
    }

    // Generar fecha y hora: DDMMYYYYHHMMSS
    const ahora = new Date();

    const fechaHora =
        String(ahora.getDate()).padStart(2, '0') +
        String(ahora.getMonth() + 1).padStart(2, '0') +
        ahora.getFullYear() +
        String(ahora.getHours()).padStart(2, '0') +
        String(ahora.getMinutes()).padStart(2, '0') +
        String(ahora.getSeconds()).padStart(2, '0');

    const nombreArchivo = `${prefijo}_${fechaHora}.xls`;

    const contenido = `
        <html xmlns:o="urn:schemas-microsoft-com:office:office"
              xmlns:x="urn:schemas-microsoft-com:office:excel"
              xmlns="http://www.w3.org/TR/REC-html40">
        <head>
            <meta charset="UTF-8">
            <style>
                table {
                    border-collapse: collapse;
                    width: 100%;
                }

                th, td {
                    border: 1px solid #000;
                    padding: 5px;
                }

                th {
                    background-color: #D9EAD3;
                    font-weight: bold;
                }

                h2 {
                    text-align: center;
                }
            </style>
        </head>
        <body>
            ${titulo ? `<h2>${titulo}</h2>` : ''}
            ${tabla.outerHTML}
        </body>
        </html>
    `;

    const blob = new Blob(
        ['\ufeff', contenido],
        { type: 'application/vnd.ms-excel;charset=utf-8;' }
    );

    const enlace = document.createElement('a');

    enlace.href = URL.createObjectURL(blob);
    enlace.download = nombreArchivo;

    document.body.appendChild(enlace);
    enlace.click();
    document.body.removeChild(enlace);

    URL.revokeObjectURL(enlace.href);
}
</script>
<!-- SCRIPTS DEL NAVBAR -->
<script>
document.addEventListener('DOMContentLoaded', function() {

    const html = document.documentElement;
    const btn = document.getElementById('themeToggle');

    // Recuperar tema guardado
    const savedTheme = localStorage.getItem('theme') || 'dark';

    html.setAttribute('data-bs-theme', savedTheme);
    actualizarIcono(savedTheme);

    btn.addEventListener('click', function() {

        const currentTheme = html.getAttribute('data-bs-theme');

        const newTheme = currentTheme === 'dark'
            ? 'light'
            : 'dark';

        html.setAttribute('data-bs-theme', newTheme);
        localStorage.setItem('theme', newTheme);

        actualizarIcono(newTheme);
    });

    function actualizarIcono(theme) {
        btn.innerHTML = theme === 'dark'
            ? '☀️'
            : '🌙';
    }

});
function imprimirDiv(idDiv) {

    const contenido = document.getElementById(idDiv);

    if (!contenido) {
        alert('No se encontró el elemento: ' + idDiv);
        return;
    }

    const ventana = window.open('', '_blank');

    ventana.document.write(`
        <!DOCTYPE html>
        <html>
        <head>
            <title>Impresión</title>
            <style>
                body {
                    font-family: Arial, sans-serif;
                    margin: 20px;
                }

                table {
                    width: 100%;
                    border-collapse: collapse;
                }

                th, td {
                    border: 1px solid #000;
                    padding: 5px;
                    text-align: left;
                }
            </style>
        </head>
        <body>
            ${contenido.innerHTML}
        </body>
        </html>
    `);

    ventana.document.close();

    ventana.onload = function () {
        ventana.focus();
        ventana.print();
        ventana.close();
    };
}
function cargarDocumentosExistentes(id_pago) {
    const contenedor = document.getElementById('contenedorDocumentos');
    contenedor.innerHTML = '<div class="text-center w-100 py-4 text-muted"><div class="spinner-border spinner-border-sm"></div> Cargando...</div>';

    fetch(`?action=obtener_documentos&id_pago=${id_pago}`)
        .then(res => res.json())
        .then(docs => {
            if (docs.length === 0) {
                contenedor.innerHTML = `
                    <div class="text-center w-100 py-4 text-muted bg-light rounded">
                        <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                        <small>No hay documentos adjuntos aún.</small>
                    </div>`;
                return;
            }

            let html = '';
            docs.forEach(doc => {
                const esImagen = doc.tipo_archivo.startsWith('image/');
                const urlDescarga = `?action=descargar_pago&id=${doc.id}`;
                const tamanoMB = (doc.tamano / (1024 * 1024)).toFixed(2);
                const fecha = new Date(doc.fecha_subida).toLocaleDateString();
                
                const descEscape = escapeHtml(doc.descripcion);
                const nombreEscape = escapeHtml(doc.nombre_original);

                html += `
                <div class="doc-card">
                    <a href="${urlDescarga}" 
                       onclick="abrirPopup(this.href); return false;" 
                       class="text-decoration-none d-block" 
                       title="Clic para ver en ventana emergente">
                        <div class="doc-preview">
                            ${esImagen 
                                ? `<img src="${urlDescarga}" alt="Vista previa">` 
                                : `<i class="bi bi-file-earmark-pdf text-danger" style="font-size: 3rem;"></i>`
                            }
                            <div class="position-absolute bottom-0 end-0 bg-dark bg-opacity-75 text-white px-2 py-1" style="font-size: 0.7rem;">
                                <i class="bi bi-eye"></i> Ver
                            </div>
                        </div>
                    </a>
                    <div class="doc-info">
                        <div class="doc-desc" title="${descEscape}">${descEscape}</div>
                        <div class="doc-meta">
                            ${nombreEscape}<br>
                            ${tamanoMB} MB • ${fecha}
                        </div>
                        <!-- ✅ NUEVO: Botón de eliminar -->
                        <button type="button" 
                                class="btn btn-sm btn-link text-danger mt-2 p-0" 
                                onclick="borrarDocumento(${doc.id}, '${descEscape}')"
                                title="Eliminar documento">
                            <i class="bi bi-trash"></i> Eliminar
                        </button>
                    </div>
                </div>`;
            });
            contenedor.innerHTML = html;
        })
        .catch(err => {
            console.error('Error al cargar documentos:', err);
            contenedor.innerHTML = '<div class="text-center w-100 py-4 text-danger">Error al cargar documentos.</div>';
        });
}
function borrarDocumento(id_documento, descripcion) {
    if (!confirm(`¿Estás seguro de eliminar el documento "${descripcion}"?\n\nEsta acción no se puede deshacer.`)) {
        return;
    }

    fetch('?action=borrar_imagen', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `id=${id_documento}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Recargar el carrusel para actualizar la lista
            const idPago = document.getElementById('inputIdPago').value;
            cargarDocumentosExistentes(idPago);
            alert('✅ Documento eliminado correctamente');
        } else {
            alert('❌ Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('❌ Error de conexión al eliminar el documento');
    });
}

</script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<?= $extraScripts ?? '' ?>
</body>
</html>