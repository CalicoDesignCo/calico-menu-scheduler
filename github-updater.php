<?php
/** Public GitHub releases through the WordPress Update URI API. */

namespace Calico\MenuScheduler;

defined( 'ABSPATH' ) || exit;

const REPOSITORY = 'https://github.com/CalicoDesignCo/calico-menu-scheduler';
const RELEASE_API = 'https://api.github.com/repos/CalicoDesignCo/calico-menu-scheduler/releases/latest';
const RELEASE_ASSET = 'calico-menu-scheduler.zip';
const RELEASE_CACHE = 'calico_menu_scheduler_public_release_v1';

function register_github_updater( $file ) {
	$basename = plugin_basename( $file );
	add_filter(
		'update_plugins_github.com',
		static function ( $update, $data, $plugin_file ) use ( $basename ) {
			if ( $plugin_file !== $basename ) {
				return $update;
			}

			$release = github_release();
			if ( ! $release || ! version_compare( $release['version'], $data['Version'], '>' ) ) {
				return false;
			}

			return array(
				'id'           => REPOSITORY,
				'slug'         => 'calico-menu-scheduler',
				'version'      => $release['version'],
				'url'          => REPOSITORY . '/releases/tag/' . $release['tag'],
				'package'      => $release['package'],
				'requires'     => '5.4',
				'requires_php' => '7.4',
			);
		},
		10,
		3
	);
	add_action( 'load-update-core.php', __NAMESPACE__ . '\\refresh_github_release', 9 );
}

function refresh_github_release() {
	// Clear the caches before WordPress runs its normal update check on this screen.
	if ( ! empty( $_GET['force-check'] ) && current_user_can( 'update_plugins' ) ) {
		delete_site_transient( RELEASE_CACHE );
		delete_site_transient( 'update_plugins' );
	}
}

function github_release() {
	$cached = get_site_transient( RELEASE_CACHE );
	if ( is_array( $cached ) && array_key_exists( 'release', $cached ) ) {
		return $cached['release'];
	}

	$response = wp_safe_remote_get(
		RELEASE_API,
		array(
			'timeout'             => 8,
			'redirection'         => 2,
			'sslverify'           => true,
			'limit_response_size' => 524288,
			'headers'             => array(
				'Accept'     => 'application/vnd.github+json',
				'User-Agent' => 'Calico-Menu-Scheduler-Updater',
			),
		)
	);
	$release = false;
	if ( ! is_wp_error( $response ) && 200 === wp_remote_retrieve_response_code( $response ) ) {
		$release = parse_github_release( json_decode( wp_remote_retrieve_body( $response ), true ) );
	}

	set_site_transient( RELEASE_CACHE, array( 'release' => $release ), $release ? 6 * HOUR_IN_SECONDS : 15 * MINUTE_IN_SECONDS );
	return $release;
}

function parse_github_release( $release ) {
	$tag = $release['tag_name'] ?? null;
	if ( empty( $release['id'] ) || false !== ( $release['draft'] ?? null ) ||
		false !== ( $release['prerelease'] ?? null ) || ! is_string( $tag ) ||
		! preg_match( '/\Av([0-9]+\.[0-9]+\.[0-9]+)\z/', $tag, $matches ) ) {
		return false;
	}

	$package = REPOSITORY . '/releases/download/' . $tag . '/' . RELEASE_ASSET;
	foreach ( (array) ( $release['assets'] ?? array() ) as $asset ) {
		if ( RELEASE_ASSET === ( $asset['name'] ?? null ) &&
			'uploaded' === ( $asset['state'] ?? null ) && ( $asset['size'] ?? 0 ) > 0 &&
			$package === ( $asset['browser_download_url'] ?? null ) ) {
			return array(
				'tag'     => $tag,
				'version' => $matches[1],
				'package' => $package,
			);
		}
	}

	return false;
}
