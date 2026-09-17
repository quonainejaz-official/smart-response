<?php

declare(strict_types=1);

if ($argc < 2) {
    fwrite(STDERR, "Usage: php tools/release.php <version> [release note]\n");
    exit(1);
}

$version = ltrim(trim($argv[1]), 'vV');
if (! preg_match('/^(?:0|[1-9]\d*)\.(?:0|[1-9]\d*)\.(?:0|[1-9]\d*)(?:-[0-9A-Za-z.-]+)?$/', $version)) {
    fwrite(STDERR, "Invalid semantic version: {$version}\n");
    exit(1);
}

$root = dirname(__DIR__);
$versionFile = $root.'/src/Core/Version.php';
$versionSource = file_get_contents($versionFile);
if ($versionSource === false) {
    throw new RuntimeException('Unable to read Core/Version.php.');
}

$updatedVersion = preg_replace("/CURRENT = '[^']+';/", "CURRENT = '{$version}';", $versionSource, 1, $count);
if ($updatedVersion === null || $count !== 1) {
    throw new RuntimeException('Version constant was not found.');
}
file_put_contents($versionFile, $updatedVersion);

$changelogFile = $root.'/CHANGELOG.md';
$changelog = file_get_contents($changelogFile);
if ($changelog === false) {
    throw new RuntimeException('Unable to read CHANGELOG.md.');
}

$notes = trim(implode(' ', array_slice($argv, 2)));
$notes = $notes !== '' ? $notes : 'Release '.$version;
$notes = '- '.str_replace(["\r", "\n"], ' ', $notes);
$today = date('Y-m-d');
$releaseHeading = "## [{$version}] - {$today}";

if (preg_match('/^## \[\d+\.\d+\.\d+(?:-[^]]+)?\] - Unreleased$/m', $changelog, $match, PREG_OFFSET_CAPTURE)) {
    $offset = $match[0][1];
    $changelog = substr_replace($changelog, $releaseHeading, $offset, strlen($match[0][0]));
} elseif (preg_match('/^## \[Unreleased\]$/m', $changelog, $match, PREG_OFFSET_CAPTURE)) {
    $offset = $match[0][1];
    $changelog = substr_replace($changelog, $releaseHeading, $offset, strlen($match[0][0]));
} else {
    throw new RuntimeException('An Unreleased changelog section was not found.');
}

$insert = "## [Unreleased]\n\n### Added\n\n{$notes}\n\n";
$changelog = preg_replace('/^## \['.preg_quote($version, '/').'\] - '.preg_quote($today, '/').'$/m', $insert.$releaseHeading, $changelog, 1);
file_put_contents($changelogFile, $changelog);

printf("Prepared release %s (%s). Run tests, commit, and tag v%s.\n", $version, $today, $version);
