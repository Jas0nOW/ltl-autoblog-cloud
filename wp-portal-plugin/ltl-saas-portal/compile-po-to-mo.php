#!/usr/bin/env php
<?php
/**
 * Simple PO to MO Compiler
 * Compiles .po files to .mo format for WordPress
 */

if (php_sapi_name() !== 'cli') {
    die('This script must be run from command line.');
}

$pluginDir = dirname(__FILE__);
$languagesDir = $pluginDir . '/languages';
$poFile = $languagesDir . '/ltl-saas-portal-de_DE.po';
$moFile = $languagesDir . '/ltl-saas-portal-de_DE.mo';

echo "=== LTL AutoBlog Cloud - Compile Translations ===\n\n";

if (!file_exists($poFile)) {
    die("ERROR: PO file not found: $poFile\n");
}

echo "Reading: $poFile\n";

$lines = file($poFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
$translations = [];
$currentMsgid = '';
$currentMsgstr = '';
$inMsgid = false;
$inMsgstr = false;

foreach ($lines as $line) {
    $line = trim($line);

    // Skip comments and empty lines
    if ($line === '' || $line[0] === '#') {
        continue;
    }

    if (strpos($line, 'msgid "') === 0) {
        // Save previous entry
        if ($currentMsgid !== '' && $currentMsgstr !== '') {
            $translations[$currentMsgid] = $currentMsgstr;
        }

        $currentMsgid = substr($line, 7, -1);
        $currentMsgstr = '';
        $inMsgid = true;
        $inMsgstr = false;
    } elseif (strpos($line, 'msgstr "') === 0) {
        $currentMsgstr = substr($line, 8, -1);
        $inMsgid = false;
        $inMsgstr = true;
    } elseif ($line[0] === '"' && $line[-1] === '"') {
        // Continuation line
        $content = substr($line, 1, -1);
        if ($inMsgid) {
            $currentMsgid .= $content;
        } elseif ($inMsgstr) {
            $currentMsgstr .= $content;
        }
    }
}

// Save last entry
if ($currentMsgid !== '' && $currentMsgstr !== '') {
    $translations[$currentMsgid] = $currentMsgstr;
}

echo "Found " . count($translations) . " translations\n";

// Build MO file
$mo = '';

// Magic number (0x950412de for little-endian)
$mo .= pack('V', 0x950412de);

// Version
$mo .= pack('V', 0);

// Number of strings
$count = count($translations);
$mo .= pack('V', $count);

// Offset to table with original strings
$mo .= pack('V', 28);

// Offset to table with translated strings
$origTable = 28;
$transTable = $origTable + $count * 8;
$mo .= pack('V', $transTable);

// Hash table size (not used)
$mo .= pack('V', 0);

// Offset to hash table (not used)
$mo .= pack('V', 0);

// Calculate string offsets
$offset = $transTable + $count * 8;
$origOffsets = [];
$transOffsets = [];

foreach ($translations as $msgid => $msgstr) {
    $origOffsets[] = ['length' => strlen($msgid), 'offset' => $offset];
    $offset += strlen($msgid) + 1;
}

foreach ($translations as $msgid => $msgstr) {
    $transOffsets[] = ['length' => strlen($msgstr), 'offset' => $offset];
    $offset += strlen($msgstr) + 1;
}

// Write original strings table
foreach ($origOffsets as $info) {
    $mo .= pack('V', $info['length']);
    $mo .= pack('V', $info['offset']);
}

// Write translated strings table
foreach ($transOffsets as $info) {
    $mo .= pack('V', $info['length']);
    $mo .= pack('V', $info['offset']);
}

// Write original strings
foreach (array_keys($translations) as $msgid) {
    $mo .= $msgid . "\0";
}

// Write translated strings
foreach ($translations as $msgstr) {
    $mo .= $msgstr . "\0";
}

// Write to file
if (file_put_contents($moFile, $mo) === false) {
    die("ERROR: Could not write MO file: $moFile\n");
}

echo "✓ Successfully compiled: $moFile (" . strlen($mo) . " bytes)\n";
echo "\n=== Done ===\n";
