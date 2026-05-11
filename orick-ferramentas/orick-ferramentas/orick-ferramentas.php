<?php
/**
 * Plugin Name: Orick Ferramentas
 * Description: Sistema de ferramentas (simuladores) com gate de cadastro/login, captura de leads e admin próprio.
 * Version: 2.2.1
 * Author: O Rick Silva
 * Text Domain: orick-ferramentas
 * Requires PHP: 7.4
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

define( 'ORICK_FERR_VERSION', '2.2.1' );
define( 'ORICK_FERR_DIR', plugin_dir_path( __FILE__ ) );
define( 'ORICK_FERR_URL', plugin_dir_url( __FILE__ ) );
define( 'ORICK_FERR_TABLE', 'orick_leads' ); // prefixada com $wpdb->prefix no uso

/* ---------- includes ---------- */
require_once ORICK_FERR_DIR . 'includes/install.php';
require_once ORICK_FERR_DIR . 'includes/cpt.php';
require_once ORICK_FERR_DIR . 'includes/render-tool-card.php';
require_once ORICK_FERR_DIR . 'includes/render-event-card.php';
require_once ORICK_FERR_DIR . 'includes/lateral-icons.php';
require_once ORICK_FERR_DIR . 'includes/cpt-material.php';
require_once ORICK_FERR_DIR . 'includes/cpt-video.php';
require_once ORICK_FERR_DIR . 'includes/cpt-episodio.php';
require_once ORICK_FERR_DIR . 'includes/episodio-helpers.php';
require_once ORICK_FERR_DIR . 'includes/cpt-evento.php';
require_once ORICK_FERR_DIR . 'includes/cpt-colunista.php';
require_once ORICK_FERR_DIR . 'includes/colunista.php';
require_once ORICK_FERR_DIR . 'includes/material-download.php';
require_once ORICK_FERR_DIR . 'includes/auth.php';
require_once ORICK_FERR_DIR . 'includes/state.php';
require_once ORICK_FERR_DIR . 'includes/forms.php';
require_once ORICK_FERR_DIR . 'includes/webhook.php';
require_once ORICK_FERR_DIR . 'includes/admin-leads.php';
require_once ORICK_FERR_DIR . 'includes/admin-settings.php';
require_once ORICK_FERR_DIR . 'includes/templates.php';
require_once ORICK_FERR_DIR . 'includes/shortcodes.php';
require_once ORICK_FERR_DIR . 'includes/newsletter-api.php';

/* ---------- lifecycle ---------- */
register_activation_hook( __FILE__, [ 'Orick_Ferr_Install', 'activate' ] );
register_deactivation_hook( __FILE__, [ 'Orick_Ferr_Install', 'deactivate' ] );

/* ---------- enqueue CSS ---------- */
add_action( 'wp_enqueue_scripts', function() {
    wp_enqueue_style(
        'orick-ferramentas',
        ORICK_FERR_URL . 'assets/css/ferramentas.css',
        [],
        ORICK_FERR_VERSION
    );
    wp_enqueue_script(
        'orick-ferramentas',
        ORICK_FERR_URL . 'assets/js/forms.js',
        [],
        ORICK_FERR_VERSION,
        true
    );
} );
