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
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<?= $extraScripts ?? '' ?>
</body>
</html>