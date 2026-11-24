<?php
/**
 * Plugin Name: Calico Dynamic Date Selection for WordPress Menus
 * Plugin URI: https://github.com/CalicoDesignCo/calico-menu-scheduler
 * Description: Set date-based visibility periods directly on classic WordPress menu items.
 * Version: 1.1.0
 * Requires at least: 5.4
 * Requires PHP: 7.4
 * Author: Calico Design Co.
 * License: GPL-2.0-or-later
 * Text Domain: calico-menu-scheduler
 * Update URI: https://github.com/CalicoDesignCo/calico-menu-scheduler
 */

namespace Calico\MenuScheduler;

defined( 'ABSPATH' ) || exit;

const META_KEY = '_calico_menu_visibility_periods';
const VERSION = '1.1.0';

require_once __DIR__ . '/github-updater.php';
register_github_updater( __FILE__ );

add_action( 'wp_nav_menu_item_custom_fields', __NAMESPACE__ . '\\item_fields' );
add_action( 'admin_enqueue_scripts', __NAMESPACE__ . '\\enqueue_assets' );
add_action( 'admin_print_footer_scripts-nav-menus.php', __NAMESPACE__ . '\\editor_templates' );
add_action( 'wp_update_nav_menu_item', __NAMESPACE__ . '\\save_periods', 10, 2 );
add_filter( 'wp_nav_menu_objects', __NAMESPACE__ . '\\filter_menu_items', 100 );

function enqueue_assets( $screen ) {
	if ( 'nav-menus.php' !== $screen ) {
		return;
	}

	wp_enqueue_style( 'calico-menu-scheduler', plugins_url( 'assets/menu-editor.css', __FILE__ ), array(), VERSION );
	wp_enqueue_script( 'calico-menu-scheduler', plugins_url( 'assets/menu-editor.js', __FILE__ ), array( 'nav-menu' ), VERSION, true );
}

function rule_labels() {
	return array(
		'through'     => __( 'Show On and Before', 'calico-menu-scheduler' ),
		'from'        => __( 'Show On and After', 'calico-menu-scheduler' ),
		'hide_before' => __( 'Hide Before', 'calico-menu-scheduler' ),
		'hide_after'  => __( 'Hide After', 'calico-menu-scheduler' ),
	);
}

function item_fields( $item_id ) {
	$periods = get_post_meta( $item_id, META_KEY, true );
	?>
	<div class="calico-menu-schedule" hidden>
		<input type="hidden" name="calico-menu-periods[<?php echo esc_attr( $item_id ); ?>]"
			value="<?php echo esc_attr( wp_json_encode( is_array( $periods ) ? $periods : array() ) ); ?>">
		<fieldset>
			<legend><?php esc_html_e( 'Visibility schedule', 'calico-menu-scheduler' ); ?></legend>
			<p class="description"><?php esc_html_e( 'The item shows when every rule in any one period matches. With no periods, it shows as usual.', 'calico-menu-scheduler' ); ?></p>
			<p class="description">
				<?php
				/* translators: %s: WordPress site's timezone. */
				printf( esc_html__( 'Dates use the site timezone (%s).', 'calico-menu-scheduler' ), esc_html( wp_timezone_string() ) );
				?>
			</p>
			<div data-calico-periods></div>
			<p class="calico-menu-error" role="alert" hidden>
				<?php esc_html_e( 'Enter a valid date for the highlighted rule or remove that rule before saving.', 'calico-menu-scheduler' ); ?>
			</p>
			<button type="button" class="button" data-calico-action="add-period"><?php esc_html_e( 'Add visibility period', 'calico-menu-scheduler' ); ?></button>
		</fieldset>
	</div>
	<?php
}

function editor_templates() {
	?>
	<template id="calico-menu-period-template">
		<fieldset class="calico-menu-period">
			<legend><?php esc_html_e( 'Visibility period', 'calico-menu-scheduler' ); ?></legend>
			<div data-calico-rules></div>
			<div class="calico-menu-period-actions">
				<button type="button" class="button" data-calico-action="add-rule"><?php esc_html_e( 'Add date rule', 'calico-menu-scheduler' ); ?></button>
				<button type="button" class="button-link-delete" data-calico-action="remove-period"><?php esc_html_e( 'Remove period', 'calico-menu-scheduler' ); ?></button>
			</div>
		</fieldset>
	</template>
	<template id="calico-menu-rule-template">
		<div class="calico-menu-rule">
			<label>
				<span class="screen-reader-text"><?php esc_html_e( 'Date rule', 'calico-menu-scheduler' ); ?></span>
				<select>
					<?php foreach ( rule_labels() as $key => $label ) : ?>
						<option value="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></option>
					<?php endforeach; ?>
				</select>
			</label>
			<label>
				<span class="screen-reader-text"><?php esc_html_e( 'Rule date', 'calico-menu-scheduler' ); ?></span>
				<input type="date">
			</label>
			<button type="button" class="button-link-delete" data-calico-action="remove-rule"><?php esc_html_e( 'Remove rule', 'calico-menu-scheduler' ); ?></button>
		</div>
	</template>
	<?php
}

