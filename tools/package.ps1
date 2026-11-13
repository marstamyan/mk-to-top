$ErrorActionPreference = 'Stop'
$pluginRoot = [IO.Path]::GetFullPath((Split-Path -Parent $PSScriptRoot))
$outputDirectory = Join-Path $pluginRoot 'dist'
[IO.Directory]::CreateDirectory($outputDirectory) | Out-Null
$header = Get-Content -LiteralPath (Join-Path $pluginRoot 'mk-to-top-button.php') -Raw
$version = [regex]::Match($header, '(?m)^\s*\* Version:\s*([0-9.]+)\s*$').Groups[1].Value
if (-not $version) { throw 'Plugin version is missing.' }
$archivePath = Join-Path $outputDirectory "mk-to-top-$version.zip"
$sourceFiles = @('mk-to-top-button.php', 'uninstall.php', 'index.php', 'README.md')
foreach ($directory in @('admin', 'includes', 'public')) {
    $sourceFiles += Get-ChildItem -LiteralPath (Join-Path $pluginRoot $directory) -File -Recurse | ForEach-Object {
        $_.FullName.Substring($pluginRoot.Length + 1)
    }
}
Add-Type -AssemblyName System.IO.Compression
Add-Type -AssemblyName System.IO.Compression.FileSystem
$stream = [IO.File]::Open($archivePath, [IO.FileMode]::Create)
$archive = [IO.Compression.ZipArchive]::new($stream, [IO.Compression.ZipArchiveMode]::Create)
try {
    foreach ($relativePath in $sourceFiles) {
        $entryName = 'mk-to-top/' + $relativePath.Replace('\', '/')
        [IO.Compression.ZipFileExtensions]::CreateEntryFromFile($archive, (Join-Path $pluginRoot $relativePath), $entryName) | Out-Null
    }
} finally {
    $archive.Dispose()
    $stream.Dispose()
}
Write-Output $archivePath
