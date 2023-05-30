<?php

if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly
}

class JES_Anchor_Settings_Page {

	public function __construct() {
		add_action('admin_menu', array($this, 'add_admin_menu'));
		add_action('admin_init', array($this, 'init_settings'));
	}

	public function add_admin_menu() {
		// add_menu_page(
		// 	esc_html__( 'Anchor Episodes', 'text_domain' ),
		// 	esc_html__( 'Anchor Episodes', 'text_domain' ),
		// 	'manage_options',
		// 	'jes_anchor_settings',
		// 	array( $this, 'page_layout' ),
		// 	'dashicons-microphone',
		// 	99
		// );
		// switch to a wordpress settings submenu page
		add_submenu_page(
			'options-general.php',
			esc_html__('Anchor Episodes', 'text_domain'),
			esc_html__('Anchor Episodes', 'text_domain'),
			'manage_options',
			'jes_anchor_settings',
			array($this, 'page_layout')
		);
	}

	public function init_settings() {
		register_setting(
			'jes_anchor_settings',
			'jes_anchor_settings'
		);
		add_settings_section(
			'jes_anchor_settings_section',
			'',
			false,
			'jes_anchor_settings'
		);
		add_settings_field(
			'site_url',
			__('Anchor Site URL', 'text_domain'),
			array($this, 'render_site_url_field'),
			'jes_anchor_settings',
			'jes_anchor_settings_section'
		);
		add_settings_field(
			'anchor_rss_url',
			__('Anchor RSS URL', 'text_domain'),
			array($this, 'render_anchor_rss_url_field'),
			'jes_anchor_settings',
			'jes_anchor_settings_section'
		);
		add_settings_field(
			'max_episodes',
			__('Total Episodes To Display', 'text_domain'),
			array($this, 'render_max_episodes_field'),
			'jes_anchor_settings',
			'jes_anchor_settings_section'
		);

		do_action('jesaeip_add_settings_fields');
	}

	public function page_layout() {
		// Check required user capability
		if (!current_user_can('manage_options')) {
			wp_die(esc_html__('You do not have sufficient permissions to access this page.', 'text_domain'));
		}

		// if pro is not active, add a notice at the top of the page to upgrade
		if (!JESAEI_IS_PRO_ACTIVE) {
			echo '<div class="notice notice-warning">';
			echo '<p><strong>Upgrade to the new Anchor Episodes Index Pro for significant enhancements and more features!</strong></p>';
			echo '<p><a href="https://jesweb.dev/" target="_blank">View Pro Features</a></p>';
			echo '</div>';
		}

		// Admin Page Layout
		echo '<div class="wrap">';
		echo '<h1>' . get_admin_page_title() . '</h1>';
		echo '<h3>Output the player on any page with the below shortcode:</br></br>';
		echo '<span style="background-color: #cecece; padding: 8px;">[anchor_episodes]</span></h3>';
		echo '<form action="options.php" method="post">';

		// if pro is active add this field
		// if (JESAEI_IS_PRO_ACTIVE) {
		// 	// do_action('jesaeip_settings_page');
		// 	do_action('jesaeip_license_form');
		// }

		settings_fields('jes_anchor_settings');
		do_settings_sections('jes_anchor_settings');

		submit_button();

		echo '</form>';
		echo '</div>';
	}

	public function render_site_url_field() {
		// Retrieve data from the database.
		$options = get_option('jes_anchor_settings');

		// Set default value.
		$value = isset($options['site_url']) ? $options['site_url'] : '';

		// Field output.
		echo '<input type="url" name="jes_anchor_settings[site_url]" class="regular-text site_url_field" placeholder="' . esc_attr__('', 'text_domain') . '" value="' . esc_attr($value) . '">';
		echo '<p class="description">' . __('Looks like https://anchor.fm/{YOUR SITE NAME} or https://podcasters.spotify.com/pod/show/{YOUR SITE NAME} (make sure there is no "/" at the end)', 'text_domain') . '</p>';
	}

	public function render_anchor_rss_url_field() {

		// Retrieve data from the database.
		$options = get_option('jes_anchor_settings');

		// Set default value.
		$value = isset($options['anchor_rss_url']) ? $options['anchor_rss_url'] : '';

		// Field output.
		echo '<input type="url" name="jes_anchor_settings[anchor_rss_url]" class="regular-text anchor_rss_url_field" placeholder="' . esc_attr__('', 'text_domain') . '" value="' . esc_attr($value) . '">';
		echo '<p class="description">' . __('Found in your Anchor settings. Looks like https://anchor.fm/s/{YOUR SITE KEY}/podcast/rss (make sure there is no "/" at the end)', 'text_domain') . '</p>';
	}

	public function render_max_episodes_field() {

		// Retrieve data from the database.
		$options = get_option('jes_anchor_settings');

		// Set default value.
		$value = isset($options['max_episodes']) ? $options['max_episodes'] : '';

		// Field output.
		echo '<input type="number" name="jes_anchor_settings[max_episodes]" class="regular-text anchor_max_episodes_field" placeholder="' . esc_attr__('', 'text_domain') . '" value="' . esc_attr($value) . '">';
		echo '<p class="description">' . __('The amount of podcasts episodes you want to display. Default is 999.');
	}
}

new JES_Anchor_Settings_Page;
