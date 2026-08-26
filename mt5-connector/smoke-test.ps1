param(
    [Parameter(Mandatory = $true)]
    [string]$BaseUrl,

    [Parameter(Mandatory = $true)]
    [string]$Token
)

$headers = @{
    Authorization = "Bearer $Token"
}

function Invoke-StetechBridgeCheck {
    param(
        [string]$Path
    )

    $uri = ($BaseUrl.TrimEnd('/') + $Path)
    try {
        $response = Invoke-RestMethod -Headers $headers -Uri $uri -Method Get
        [pscustomobject]@{
            Path = $Path
            Ok = $true
            Data = $response
        }
    } catch {
        [pscustomobject]@{
            Path = $Path
            Ok = $false
            Error = $_.Exception.Message
        }
    }
}

$results = @(
    Invoke-StetechBridgeCheck -Path '/health'
    Invoke-StetechBridgeCheck -Path '/account'
)

$results | ForEach-Object {
    if ($_.Ok) {
        Write-Host "[OK] $($_.Path)"
        $_.Data | ConvertTo-Json -Depth 6
    } else {
        Write-Host "[FAIL] $($_.Path): $($_.Error)"
    }
}

if ($results.Where({ -not $_.Ok }).Count -gt 0) {
    exit 1
}
