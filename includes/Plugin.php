<?php
/**
 * Main plugin bootstrap.
 *
 * @package SeofymeSEO
 */

namespace SeofymeSEO;

use SeofymeSEO\Admin\Admin;
use SeofymeSEO\Admin\Metabox;
use SeofymeSEO\Analysis\Analyzer;
use SeofymeSEO\Frontend\Head;
use SeofymeSEO\Frontend\Robots;
use SeofymeSEO\Schema\Graph;
use SeofymeSEO\Sitemap\Sitemap;
use SeofymeSEO\Support\Options;
use SeofymeSEO\Modules\Redirects\Redirects;
use SeofymeSEO\Modules\Keyphrases\Keyphrases;
use SeofymeSEO\Modules\InternalLinking\InternalLinking;
use SeofymeSEO\Modules\InternalLinking\OrphanedContent;
use SeofymeSEO\Modules\Social\Social;
use SeofymeSEO\Modules\BotBlocker\BotBlocker;
use SeofymeSEO\Modules\IndexNow\IndexNow;
use SeofymeSEO\Modules\AI\Generator as AIGenerator;
use SeofymeSEO\Modules\AI\BulkMeta;
use SeofymeSEO\Modules\ContentPlanner\ContentPlanner;
use SeofymeSEO\Modules\LocalSEO\LocalSEO;
use SeofymeSEO\Modules\VideoSEO\VideoSEO;
use SeofymeSEO\Modules\NewsSEO\NewsSEO;
use SeofymeSEO\Modules\SchemaAggregator\SchemaAggregator;
use SeofymeSEO\Modules\FrontendInspector\FrontendInspector;
use SeofymeSEO\Modules\Workouts\Workouts;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Boots core + feature modules.
 */
class Plugin {

	/**
	 * Singleton.
	 *
	 * @var self|null
	 */
	private static $instance = null;

	/**
	 * Registered services.
	 *
	 * @var object[]
	 */
	private $services = array();

	/**
	 * Instance.
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
	 * Activation.
	 *
	 * @return void
	 */
	public static function activate() {
		Options::ensure_defaults();
		Redirects::install_table();
		flush_rewrite_rules();
	}

	/**
	 * Deactivation.
	 *
	 * @return void
	 */
	public static function deactivate() {
		flush_rewrite_rules();
	}

	/**
	 * Init hooks.
	 *
	 * @return void
	 */
	public function init() {
		load_plugin_textdomain( 'seofyme-seo', false, dirname( SEOFYME_SEO_BASENAME ) . '/languages' );

		$this->services = array(
			new Options(),
			new Admin(),
			new Metabox(),
			new Head(),
			new Robots(),
			new Sitemap(),
			new Graph(),
			new Analyzer(),
			new Redirects(),
			new Keyphrases(),
			new InternalLinking(),
			new OrphanedContent(),
			new Social(),
			new BotBlocker(),
			new IndexNow(),
			new AIGenerator(),
			new BulkMeta(),
			new ContentPlanner(),
			new LocalSEO(),
			new VideoSEO(),
			new NewsSEO(),
			new SchemaAggregator(),
			new FrontendInspector(),
			new Workouts(),
		);

		foreach ( $this->services as $service ) {
			if ( method_exists( $service, 'register' ) ) {
				$service->register();
			}
		}
	}
}
