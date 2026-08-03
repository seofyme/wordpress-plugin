<?php
/**
 * Simple autoloader for Seofyme Premium classes.
 *
 * @package Seofyme\SEO\Premium
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

spl_autoload_register(
	static function ( $class ) {
		if ( strpos( $class, 'Seofyme_' ) !== 0 ) {
			return;
		}

		$map = [
			'Seofyme_Premium'                 => 'class-premium.php',
			'Seofyme_Redirect_Repository'     => 'redirects/class-redirect-repository.php',
			'Seofyme_Redirect_Manager'        => 'redirects/class-redirect-manager.php',
			'Seofyme_Redirect_Admin'          => 'redirects/class-redirect-admin.php',
			'Seofyme_Redirect_Watcher'        => 'redirects/class-redirect-watcher.php',
			'Seofyme_Multi_Keyphrase'         => 'keyphrases/class-multi-keyphrase.php',
			'Seofyme_Internal_Linking'        => 'internal-linking/class-internal-linking.php',
			'Seofyme_Orphaned_Content'        => 'internal-linking/class-orphaned-content.php',
			'Seofyme_Social_Previews'         => 'social/class-social-previews.php',
			'Seofyme_Bot_Blocker'             => 'bot-blocker/class-bot-blocker.php',
			'Seofyme_IndexNow'                => 'indexnow/class-indexnow.php',
			'Seofyme_AI_Generator'            => 'ai/class-ai-generator.php',
			'Seofyme_Bulk_Meta'               => 'ai/class-bulk-meta.php',
			'Seofyme_Content_Planner'         => 'content-planner/class-content-planner.php',
			'Seofyme_Local_SEO'               => 'local-seo/class-local-seo.php',
			'Seofyme_Video_SEO'               => 'video-seo/class-video-seo.php',
			'Seofyme_News_SEO'                => 'news-seo/class-news-seo.php',
			'Seofyme_Schema_Aggregator'       => 'schema/class-schema-aggregator.php',
			'Seofyme_Frontend_Inspector'      => 'frontend-inspector/class-frontend-inspector.php',
			'Seofyme_Workouts'                => 'workouts/class-workouts.php',
			'Seofyme_Premium_Admin'           => 'admin/class-premium-admin.php',
		];

		if ( ! isset( $map[ $class ] ) ) {
			return;
		}

		$file = SEOFYME_PREMIUM_PATH . $map[ $class ];
		if ( is_readable( $file ) ) {
			require_once $file;
		}
	}
);
