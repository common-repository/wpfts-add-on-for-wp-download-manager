<?php

/*
Plugin Name: WPFTS Add-on for WP Download Manager
Description: Implementing an indexing and searching files uploaded by WP Download Manager plugin
Version: 1.10.24
Tested up to: 6.6.1
Author: Epsiloncool
Author URI: https://e-wm.org
License: GPLv3
License URI: http://www.gnu.org/licenses/gpl-3.0.html
Text Domain: wpfts_addon_wpdm
Domain Path: /languages/
*/

/**
 *  Copyright 2013-2024 Epsiloncool
 *
 *  This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 ******************************************************************************
 *  I am thank you for the help by buying PRO version of this plugin 
 *  at https://fulltextsearch.org/ 
 *  It will keep me working further on this useful product.
 ******************************************************************************
 * 
 *  @copyright 2013-2024
 *  @license GPLv3
 *  @version 1.10.24
 *  @package WPFTS Addon for WP Download Manager
 *  @author Epsiloncool <info@e-wm.org>
 */

 /**
  * This addon is intended to be run with WP Fast Total Search Pro plugin
  * 
  * It will help WPFTS to find and index files, which are mantained by WP Download Manager plugin.
  */
 
global $wpdm_session_force_rebuild;
 
$wpdm_session_force_rebuild = array(); 
 
function WPFTS_DM_ADDON_is_url($url)
{
	$result = ( false !== filter_var( $url, FILTER_VALIDATE_URL ) );
	return apply_filters( '__is_url', $result, $url );
}
 
// This function is a replication of the original WPDM's function to get the real path from the stored file path
// Using WPDM()->fileSystem->absPath($rel_file) generates the error.
function WPFTS_DM_ADDON_absPath($rel_path, $pid = null)
{
	$upload_dir = wp_upload_dir();
 
	if(!defined('UPLOAD_DIR')) {
		define('UPLOAD_DIR',$upload_dir.'/download-manager-files/');
	}
 
	$abs_path = false;
 
	$upload_dir = wp_upload_dir();
	$upload_base_url = $upload_dir['baseurl'];
	$upload_dir = $upload_dir['basedir'];
 
	if (WPFTS_DM_ADDON_is_url($rel_path)) {
		$abs_path = str_replace($upload_base_url, $upload_dir, $rel_path);
		if (WPFTS_DM_ADDON_is_url($abs_path))
			return $rel_path;
	}
 
	if (substr_count($rel_path, './'))
		return false;
 
	$fixed_abs_path = false;
	if (substr_count($rel_path, 'wp-content') > 0 && substr_count($rel_path, WP_CONTENT_DIR) === 0) {
		$rel_rel_path = explode("wp-content", $rel_path);
		$rel_rel_path = end($rel_rel_path);
		$fixed_abs_path = WP_CONTENT_DIR . $rel_rel_path;
	}
 
	$file_browser_root = get_option('_wpdm_file_browser_root', '');
	$network_upload_dir = explode("sites", UPLOAD_DIR);
	$network_upload_dir = $network_upload_dir[0];
	$network_upload_dir = $network_upload_dir."download-manager-files/";
 
	if (file_exists($rel_path))
		$abs_path = $rel_path;
	else if (file_exists(UPLOAD_DIR.$rel_path))
		$abs_path = UPLOAD_DIR.$rel_path;
	else if (file_exists($network_upload_dir.$rel_path))
		$abs_path = $network_upload_dir.$rel_path;
	else if (file_exists(ABSPATH.$rel_path))
		$abs_path = ABSPATH.$rel_path;
	else if (file_exists($file_browser_root.$rel_path))
		$abs_path = $file_browser_root.$rel_path;
	else if ($fixed_abs_path && file_exists($fixed_abs_path))
		$abs_path = $fixed_abs_path;
	else if ($pid) {
		$user_upload_dir = null;
		$package = get_post($pid);
		if (is_object($package)){
			$author = get_user_by('id', $package->post_author);
			if ($author)
				$user_upload_dir = UPLOAD_DIR . $author->user_login . '/';
		}
		if($user_upload_dir && file_exists($user_upload_dir.$rel_path))
			$abs_path = $user_upload_dir.$rel_path;
	}
 
	$abs_path = str_replace('\\','/', $abs_path );

	if(!$abs_path) return null;

	$real_path = realpath($abs_path);

	return $real_path;
}
 
add_action('save_post', function($post_id) 
{
	// We need to clear raw cache for wpdmpro posts
	global $wpdm_session_force_rebuild;

	if ((!$wpdm_session_force_rebuild) || (!is_array($wpdm_session_force_rebuild))) {
		$wpdm_session_force_rebuild = array();
	}

	$wpdm_session_force_rebuild[$post_id] = 1;

}, 95);

