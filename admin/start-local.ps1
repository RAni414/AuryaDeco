param([int]$Port = 8081)
$ErrorActionPreference = 'Stop'
$sitePath = Split-Path -Parent $PSScriptRoot
$php = 'C:\xampp\php\php.exe'
if (!(Test-Path -LiteralPath $php)) { throw 'PHP XAMPP introuvable. Adaptez le chemin dans admin/start-local.ps1.' }
Write-Host "AuryaDeco : http://127.0.0.1:$Port/ (Ctrl+C pour arreter)"
& $php -d extension=gd -d "upload_tmp_dir=$env:TEMP" -d upload_max_filesize=5M -d post_max_size=6M -d display_startup_errors=0 -d display_errors=0 -S "127.0.0.1:$Port" -t $sitePath
