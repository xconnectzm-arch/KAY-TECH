# Kay-Tech local dev server
# Serves the site on http://localhost:PORT with extension-less (clean) URLs.
# Usage:  .\dev-server.ps1 [-Port 8080] [-Root <path>]

param(
    [int]$Port = 8080,
    [string]$Root = ''
)

$ErrorActionPreference = 'Stop'
if ([string]::IsNullOrWhiteSpace($Root)) {
    $Root = $PSScriptRoot
    if (-not $Root) { $Root = (Get-Location).Path }
}
$Root = (Resolve-Path $Root).Path

# Locate a PHP binary (optional)
$PhpExe = $null
foreach ($c in @('php','php.exe','C:\php\php.exe','C:\xampp\php\php.exe')) {
    $cmd = Get-Command $c -ErrorAction SilentlyContinue
    if ($cmd) { $PhpExe = $cmd.Source; break }
    if (Test-Path $c) { $PhpExe = (Resolve-Path $c).Path; break }
}

$mime = @{
    '.html'='text/html; charset=utf-8'; '.htm'='text/html; charset=utf-8'
    '.css'='text/css; charset=utf-8'; '.js'='application/javascript; charset=utf-8'
    '.json'='application/json; charset=utf-8'; '.xml'='application/xml; charset=utf-8'
    '.txt'='text/plain; charset=utf-8'; '.md'='text/plain; charset=utf-8'
    '.png'='image/png'; '.jpg'='image/jpeg'; '.jpeg'='image/jpeg'
    '.gif'='image/gif'; '.svg'='image/svg+xml'; '.ico'='image/x-icon'
    '.webp'='image/webp'; '.woff'='font/woff'; '.woff2'='font/woff2'
    '.ttf'='font/ttf'; '.eot'='application/vnd.ms-fontobject'
    '.mp4'='video/mp4'; '.webm'='video/webm'
    '.pdf'='application/pdf'; '.zip'='application/zip'
}

function Get-MimeType($path) {
    $ext = [System.IO.Path]::GetExtension($path).ToLower()
    if ($mime.ContainsKey($ext)) { return $mime[$ext] }
    return 'application/octet-stream'
}


function Send-Text($ctx, $status, $text, $contentType) {
    $bytes = [System.Text.Encoding]::UTF8.GetBytes($text)
    $ctx.Response.StatusCode = $status
    $ctx.Response.ContentType = $contentType
    $ctx.Response.ContentLength64 = $bytes.Length
    $ctx.Response.OutputStream.Write($bytes, 0, $bytes.Length)
    $ctx.Response.Close()
}

function Send-Bytes($ctx, $status, $bytes, $contentType) {
    $ctx.Response.StatusCode = $status
    $ctx.Response.ContentType = $contentType
    $ctx.Response.ContentLength64 = $bytes.Length
    $ctx.Response.OutputStream.Write($bytes, 0, $bytes.Length)
    $ctx.Response.Close()
}

