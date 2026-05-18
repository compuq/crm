@echo off
cd /d C:\xampp\htdocs\crm

echo ==========================
echo EXPORTANDO BASE DE DATOS
echo ==========================

set PGPASSWORD=C0N3CT4D0

"C:\Program Files\PostgreSQL\16\bin\pg_dump.exe" ^
-U postgres ^
-h localhost ^
-p 5432 ^
-F p ^
-b ^
-v ^
-f "database.sql" ^
crm

echo.
echo database.sql generado correctamente
pause