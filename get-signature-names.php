<?php
/**
 * Generate and output a list of signature names from a Petitions.MoveOn.org petition.
 * Author: Chris Hardie, https://chrishardie.com/
 *
 * This script uses the petitions.moveon.org API endpoint behind the site's React
 * app (https://github.com/MoveOnOrg/mop-frontend) to fetch a count of available signatures, and then
 * iterate through the list to retrieve all the signature names. It discards names without at least one
 * space (looking for full names) and does some basic upper/lowercase cleanup. It outputs a string.
 *
 * Usage: php generate-names.php --list_id 12345
 * Where the ID is the signature list id found in the <meta property="list_id" ...> tag in the petition HTML source.
 */

// The API returns unpredictable results for per_page values higher than 10. I recommend against changing this.
$per_page = 10;

$all_valid_names = array();

// Check the passed options and validate.
$options = getopt( '', array( 'list_id:' ) );

if ( empty( $options['list_id'] ) || ! is_numeric( $options['list_id'] ) ) {
	echo 'Usage: php get-signature-names.php --list_id 12345' . PHP_EOL;
	echo 'You must specify a numeric Petition List ID. Exiting.' . PHP_EOL;
	exit;
}

// Build the base API endpoint URL
$api_endpoint_base = 'https://petitions.moveon.org/api/v1/petitions/list' . (int) $options['list_id'] . '/signatures.json';

// Fetch the total signature count as of right now
$signatures_context     = stream_context_create( array( 'http' => array( 'user_agent' => 'MoveOn Petition Signature Name Fetcher' ) ) );
$signature_count_source = file_get_contents( $api_endpoint_base, false, $signatures_context );
$signature_count_object = json_decode( $signature_count_source );

// If we didn't get a valid count back, exit
if ( ! empty( $signature_count_object ) && is_object( $signature_count_object ) && ! empty( $signature_count_object->count ) ) {
	$signature_count = $signature_count_object->count;
} else {
	echo 'No signatures detected on this petition. Exiting.' . PHP_EOL;
	exit;
}

// Set the maximum number of pages we'll request based on the count and per_page values
$max_pages = intdiv( $signature_count, $per_page );

if ( ( $signature_count % $per_page ) !== 0 ) {
	$max_pages++;
}

// For eachpage we need to fetch, fetch it
for ( $page = 1; $page <= $max_pages; $page++ ) {

	$api_endpoint      = $api_endpoint_base . '?per_page=' . (int) $per_page . '&page=' . (int) $page;
	$signatures_source = file_get_contents( $api_endpoint, false, $signatures_context );
	$signatures_object = json_decode( $signatures_source );

	// If we didn't get any valid signature results, note that and skip this page.
	if ( empty( $signatures_object ) || ! is_object( $signatures_object ) || empty( $signatures_object->_embedded ) ) {
		echo 'No valid signatures on page ' . $page . PHP_EOL;
		continue;
	}

	// For each signature we found, get the name out of it
	foreach ( $signatures_object->_embedded as $signature ) {

		if ( ! empty( $signature->_embedded->user->name ) ) {

			$name = $signature->_embedded->user->name;

			// Make sure the name has at least one space in it, to avoid first-name only signatures
			if ( 0 === preg_match( '/\s/', $name ) ) {
				continue;
			}

			// Fix ALL CAPS and lowercase first letters
			if ( strtoupper( $name ) === $name ) {
				$name = ucwords( strtolower( $name ) );
			} else {
				$name = ucwords( $name );
			}

			// Trim and replace multiple whitespace characters with a single space
			$name = trim( $name );
			$name = preg_replace( '/\s+/', ' ', $name );

			$all_valid_names[] = $name;

		}
	}
}

// Discard duplicate names
$unique_valid_names = array_unique( $all_valid_names );

// Sort the names by last name
usort( $unique_valid_names,
	function( $a, $b ) {
		$a = substr( strrchr( $a, ' ' ), 1 );
		$b = substr( strrchr( $b, ' ' ), 1 );
		return strcmp( $a, $b );
	}
);

// Output the names and a count
echo PHP_EOL . implode( ' * ', $unique_valid_names ) . PHP_EOL . PHP_EOL;

echo 'Successfully output ' . count( $unique_valid_names ) . ' signature names.' . PHP_EOL;

exit;
