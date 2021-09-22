<?php
/**
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              justdev.org
 * @since             0.0.1
 * @package           jd_support
 *
 * @wordpress-plugin
 * Plugin Name:       justDev Support
 * Plugin URI:        justdev.org
 * Description:       Plugin for dev tools.
 * Version:           0.0.1
 * Author:            Kyrylo Dorozhynskyi | justDev
 * Author URI:        justdev.org
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       jd_support
 * Domain Path:       /languages
 */

function hide_update_notice()
{
	if (!current_user_can('update_core')) {
		remove_action('admin_notices', 'update_nag', 3);
	}
}
add_action('admin_head', 'hide_update_notice', 1);
