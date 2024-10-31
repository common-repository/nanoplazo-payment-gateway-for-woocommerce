<?php
/**
 * Nanoplazo Uninstall
 *
 * Uninstalling NanoPlazo deletes pages, options.
 */

if (!defined('WP_UNINSTALL_PLUGIN')) {
    die;
}
delete_option('nanoplazo_quick_checkout');
delete_option('nanoplazo_wsppc_hook');