@echo off
cd /d C:\xampp\htdocs\crm

echo ==========================
echo ACTUALIZANDO GITHUB
echo ==========================

git add .

set /p mensaje=Mensaje del commit: 

git commit -m "%mensaje%"

git push origin main

echo.
echo ==========================
echo PROYECTO ACTUALIZADO
echo ==========================
pause