function Resolve-File($urlPath) {
    $p = $urlPath.Split('?')[0].Split('#')[0]
    try { $p = [System.Uri]::UnescapeDataString($p) } catch {}
    $p = $p -replace '/', '\'
    if ($p -eq '\' -or $p -eq '') { $p = '\index.html' }
    $full = Join-Path $Root ($p.TrimStart('\'))
    $resolved = [System.IO.Path]::GetFullPath($full)
    if (-not $resolved.StartsWith($Root, [System.StringComparison]::OrdinalIgnoreCase)) {
        return $null
    }
    if (Test-Path $resolved -PathType Leaf) { return $resolved }
    foreach ($ext in @('.html','.php','.htm')) {
        $cand = $resolved + $ext
        if (Test-Path $cand -PathType Leaf) { return $cand }
    }
    if (Test-Path $resolved -PathType Container) {
        foreach ($idx in @('index.html','index.php','index.htm')) {
            $cand = Join-Path $resolved $idx
            if (Test-Path $cand -PathType Leaf) { return $cand }
        }
    }
    return $null
}

function Handle-Php($ctx, $filePath) {
    $name = [System.IO.Path]::GetFileName($filePath).ToLower()
    if ($name -eq 'contact.php') {
        $method = $ctx.Request.HttpMethod
        if ($method -eq 'OPTIONS') {
            $ctx.Response.Headers.Add('Access-Control-Allow-Origin','*')
            $ctx.Response.Headers.Add('Access-Control-Allow-Methods','POST, GET, OPTIONS')
            $ctx.Response.Headers.Add('Access-Control-Allow-Headers','Content-Type')
            Send-Text $ctx 200 '' 'application/json; charset=utf-8'
            return
        }
        if ($method -eq 'GET') {
            $q = $ctx.Request.Url.Query
            if ($q -match 'ping') {
                Send-Text $ctx 200 '{"ok":true,"message":"contact.php alive (dev mock)"}' 'application/json; charset=utf-8'
                return
            }
        }
        $ctx.Response.Headers.Add('Access-Control-Allow-Origin','*')
        Send-Text $ctx 200 '{"success":true,"dev":true}' 'application/json; charset=utf-8'
        return
    }
    if (-not $PhpExe) {
        Send-Text $ctx 500 '{"error":"PHP binary not found on this machine"}' 'application/json; charset=utf-8'
        return
    }
    $psi = New-Object System.Diagnostics.ProcessStartInfo
    $psi.FileName = $PhpExe
    $psi.Arguments = "-f `"$filePath`""
    $psi.WorkingDirectory = [System.IO.Path]::GetDirectoryName($filePath)
    $psi.UseShellExecute = $false
    $psi.RedirectStandardOutput = $true
    $psi.RedirectStandardError = $true
    $proc = [System.Diagnostics.Process]::Start($psi)
    $stdout = $proc.StandardOutput.ReadToEnd()
    $stderr = $proc.StandardError.ReadToEnd()
    $proc.WaitForExit()
    if ($proc.ExitCode -ne 0 -and -not $stdout) {
        Send-Text $ctx 500 $stderr 'text/plain; charset=utf-8'
        return
    }
    $bytes = [System.Text.Encoding]::UTF8.GetBytes($stdout)
    Send-Bytes $ctx 200 $bytes (Get-MimeType $filePath)
}


$prefix = "http://localhost:$Port/"
$listener = New-Object System.Net.HttpListener
$listener.Prefixes.Add($prefix)
try { $listener.Start() } catch {
    Write-Host "Failed to start listener on $prefix : $($_.Exception.Message)" -ForegroundColor Red
    Write-Host "Try a different port: .\dev-server.ps1 -Port 8081" -ForegroundColor Yellow
    exit 1
}

Write-Host ""
Write-Host "Kay-Tech dev server running at $prefix" -ForegroundColor Green
Write-Host "Root: $Root" -ForegroundColor DarkGray
if ($PhpExe) { Write-Host "PHP: $PhpExe" -ForegroundColor DarkGray } else { Write-Host "PHP: (none - contact endpoints mocked)" -ForegroundColor DarkGray }
Write-Host "Press Ctrl+C to stop." -ForegroundColor DarkGray
Write-Host ""

try {
    while ($listener.IsListening) {
        $ctx = $listener.GetContext()
        $path = $ctx.Request.Url.AbsolutePath
        $filePath = Resolve-File $path
        if (-not $filePath) {
            Send-Text $ctx 404 'Not Found' 'text/plain; charset=utf-8'
            continue
        }
        $ext = [System.IO.Path]::GetExtension($filePath).ToLower()
        if ($ext -eq '.php') {
            Handle-Php $ctx $filePath
        } else {
            $bytes = [System.IO.File]::ReadAllBytes($filePath)
            Send-Bytes $ctx 200 $bytes (Get-MimeType $filePath)
        }
    }
} finally {
    $listener.Stop()
    $listener.Close()
    Write-Host "Server stopped." -ForegroundColor Yellow
}