add_action('init', function()
{
	// This hook will be called after WPFTS's init hook, but before irules_collect
	if (!defined('WPFTS_VERSION')) {
		// We require WPFTS to be ran
		return;
	}
	
	if (version_compare(WPFTS_VERSION, '2.51.212', '<=') && version_compare(WPFTS_VERSION, '2.0.0', '>=')) {
		// Using v2 algorithm
		add_filter('wpfts_index_post', function($index, $p)
		{
			global $wpfts_core, $wpdm_session_force_rebuild;

			if (($wpfts_core) && ($p->post_type == 'wpdmpro')) {

				$is_force_rebuild = false;
				if ($wpdm_session_force_rebuild && isset($wpdm_session_force_rebuild[$p->ID]) && ($wpdm_session_force_rebuild[$p->ID] > 0)) {
					$is_force_rebuild = true;
				}
 
				$files_a = get_post_meta($p->ID, '__wpdm_files', true);

				// New Download Manager version uses non-zero-based keys for file lists
				if ($files_a && is_array($files_a)) {
					// Make them zero-based
					$files_a = array_values($files_a);
				}
 
				if ($files_a && is_array($files_a) && (count($files_a) > 0)) {
					// Get filename from this link
					$updir_data = wp_get_upload_dir();
 
					require_once $wpfts_core->root_dir.'/includes/wpfts_utils.class.php';
 
					$index['wpdm_content'] = '';
 
					foreach ($files_a as $file0) {
				 		if (strlen($file0) < 1) {
							continue;
						}
 
						try {
							if (function_exists('WPDM')) {
								$full_file = WPFTS_DM_ADDON_absPath($file0);
						 
								if (($full_file) && (file_exists($full_file)) && is_file($full_file)) {
									$file0 = $full_file;
								}
							}
					 
						} catch (Exception $e) {
							// Something went wrong
						}
				 
						$local = false;
						$is_local_file = true;
						if (preg_match('~^http(s)?\x3a\/\/~', $file0)) {
							// This is http link
							$local = $file0;
							$is_local_file = false;
						} elseif (preg_match('~^\/~', $file0) || preg_match('~^[a-zA-Z_]+\x3a~i', $file0)) {
							// This is a server local file
							$local = $file0;
						} elseif ((preg_match('~^wp\-content\/uploads~', $file0))) {
							// Part of the local path
							$local = $updir_data['basedir'].substr($file0, 18);
						} else {
							// Should be a local file
							$local = $updir_data['basedir'].'/download-manager-files/'.$file0;
						}
 
						if ($local) {
							$ret = WPFTS_Utils::GetCachedFileContent_ByLocalLink($local, $is_force_rebuild, $is_local_file);
				 
							$index['wpdm_content'] .= (isset($ret['post_content']) ? trim($ret['post_content']) : '')." \n\n";
						}
					}
				}
 
				if ($wpdm_session_force_rebuild && isset($wpdm_session_force_rebuild[$p->ID]) && ($wpdm_session_force_rebuild[$p->ID] > 0)) {
					$wpdm_session_force_rebuild[$p->ID] = 0;	// Rebuild only once per session
				}
			}
 
			return $index;
		}, 3, 2);
	} elseif (version_compare(WPFTS_VERSION, '3.0.0', '>=')) {
		// Using v3 algorithm (see below)
	} else {
		// v1 is free, not supported
		return;
	}

	// WPFTS Pro v3 is using IRules to define indexing rules
	add_filter('wpfts_irules_before', function($irules)
	{
		$irule = array(
			'filter' => array(
				'post_type' => 'wpdmpro',
			),
			'actions' => array(
				array(
					'call' => 'wpftspro_addon_dm',
				)
			),
			'short' => array(
				'.__wpdm_files' => array('wpdm_content'), 
			),
			'ident' => 'wpfts_pro/addon_dn',
			'name' => 'WPFTS Addon for Download Manager',
			'description' => __('Index files attached to wpdmpro records so they become searchable by files content', 'wpfts_addon_wpdm'),
			'ver' => '1.0',
			'defined_by' => 'WPFTS Add-on for WP Download Manager',
			'ord' => 100,
		);

		$irules[] = $irule;

		return $irules;
	});

	add_filter('wpfts_irules_call/wpftspro_addon_dm', function($chunks, $post, $props, $t_rule)
	{
		global $wpfts_core, $wpdm_session_force_rebuild;

		if (!$wpfts_core) {
			return $chunks;
		}

		if (!($post && isset($post->ID) && ($post->ID > 0))) {
			return $chunks;
		}

		$post_id = $post->ID;

		$is_force_rebuild = false;
		if ($wpdm_session_force_rebuild && isset($wpdm_session_force_rebuild[$post_id]) && ($wpdm_session_force_rebuild[$post_id] > 0)) {
			$is_force_rebuild = true;
		}

		$files_a = get_post_meta($post_id, '__wpdm_files', true);

		// New Download Manager version uses non-zero-based keys for file lists
		if ($files_a && is_array($files_a)) {
			// Make them zero-based
			$files_a = array_values($files_a);
		}

		if ($files_a && is_array($files_a) && (count($files_a) > 0)) {
			// Get filename from this link
			$updir_data = wp_get_upload_dir();

			require_once $wpfts_core->root_dir.'/includes/wpfts_utils.class.php';

			$sum = array();

			foreach ($files_a as $file0) {
				 if (strlen($file0) < 1) {
					continue;
				}

				try {
					if (function_exists('WPDM')) {
						$full_file = WPFTS_DM_ADDON_absPath($file0);
				 
						if (($full_file) && (file_exists($full_file)) && is_file($full_file)) {
							$file0 = $full_file;
						}
					}
			 
				} catch (Exception $e) {
					// Something went wrong
				}
		 
				$local = false;
				$is_local_file = true;
				if (preg_match('~^http(s)?\x3a\/\/~', $file0)) {
					// This is http link
					$local = $file0;
					$is_local_file = false;
				} elseif (preg_match('~^\/~', $file0) || preg_match('~^[a-zA-Z_]+\x3a~i', $file0)) {
					// This is a server local file
					$local = $file0;
				} elseif ((preg_match('~^wp\-content\/uploads~', $file0))) {
					// Part of the local path
					$local = $updir_data['basedir'].substr($file0, 18);
				} else {
					// Should be a local file
					$local = $updir_data['basedir'].'/download-manager-files/'.$file0;
				}

				if ($local) {
					$ret = WPFTS_Utils::GetCachedFileContent_ByLocalLink($local, $is_force_rebuild, $is_local_file);
		 
					$sum[] = isset($ret['post_content']) ? $ret['post_content'] : '';
					if (isset($ret['__debug'])) {
						if ((!isset($chunks['__debug'])) || (!is_array($chunks['__debug']))) {
							$chunks['__debug'] = array();
						}
						$chunks['__debug'] = array_merge($chunks['__debug'], $ret['__debug']);
					}								
				}
			}
			$chunks['wpdm_content'] = $sum;
		}

		if ($wpdm_session_force_rebuild && isset($wpdm_session_force_rebuild[$post_id]) && ($wpdm_session_force_rebuild[$post_id] > 0)) {
			$wpdm_session_force_rebuild[$post_id] = 0;	// Rebuild only once per session
		}

		return $chunks;
	}, 10, 4);

}, 1000);
 
