param(
    [ValidateSet('gemini-3.1-flash-lite','gemini-2.5-flash-lite')][string]$Model = 'gemini-3.1-flash-lite',
    [int]$Port = 8080
)
$ErrorActionPreference = 'Stop'
$php = 'C:\xampp\php\php.exe'
$sitePath = 'C:\Users\MSI\Desktop\ahmed ben tiba\auryadeco-site (1)\auryadeco'
$sessionPath = 'C:\Users\MSI\auryadeco-sessions'
if (!(Test-Path -LiteralPath $php)) { throw 'PHP XAMPP introuvable.' }
if (!(Test-Path -LiteralPath (Join-Path $sitePath 'index.php'))) { throw 'Le dossier du site est introuvable.' }
# No secret appears in this script, the command history or PHP command arguments.
$secret = Read-Host 'Nouvelle cle Gemini (projet Free Tier sans facturation)' -AsSecureString
try {
    $env:GEMINI_API_KEY = [System.Net.NetworkCredential]::new('', $secret).Password.Trim()
    if ([string]::IsNullOrWhiteSpace($env:GEMINI_API_KEY)) { throw 'Aucune cle saisie.' }
    $env:GEMINI_MODEL = $Model
    $env:AURYA_SESSION_DIR = $sessionPath
    New-Item -ItemType Directory -Force -Path $sessionPath | Out-Null
    Write-Host "AuryaDeco : http://127.0.0.1:$Port/ - Ctrl+C pour arreter. Aucune facturation ni outil payant active."
    & $php -d extension=gd -d "session.save_path=$sessionPath" -d "upload_tmp_dir=$env:TEMP" -d upload_max_filesize=5M -d post_max_size=6M -d display_startup_errors=0 -d display_errors=0 -S "127.0.0.1:$Port" -t $sitePath
    if ($LASTEXITCODE) { throw 'PHP ne demarre pas. Verifiez notamment que le port est libre.' }
} finally {
    Remove-Item Env:\GEMINI_API_KEY -ErrorAction SilentlyContinue
    $secret.Dispose()
}
