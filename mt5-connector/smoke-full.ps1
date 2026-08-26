param(
    [Parameter(Mandatory = $true)]
    [string]$BaseUrl,

    [Parameter(Mandatory = $true)]
    [string]$Token
)

$headers = @{
    Authorization = "Bearer $Token"
}

function Invoke-StetechBridgeGet {
    param([string]$Path)
    $uri = ($BaseUrl.TrimEnd('/') + $Path)
    Invoke-RestMethod -Headers $headers -Uri $uri -Method Get
}

$health = Invoke-StetechBridgeGet -Path '/health'
$account = Invoke-StetechBridgeGet -Path '/account'
$symbols = Invoke-StetechBridgeGet -Path '/symbols'

$firstSymbol = $symbols.symbols | Select-Object -First 1

if (-not $firstSymbol) {
    Write-Host "[FAIL] No symbols were returned by the connector."
    exit 1
}

$spec = Invoke-StetechBridgeGet -Path ("/symbols/{0}/specification" -f [uri]::EscapeDataString($firstSymbol))
$positions = Invoke-StetechBridgeGet -Path '/positions'

Write-Host "[OK] /health"
$health | ConvertTo-Json -Depth 6
Write-Host "[OK] /account"
$account | ConvertTo-Json -Depth 6
Write-Host "[OK] /symbols"
$symbols | ConvertTo-Json -Depth 6
Write-Host "[OK] /symbols/$firstSymbol/specification"
$spec | ConvertTo-Json -Depth 6
Write-Host "[OK] /positions"
$positions | ConvertTo-Json -Depth 6
