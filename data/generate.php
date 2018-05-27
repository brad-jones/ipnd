#!/usr/bin/env php
<?php

// Convert postcode.csv to PHP hashtable

if (!file_exists(__DIR__."/postcodes.csv")) {
	print "Warning: ".__DIR__."/postcodes.csv does not exist. Using sample.csv instead\n";
	$fh = fopen(__DIR__."/sample.csv", "r");
} else {
	$fh = fopen(__DIR__."/postcodes.csv", "r");
}

// First row is the names of the cols
$map = array_map('strtoupper', fgetcsv($fh));

// Now run through the file and generate our output
$output = [];
while (($row = fgetcsv($fh)) !== false) {
	$output[] = array_combine($map, array_map(function ($i) { 
		return str_replace("'", "\\'", $i);
       	}, $row));
}

//  array(5) {
//    'PCODE' => string(4) "0200"
//    'LOCALITY' => string(30) "AUSTRALIAN NATIONAL UNIVERSITY"
//    'STATE' => string(3) "ACT"
//    'COMMENT' => string(0) ""
//    'CATEGORY' => string(17) "Post Office Boxes"
//  }

// Output postcode_to_suburb.hash.php

date_default_timezone_set("Australia/Brisbane");
$d = new \DateTime();
$d->setTimezone(new \DateTimeZone("Australia/Brisbane"));
$timestamp = $d->format("Y-m-d H:i:s");

$header = [ '<?php', '', '// This file is automatically generated by \'generate.php\', and processes',
	'// the postcode file provided by Australia Post.', '//', '// Do not edit this file!',
	'// Edit the CSV and re-run the generation script.', '//', '// Generation time: '.$timestamp, '',
];

$postcode_to_suburb = $header;
$postcode_to_suburb[] = '$postcode_to_suburb_hash = [';
$postcode_to_suburb_line = "[ 'LOCALITY' => '%s', 'STATE' => '%s', 'COMMENT' => '%s', 'CATEGORY' => '%s' ],";
$postcode_map = [];

$state_postcode_map = $header;
$state_postcode_map[] = '$state_postcode_hash = [';
$state_map = [];

foreach ($output as $row) {
	$rawline = sprintf($postcode_to_suburb_line, $row['LOCALITY'], $row['STATE'], $row['COMMENT'], $row['CATEGORY']);
	$postcode_map[(string)$row['PCODE']][] = "    $rawline";
	$state_map[$row['STATE']][(string)$row['PCODE']][] = "      $rawline";
}


// Generate the postcode_to_suburb hash
foreach ($postcode_map as $pcode => $rows) {
	$postcode_to_suburb[] = "  '$pcode' => [";
	foreach ($rows as $r) {
		$postcode_to_suburb[] = $r;
	}
	$postcode_to_suburb[] = "  ],";
}
$postcode_to_suburb[] = "];";
file_put_contents(__DIR__."/postcode_to_suburb.hash.php", implode("\n", $postcode_to_suburb));

// Generate the states hash
foreach ($state_map as $state => $pcodes) {
	$state_postcode_map[] = "  '$state' => [";
	foreach ($pcodes as $pcode => $rows) {
		$state_postcode_map[] = "    '$pcode' => [";
		foreach ($rows as $r) {
			$state_postcode_map[] = $r;
		}
		$state_postcode_map[] = "    ],";
	}
	$state_postcode_map[] = "  ],";
}

$state_postcode_map[] = "];";
file_put_contents(__DIR__."/state_postcode.hash.php", implode("\n", $state_postcode_map));


