<?php

declare(strict_types=1);

exec('git diff-index --quiet HEAD --', $output, $exitCode);
if ($exitCode !== 0) {
    fwrite(STDERR, "Erreur : le dépôt Git n'est pas propre (fichiers versionnés modifiés).\n");
    passthru('git status --short');
    exit(1);
}

$composer = json_decode(file_get_contents(__DIR__ . '/../composer.json'), true);
$version = $composer['version'];
echo $version . "\n";

$gitVersion = "v$version";

// Create a new tag
$cmd = 'git tag -a '.escapeshellarg($gitVersion).' -m '.escapeshellarg('Release '.$gitVersion);
echo $cmd . "\n";
exec($cmd, $tagOutput, $tagExitCode);
if ($tagExitCode !== 0) {
    fwrite(STDERR, "Erreur : échec de la création du tag $gitVersion.\n");
    exit(1);
}

// Push the tag
$cmd = "git push origin ".escapeshellarg($gitVersion);
echo $cmd . "\n";
exec($cmd, $pushOutput, $pushExitCode);
if ($pushExitCode !== 0) {
    fwrite(STDERR, "Erreur : échec du push du tag $gitVersion.\n");
    exit(1);
}