function save_periods( $menu_id, $item_id ) {
	// Only the classic menu editor sends these fields and its save nonce.
	$value = $_POST['calico-menu-periods'][ $item_id ] ?? null;
	$nonce = $_POST['update-nav-menu-nonce'] ?? null;
	if ( ! is_admin() || ! is_string( $value ) || ! is_string( $nonce ) ||
		! current_user_can( 'edit_theme_options' ) || ! wp_verify_nonce( wp_unslash( $nonce ), 'update-nav_menu' ) ) {
		return;
	}

	$periods = validate_periods( json_decode( wp_unslash( $value ), true ) );
	if ( null === $periods ) {
		return;
	}
	if ( array() === $periods ) {
		delete_post_meta( $item_id, META_KEY );
		return;
	}
	update_post_meta( $item_id, META_KEY, $periods );
}

function validate_periods( $periods ) {
	if ( ! is_array( $periods ) ) {
		return null;
	}

	$labels = rule_labels();
	$clean = array();
	foreach ( $periods as $period ) {
		if ( ! is_array( $period ) || ! $period ) {
			return null;
		}

		$rules = array();
		foreach ( $period as $rule ) {
			$type = $rule['type'] ?? null;
			$date = $rule['date'] ?? null;
			if ( ! is_string( $type ) || ! isset( $labels[ $type ] ) || ! is_string( $date ) ||
				! preg_match( '/\A([0-9]{4})-([0-9]{2})-([0-9]{2})\z/', $date, $parts ) ||
				! checkdate( (int) $parts[2], (int) $parts[3], (int) $parts[1] ) ) {
				return null;
			}
			$rules[] = array( 'type' => $type, 'date' => $date );
		}
		$clean[] = $rules;
	}

	return $clean;
}

function is_visible( $periods, $today ) {
	if ( ! is_array( $periods ) || ! $periods ) {
		return true;
	}

	foreach ( $periods as $period ) {
		$matches = true;
		foreach ( $period as $rule ) {
			// Each pair uses the same inclusive boundary: hide before X allows X itself.
			$starts_period = 'from' === $rule['type'] || 'hide_before' === $rule['type'];
			if ( $starts_period ? $today < $rule['date'] : $today > $rule['date'] ) {
				$matches = false;
				break;
			}
		}
		if ( $matches ) {
			return true;
		}
	}

	return false;
}

function filter_menu_items( $items ) {
	$today = current_datetime()->format( 'Y-m-d' );
	$hidden = array();
	$children = array();
	foreach ( $items as $item ) {
		$children[ $item->menu_item_parent ][] = $item->ID;
		if ( is_numeric( $item->ID ) && ! is_visible( get_post_meta( $item->ID, META_KEY, true ), $today ) ) {
			$hidden[ $item->ID ] = true;
		}
	}
	if ( ! $hidden ) {
		return $items;
	}

	// Keep a hidden parent's entire branch out of the walker.
	$pending = array_keys( $hidden );
	while ( $pending ) {
		$parent = array_pop( $pending );
		foreach ( $children[ $parent ] ?? array() as $child ) {
			if ( ! isset( $hidden[ $child ] ) ) {
				$hidden[ $child ] = true;
				$pending[] = $child;
			}
		}
	}

	foreach ( $items as $order => $item ) {
		if ( isset( $hidden[ $item->ID ] ) ) {
			unset( $items[ $order ] );
			continue;
		}
		// WordPress adds this class before the filter. Remove it if all children were hidden.
		$child_ids = $children[ $item->ID ] ?? array();
		if ( $child_ids && ! array_diff_key( array_flip( $child_ids ), $hidden ) ) {
			$item = clone $item;
			$item->classes = array_diff( $item->classes, array( 'menu-item-has-children' ) );
			$items[ $order ] = $item;
		}
	}

	return $items;
}
