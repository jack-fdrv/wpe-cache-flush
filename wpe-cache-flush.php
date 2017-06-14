<?php
/**
 * Plugin Name: WP Engine Cache Flush
 * Plugin URI: https://github.org/a7/wpe-cache-flush/
 * Description: Programmatically flush the WP Engine Cache
 * Version: 0.1.0
 * Author: A7
 * Author URI: http://github.org/a7/
 */

namespace A7\WPE_Cache_Flush;

function get_flush_token() {
	$flush_token = getenv( 'WPE_CACHE_FLUSH' );

	if ( ! empty( $flush_token ) ) {
		return $flush_token;
	}

	if ( defined( 'WPE_CACHE_FLUSH' ) ) {
		return WPE_CACHE_FLUSH;
	}

	return apply_filters( __NAMESPACE__ . '/wpe_cache_flush_token', false );
}

add_action( 'init', function () {

	$key = 'wpe-cache-flush';

	if ( empty( $_GET[ $key ] ) ) {
		return;
	}

	$flush_token = get_flush_token();

	if ( false === $flush_token ) {
		return;
	}

	if ( $flush_token !== $_GET[ $key ] ) {
		return;
	}

	// Don't cause a fatal if there is no WpeCommon class
	if ( class_exists( 'WpeCommon' ) ) {
		return;
	}

	if ( function_exists( 'WpeCommon::purge_memcached' ) ) {
		\WpeCommon::purge_memcached();
	}

	if ( function_exists( 'WpeCommon::clear_maxcdn_cache' ) ) {
		\WpeCommon::clear_maxcdn_cache();
	}

	if ( function_exists( 'WpeCommon::purge_varnish_cache' ) ) {
		\WpeCommon::purge_varnish_cache();
	}

	global $wp_object_cache;
	// Check for valid cache. Sometimes this is broken -- we don't know why! -- and it crashes when we flush.
	// If there's no cache, we don't need to flush anyway.
	$error = '';

	if ( $wp_object_cache && is_object( $wp_object_cache ) ) {
		try {
			wp_cache_flush();
		} catch ( \Exception $ex ) {
			$error = "Warning: error flushing WordPress object cache: " . $ex->getMessage();
		}
	}

	header( "Content-Type: text/plain" );
	header( "X-WPE-Host: " . gethostname() . " " . $_SERVER['SERVER_ADDR'] );

	echo "All Caches were purged!";
	echo $error;

	exit( 0 );
} );
