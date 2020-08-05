<?php
/**
 * WooSheets Dependency Checker
 *
 */
class Wc_WooSheets_Dependencies {
	private static $active_plugins;
	static function init() {
		self::$active_plugins = (array) get_option( 'active_plugins', array() );
	}
	/**
	 * Check woocommerce exist
	 * @return Boolean
	 */
	public static function woocommerce_active_check() {
		if (!self::$active_plugins)
			self::init();
		return in_array('woocommerce/woocommerce.php', self::$active_plugins) || array_key_exists('woocommerce/woocommerce.php', self::$active_plugins);
	}
	/**
	 * Check if woocommerce active
	 * @return Boolean
	 */
	public static function is_woocommerce_active() {
		return self::woocommerce_active_check();
	}
}
