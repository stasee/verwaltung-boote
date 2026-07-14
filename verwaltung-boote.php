<?php
/**
 * Plugin Name:       Verwaltung Boote
 * Description:       Verwaltung von Motor- und Segelbooten fuer einen Segelverein.
 * Version:           1.5.5
 * Requires at least: 6.9
 * Requires PHP:      7.2.24
 * Author:            Website-Administration
 * Text Domain:       verwaltung-boote
 * Domain Path:       /languages
 *
 * @package VerwaltungBoote
 */

defined( 'ABSPATH' ) || exit;

define( 'VERWALTUNG_BOOTE_VERSION', '1.5.5' );
define( 'VERWALTUNG_BOOTE_FILE', __FILE__ );
define( 'VERWALTUNG_BOOTE_PATH', plugin_dir_path( __FILE__ ) );

require_once VERWALTUNG_BOOTE_PATH . 'includes/class-plugin.php';

register_activation_hook( __FILE__, array( 'Verwaltung_Boote\Plugin', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'Verwaltung_Boote\Plugin', 'deactivate' ) );

Verwaltung_Boote\Plugin::instance()->register_hooks();
