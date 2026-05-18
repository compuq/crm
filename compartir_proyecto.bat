@echo off
cd /d C:\xampp\htdocs\crm

echo Generando proyecto.txt...

if exist proyecto.txt del proyecto.txt

echo ============================== >> proyecto.txt
echo PROYECTO CRM >> proyecto.txt
echo ============================== >> proyecto.txt
echo. >> proyecto.txt

echo [INFO] Carpeta vendor omitida (dependencias Composer) >> proyecto.txt
echo [INFO] Carpeta node_modules omitida (dependencias Node.js) >> proyecto.txt
echo. >> proyecto.txt

powershell -ExecutionPolicy Bypass -Command "Get-ChildItem -Recurse -File | Where-Object { $_.FullName -notmatch 'vendor|node_modules|\.git|proyecto\.txt' } | ForEach-Object { Add-Content proyecto.txt ''; Add-Content proyecto.txt '=============================='; Add-Content proyecto.txt ('ARCHIVO: ' + $_.FullName); Add-Content proyecto.txt '=============================='; Get-Content $_.FullName | Add-Content proyecto.txt }"

echo.
echo proyecto.txt generado correctamente
pause