param(
    [string]$SessionName = "ayashii.world",
    [switch]$IncludeVendor,
    [switch]$TestConnection
)

Set-StrictMode -Version Latest
$ErrorActionPreference = "Stop"

$repoRoot = (Resolve-Path (Join-Path $PSScriptRoot "..")).Path
$remotePath = "/public_html/ayashii.world/cgi-bin/bbs-v2"
$logPath = Join-Path $repoRoot "deploy-winscp.log"
$targets = @("app", "config", "public")
if ($IncludeVendor) {
    $targets += "vendor"
}

function Get-WinScpComPath {
    $candidates = @(
        "C:\Program Files (x86)\WinSCP\WinSCP.com",
        "C:\Program Files\WinSCP\WinSCP.com"
    )
    $path = $candidates | Where-Object { Test-Path $_ } | Select-Object -First 1
    if (-not $path) {
        throw "WinSCP.com が見つかりません。"
    }
    return $path
}

function Build-WinScpScriptLines {
    param(
        [string]$Session,
        [string]$RepoRootPath,
        [string]$RemoteDir,
        [string[]]$TargetDirs,
        [bool]$OnlyTestConnection
    )

    $lines = @(
        "option batch abort",
        "option confirm off",
        "open ""$Session"""
    )

    if ($OnlyTestConnection) {
        $lines += "pwd"
        $lines += "ls"
        $lines += "exit"
        return @{
            ScriptLines = $lines
            UploadedTargets = @()
        }
    }

    $uploadedTargets = @()
    foreach ($target in $TargetDirs) {
        $localTarget = Join-Path $RepoRootPath $target
        if (-not (Test-Path $localTarget)) {
            Write-Warning "スキップ: $target が見つかりません。"
            continue
        }
        $lines += "put -transfer=binary `"$localTarget`" `"$RemoteDir/`""
        $uploadedTargets += $target
    }

    $lines += "exit"
    return @{
        ScriptLines = $lines
        UploadedTargets = $uploadedTargets
    }
}

$winScpPath = Get-WinScpComPath
$scriptData = Build-WinScpScriptLines -Session $SessionName -RepoRootPath $repoRoot -RemoteDir $remotePath -TargetDirs $targets -OnlyTestConnection $TestConnection.IsPresent
$scriptLines = $scriptData.ScriptLines
$uploadedTargets = $scriptData.UploadedTargets
$shouldRunWinScp = $TestConnection -or $uploadedTargets.Count -gt 0

if ($shouldRunWinScp) {
    $tempScript = Join-Path $env:TEMP ("winscp-deploy-" + [guid]::NewGuid().ToString("N") + ".txt")
    Set-Content -Path $tempScript -Value ($scriptLines -join "`r`n") -Encoding UTF8

    try {
        & $winScpPath "/log=$logPath" "/script=$tempScript"
        if ($LASTEXITCODE -ne 0) {
            throw "WinSCP の終了コード: $LASTEXITCODE"
        }
        if ($TestConnection) {
            Write-Host "接続テスト成功"
        } else {
            Write-Host "アップロード完了: $($uploadedTargets -join ', ') -> $remotePath"
        }
        Write-Host "ログ: $logPath"
    }
    finally {
        if (Test-Path $tempScript) {
            Remove-Item $tempScript -Force
        }
    }
}
