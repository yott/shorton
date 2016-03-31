<?php
/*
 * Plugin Name: Shorton
 * Description: This plugin allows you to define shortcodes via JSON and enable WP integrations for them.
 * Plugin URI: https://github.com/yott/wp-shortcode
 * Author: Logan Yott
 * Version: 0.1
 * Author URI: https://github.com/loganyott
 */
if ( class_exists( "Yott\\WP\\Shortcode" ) ) {
    Yott\WP\Shortcode::init();
}
