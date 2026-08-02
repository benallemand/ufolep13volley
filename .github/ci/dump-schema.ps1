<#
.SYNOPSIS
    Regenere .github/ci/schema.sql a partir d'une base MySQL de reference.

.DESCRIPTION
    Le job PHPUnit de la CI (.github/workflows/tests.yml) demarre un service
    container MySQL vide et y charge ce schema. Il doit donc rester aligne sur
    le schema reel, sinon la CI teste une structure qui a derive de la prod.

    A relancer apres chaque migration appliquee a la base
    (les scripts de migration vivent dans ufolep13volley_python/sql/updates/).

    Le dump est volontairement --no-data : aucune donnee metier n'est versionnee.
    Les clauses DEFINER sont retirees car elles referencent des comptes MySQL
    (ufolepvocbufolep@%, root@localhost) qui n'existent pas dans le container CI.

.EXAMPLE
    pwsh .github/ci/dump-schema.ps1
    pwsh .github/ci/dump-schema.ps1 -Database ufolep_13volley -Password test
#>
param(
    [string]$Server = '127.0.0.1',
    [string]$Port = '3306',
    [string]$User = 'root',
    [string]$Password = 'test',
    [string]$Database = 'ufolep_13volley',
    [string]$MysqlDump = 'C:\Program Files\MySQL\MySQL Server 8.0\bin\mysqldump.exe'
)

$ErrorActionPreference = 'Stop'

if (-not (Test-Path $MysqlDump)) {
    throw "mysqldump introuvable : $MysqlDump (passer -MysqlDump <chemin>)"
}

$outFile = Join-Path $PSScriptRoot 'schema.sql'
$tmpFile = [System.IO.Path]::GetTempFileName()

try {
    # --skip-dump-date : evite un diff git a chaque regeneration.
    & $MysqlDump `
        "-h$Server" "-P$Port" "-u$User" "-p$Password" `
        --no-data --routines --triggers --events `
        --skip-dump-date --column-statistics=0 `
        "--result-file=$tmpFile" `
        $Database
    if ($LASTEXITCODE -ne 0) {
        throw "mysqldump a echoue (code $LASTEXITCODE)"
    }

    $sql = [System.IO.File]::ReadAllText($tmpFile)

    # Les vues/routines/triggers sont recrees sous l'identite du compte CI.
    $sql = [regex]::Replace($sql, 'DEFINER=`[^`]*`@`[^`]*`\s*', '')
    $sql = $sql -replace 'SQL SECURITY DEFINER', 'SQL SECURITY INVOKER'

    # Le repo impose LF (voir CLAUDE.md).
    $sql = $sql -replace "`r`n", "`n"

    [System.IO.File]::WriteAllText($outFile, $sql, (New-Object System.Text.UTF8Encoding $false))

    $tables = ([regex]::Matches($sql, 'CREATE TABLE')).Count
    $views = ([regex]::Matches($sql, 'CREATE\s+VIEW')).Count
    Write-Host "schema.sql regenere : $tables tables, $views vues, $([math]::Round((Get-Item $outFile).Length / 1KB)) Ko"
}
finally {
    Remove-Item $tmpFile -ErrorAction SilentlyContinue
}
