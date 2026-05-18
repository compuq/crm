@echo off
cd /d C:\xampp\htdocs\crm

echo =========================
echo COMPARTIENDO PROYECTO
echo =========================

git add .
git commit -m "Actualizacion del proyecto"
git push origin main

echo.
echo =========================
echo PROYECTO ACTUALIZADO
echo =========================

pause