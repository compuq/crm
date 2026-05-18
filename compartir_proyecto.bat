powershell -Command ^
"Get-ChildItem -Recurse -File | Where-Object {
    $_.FullName -notmatch '\\vendor\\|\\node_modules\\|\\.git\\'
} | ForEach-Object {
    Add-Content proyecto.txt ('`n==============================');
    Add-Content proyecto.txt ('ARCHIVO: ' + $_.FullName);
    Add-Content proyecto.txt '==============================';
    Get-Content $_.FullName | Add-Content proyecto.txt
}"