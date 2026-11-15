<?php

/**
 * Download vendor MAC addresses (OUI) from a URL and save to JSON file
 *
 * Usage: php download_vendormacs.php <url> [output_file]
 */

// script can only be run from cli
if (PHP_SAPI !== 'cli') {
    exit('This script can only be run from cli!'.PHP_EOL);
}

// check if url is provided
if ($argc < 2) {
    print 'Usage: php download_vendormacs.php <url> [output_file]'.PHP_EOL;
    print 'Example: php download_vendormacs.php https://maclookup.app/downloads/json-database/get-db'.PHP_EOL;
    exit(1);
}

// get URL from command line
$url = $argv[1];
$skip_private = false;

// set output file path (default to functions/vendormacs_new.json)
$output_file = __DIR__.'/../vendormacs_new.json';
$output_file = realpath($output_file) ?: $output_file;
if ($argc >= 3) {
    $output_file = $argv[2];
}

// download the file content
$content = file_get_contents($url);
if ($content === false) {
    print "Error: Failed to download content from URL: $url".PHP_EOL;
    exit(1);
}

// decode JSON
$data = json_decode($content, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    print 'Error: Failed to decode JSON: '.json_last_error_msg().PHP_EOL;
    exit(1);
}

// Check if $data is an array before iterating
if (! is_array($data)) {
    print 'Error: Downloaded content is not a valid JSON array'.PHP_EOL;
    exit(1);
}

// extract macPrefix and vendorName into associative array
$result = [];
foreach ($data as $item) {
    if (! isset($item['macPrefix'], $item['vendorName'], $item['private'])) {
        continue;
    }

    if ($skip_private && $item['private']) {
        continue;
    }

    if ((string) $item['vendorName'] === '') {
        continue;
    }

    $prefix = strtolower(str_replace(':', '', $item['macPrefix']));
    $result[$prefix] = $item['vendorName'];
}

// sort the result by key (MAC prefix)
ksort($result);

// save to JSON file with pretty formatting
$output_content = json_encode($result, JSON_UNESCAPED_UNICODE);
if ($output_content === false) {
    print 'Error: Failed to encode result as JSON:'.PHP_EOL;
    exit(1);
}

$save_result = file_put_contents($output_file, $output_content);
if ($save_result === false) {
    print "Error: Failed to save content to file: $output_file".PHP_EOL;
    exit(1);
}

print "Successfully downloaded and saved vendor MAC data to: $output_file".PHP_EOL;
print 'Processed '.count($result).' vendor entries.'.PHP_EOL;
