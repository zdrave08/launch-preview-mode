<?php
/**
 * Uninstall Launch Preview Mode.
 *
 * @package LaunchPreviewMode
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_post_meta_by_key( '_lpm_launch_preview_enabled' );
delete_post_meta_by_key( '_lpm_launch_preview_note' );
