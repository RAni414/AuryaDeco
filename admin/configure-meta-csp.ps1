$path = Join-Path $PSScriptRoot '..\.htaccess'
$content = [System.IO.File]::ReadAllText($path)
$content = $content.Replace("script-src 'self';", "script-src 'self' https://connect.facebook.net;")
$content = $content.Replace("img-src 'self' data: blob:;", "img-src 'self' data: blob: https://www.facebook.com;")
$content = $content.Replace("connect-src 'self';", "connect-src 'self' https://www.facebook.com https://connect.facebook.net;")
[System.IO.File]::WriteAllText($path, $content, (New-Object System.Text.UTF8Encoding($false)))
