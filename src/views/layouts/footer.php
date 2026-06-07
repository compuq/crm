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

</script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<?= $extraScripts ?? '' ?>
</body>
</html>