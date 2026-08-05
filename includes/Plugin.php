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
use SeofymeSEO\Modules\InternalLinking\Linking_Block;
use SeofymeSEO\Modules\Social\Social;
use SeofymeSEO\Modules\BotBlocker\BotBlocker;
use SeofymeSEO\Modules\LlmsTxt\LlmsTxt;
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
use SeofymeSEO\Modules\NotFound\NotFoundMonitor;
use SeofymeSEO\Modules\ImageSEO\ImageSEO;
use SeofymeSEO\Modules\Headline\HeadlineAnalyzer;
use SeofymeSEO\Modules\AdvancedSchema\AdvancedSchema;
use SeofymeSEO\Modules\AuthorSEO\AuthorSEO;
use SeofymeSEO\Modules\Revisions\SEORevisions;
use SeofymeSEO\Modules\SiteAudit\SiteAudit;
use SeofymeSEO\Modules\WooCommerce\WooCommerceSEO;
use SeofymeSEO\Modules\RankTracker\RankTracker;
use SeofymeSEO\Modules\LinkAssistant\LinkAssistant;
use SeofymeSEO\Modules\WhiteLabel\WhiteLabel;
use SeofymeSEO\Modules\Reports\EmailReports;

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
		NotFoundMonitor::install_table();
		if ( ! wp_next_scheduled( EmailReports::HOOK ) && Options::get( 'email_reports', false ) ) {
			wp_schedule_event( time() + HOUR_IN_SECONDS, 'weekly', EmailReports::HOOK );
		}
		flush_rewrite_rules();
	}

	/**
	 * Deactivation.
	 *
	 * @return void
	 */
	public static function deactivate() {
		$timestamp = wp_next_scheduled( EmailReports::HOOK );
		if ( $timestamp ) {
			wp_unschedule_event( $timestamp, EmailReports::HOOK );
		}
		flush_rewrite_rules();
	}

	/**
	 * Init hooks.
	 *
	 * @return void
	 */
	public function init() {
		// Ensure new DB tables exist after updates.
		Redirects::install_table();
		NotFoundMonitor::install_table();

		$this->services = array(
			new Options(),
			new WhiteLabel(),
			new Admin(),
			new Metabox(),
			new Head(),
			new Robots(),
			new Sitemap(),
			new Graph(),
			new Analyzer(),
			new Redirects(),
			new NotFoundMonitor(),
			new Keyphrases(),
			new InternalLinking(),
			new OrphanedContent(),
			new Linking_Block(),
			new LinkAssistant(),
			new Social(),
			new BotBlocker(),
			new LlmsTxt(),
			new IndexNow(),
			new AIGenerator(),
			new BulkMeta(),
			new ContentPlanner(),
			new LocalSEO(),
			new VideoSEO(),
			new NewsSEO(),
			new SchemaAggregator(),
			new AdvancedSchema(),
			new AuthorSEO(),
			new SEORevisions(),
			new ImageSEO(),
			new HeadlineAnalyzer(),
			new SiteAudit(),
			new WooCommerceSEO(),
			new RankTracker(),
			new FrontendInspector(),
			new Workouts(),
			new EmailReports(),
		);

		foreach ( $this->services as $service ) {
			if ( method_exists( $service, 'register' ) ) {
				$service->register();
			}
		}
	}
}
