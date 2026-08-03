<?php
/**
 * Seofyme Premium feature orchestrator.
 *
 * @package Seofyme\SEO\Premium
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Boots all premium-parity modules.
 */
class Seofyme_Premium {

	/**
	 * Singleton instance.
	 *
	 * @var self|null
	 */
	private static $instance = null;

	/**
	 * Module instances.
	 *
	 * @var object[]
	 */
	private $modules = [];

	/**
	 * Get singleton.
	 *
	 * @return self
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Initialize modules.
	 *
	 * @return void
	 */
	public function init() {
		$this->modules = [
			'admin'             => new Seofyme_Premium_Admin(),
			'redirects'         => new Seofyme_Redirect_Manager(),
			'redirect_admin'    => new Seofyme_Redirect_Admin(),
			'redirect_watcher'  => new Seofyme_Redirect_Watcher(),
			'keyphrases'        => new Seofyme_Multi_Keyphrase(),
			'internal_linking'  => new Seofyme_Internal_Linking(),
			'orphaned'          => new Seofyme_Orphaned_Content(),
			'social'            => new Seofyme_Social_Previews(),
			'bot_blocker'       => new Seofyme_Bot_Blocker(),
			'indexnow'          => new Seofyme_IndexNow(),
			'ai'                => new Seofyme_AI_Generator(),
			'bulk_meta'         => new Seofyme_Bulk_Meta(),
			'content_planner'   => new Seofyme_Content_Planner(),
			'local'             => new Seofyme_Local_SEO(),
			'video'             => new Seofyme_Video_SEO(),
			'news'              => new Seofyme_News_SEO(),
			'schema_aggregator' => new Seofyme_Schema_Aggregator(),
			'frontend_inspector'=> new Seofyme_Frontend_Inspector(),
			'workouts'          => new Seofyme_Workouts(),
		];

		foreach ( $this->modules as $module ) {
			if ( method_exists( $module, 'register' ) ) {
				$module->register();
			}
		}

		add_filter( 'wpseo_product_name', [ $this, 'product_name' ] );
	}

	/**
	 * User-facing product name.
	 *
	 * @return string
	 */
	public function product_name() {
		return 'Seofyme SEO';
	}

	/**
	 * Get a module by key.
	 *
	 * @param string $key Module key.
	 * @return object|null
	 */
	public function module( $key ) {
		return $this->modules[ $key ] ?? null;
	}
}
