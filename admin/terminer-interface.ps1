param([string]$BaseUrl = 'http://127.0.0.1:8081/')
$ErrorActionPreference = 'Stop'
$site = Split-Path -Parent $PSScriptRoot
$index = Join-Path $site 'index.php'
$source = [System.IO.File]::ReadAllText($index)
if (!$source.Contains("'/includes/home-universes.php'")) {
    $pattern = '<section class="block"><div class="wrap">\s*<div class="shead"><div><h2>Nos univers</h2>[\s\S]*?</div></section>'
    if ([regex]::Matches($source, $pattern).Count -ne 1) { throw 'Bloc Nos univers introuvable : index.php conserve.' }
    Copy-Item -LiteralPath $index -Destination (Join-Path $PSScriptRoot 'index-avant-univers.bak') -Force
    $source = [regex]::Replace($source, $pattern, '<?php require __DIR__ . ''/includes/home-universes.php''; ?>', 1)
    [System.IO.File]::WriteAllText($index, $source, [System.Text.UTF8Encoding]::new($false))
}
Write-Host 'Nos univers active.' -ForegroundColor Green

Add-Type -AssemblyName System.Drawing
$photos=@(@('34673919','univers-plantes.jpg'),@('7674561','univers-pots.jpg'),@('7663968','univers-tombantes.jpg'),@('14465902','univers-accessoires.jpg'))
$folder=Join-Path $site 'assets\images'
New-Item -ItemType Directory -Force -Path $folder | Out-Null
$failed=@()
foreach($photo in $photos){
    $path=Join-Path $folder $photo[1]
    if (Test-Path -LiteralPath $path) { Write-Host ($photo[1]+' existe deja : conserve.'); continue }
    try {
        $uri='https://images.pexels.com/photos/'+$photo[0]+'/pexels-photo-'+$photo[0]+'.jpeg?auto=compress&cs=srgb&fm=jpg&w=1200&q=82'
        Invoke-WebRequest -UseBasicParsing -Uri $uri -OutFile ($path+'.download')
        $image=[System.Drawing.Image]::FromFile($path+'.download')
        $image.Dispose()
        Move-Item -LiteralPath ($path+'.download') -Destination $path
        Write-Host ($photo[1]+' installee.') -ForegroundColor Green
    } catch { $failed += $photo[1]; Write-Warning ($photo[1]+' : '+$_.Exception.Message) }
}

$page=Invoke-WebRequest -UseBasicParsing -Uri $BaseUrl
if (!$page.Content.Contains('home-universes')) { throw 'La page servie ne contient pas le nouveau composant. Verifiez le dossier du serveur PHP.' }
node (Join-Path $PSScriptRoot 'tests\interface-browser.mjs') $BaseUrl
if ($LASTEXITCODE) { throw 'Les verifications visuelles ou fonctionnelles ont signale une erreur. Consultez admin/verification/rapport.json.' }
if ($failed.Count) { throw ('Activation et captures terminees, mais photos manquantes : '+($failed -join ', ')) }
Write-Host 'Termine. Captures et rapport dans admin/verification.' -ForegroundColor Green