function wpfts_wpdmpro_getCachedContent($id)
{
	global $wpfts_core;

	if ($wpfts_core) {
		// Dirty hack for one domain
		$h = home_url();
		$z = array();
		if (($id == 5908) && (preg_match('~nanoe\.org~', $h, $z))) {
			// This website breaks execution on this post_id by unknown reason. Since I have no access to its code, this is the only solution for the moment
			return array();
		}

		$chunks = $wpfts_core->getPostChunks($id);

		if ($chunks) {
			foreach ($chunks as $k => $ch) {
				if ($k === 'wpdm_content') {
					return $ch;
				}
			}
		}
 
	}

	return '';
}
 
add_filter('wpdm_after_prepare_package_data', function($post_vars)
{
	$id = isset($post_vars['ID']) ? intval($post_vars['ID']) : '';
 
	$txt = '';
	if ($id > 0) {
		// Get raw text from the WPFTS Pro cache
		$t = wpfts_wpdmpro_getCachedContent($id);
		$txt = isset($t['post_content']) ? trim($t['post_content']) : '';
	}
 
	$post_vars['wpfts_dm_rawtext'] = $txt;
	$post_vars['wpfts_dm_rawtext_esc'] = htmlspecialchars($txt);
	$post_vars['wpfts_dm_rawtext_pre'] = '<pre class="wpfts_dm_rawtext_pre">'.htmlspecialchars($txt).'</pre>';
	$post_vars['wpfts_dm_rawtext_div'] = '<div class="wpfts_dm_rawtext_div">'.htmlspecialchars($txt).'</div>';
 
	return $post_vars;
}, 200);
 
