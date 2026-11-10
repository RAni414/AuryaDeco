$ErrorActionPreference = 'Stop'
$sitePath = Split-Path -Parent $PSScriptRoot
$indexPath = Join-Path $sitePath 'index.php'
$videoPath = Join-Path $sitePath 'assets\videos\hero-deco.mp4'
$posterPath = Join-Path $sitePath 'assets\images\hero-deco-poster.jpg'

try {
    foreach ($folder in @('assets\videos', 'assets\images')) {
        New-Item -ItemType Directory -Force -Path (Join-Path $sitePath $folder) | Out-Null
    }
    Write-Host 'Telechargement de la video Pexels choisie...'
    Invoke-WebRequest -UseBasicParsing -Uri 'https://videos.pexels.com/video-files/7235796/7235796-uhd_2160_3840_30fps.mp4' -OutFile ($videoPath + '.download')
    if ((Get-Item ($videoPath + '.download')).Length -lt 10000) { throw 'Video incomplete : activation annulee.' }
    Move-Item -LiteralPath ($videoPath + '.download') -Destination $videoPath -Force
    Invoke-WebRequest -UseBasicParsing -Uri 'https://images.pexels.com/videos/7235796/architecture-beautiful-botanical-cactus-7235796.jpeg?auto=compress&dpr=1&h=750&w=1260' -OutFile ($posterPath + '.download')
    Move-Item -LiteralPath ($posterPath + '.download') -Destination $posterPath -Force

    $source = [System.IO.File]::ReadAllText($indexPath)
    if ($source.Contains('<section class="hero">')) {
        if (!(Test-Path -LiteralPath ($indexPath + '.before-video.bak'))) {
            Copy-Item -LiteralPath $indexPath -Destination ($indexPath + '.before-video.bak')
        }
        $source = [regex]::Replace($source, '\$rulerLinks = \[\];[\s\S]*?(?=require __DIR__ . ''/includes/header.php'';)', '$homePage = true;' + "`r`n", 1)
        $source = [regex]::Replace($source, '<section class="hero">[\s\S]*?</div></section>', '<?php require __DIR__ . ''/includes/home-hero.php''; ?>', 1)
        $source = $source.Replace("<?php require __DIR__ . '/includes/footer.php';", "</div>`r`n<?php require __DIR__ . '/includes/footer.php';")
        if (!$source.Contains('$homePage = true;') -or !$source.Contains("'/includes/home-hero.php'")) {
            throw 'Structure index.php inattendue : fichier conserve.'
        }
        [System.IO.File]::WriteAllText($indexPath, $source, [System.Text.UTF8Encoding]::new($false))
    } elseif (!$source.Contains("'/includes/home-hero.php'")) {
        throw 'Banniere introuvable : index.php conserve.'
    }
    Write-Host 'Accueil video active. Ouvrez http://127.0.0.1:8080/ puis Ctrl+F5.' -ForegroundColor Green
} catch {
    Write-Host ('Echec : ' + $_.Exception.Message) -ForegroundColor Red
    exit 1
}
