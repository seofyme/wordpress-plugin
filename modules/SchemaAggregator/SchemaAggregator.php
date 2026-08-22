<?php
/**
 * Site-wide deduplicated schema graph endpoint.
 *
 * @package SeofymeSEO
 */

namespace SeofymeSEO\Modules\SchemaAggregator;

use SeofymeSEO\Modules\LocalSEO\LocalSEO;
use SeofymeSEO\Schema\Json_Ld;
use SeofymeSEO\Support\Options;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Exposes /seofyme-schema.json
 */
class SchemaAggregator {

	/**
	 * Register.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'init', array( $this, 'rewrite' ) );
		add_action( 'template_redirect', array( $this, 'render' ) );
		add_action(
			'rest_api_init',
			function () {
				register_rest_route(
					'seofyme/v1',
					'/schema-graph',
					array(
						'methods'             => 'GET',
						'permission_callback' => '__return_true',
						'callback'            => function () {
							if ( ! Options::get( 'schema_aggregate' ) ) {
								return new \WP_Error( 'disabled', 'Disabled', array( 'status' => 404 ) );
							}
							return rest_ensure_response( $this->graph() );
						},
					)
				);
			}
		);
	}

	/**
	 * Rewrite.
	 *
	 * @return void
	 */
	public function rewrite() {
		add_rewrite_rule( '^seofyme-schema\.json$', 'index.php?seofyme_schema_graph=1', 'top' );
		add_rewrite_tag( '%seofyme_schema_graph%', '1' );
	}

	/**
	 * Render.
	 *
	 * @return void
	 */
	public function render() {
		if ( ! get_query_var( 'seofyme_schema_graph' ) || ! Options::get( 'schema_aggregate' ) ) {
			return;
		}
		header( 'Content-Type: application/ld+json; charset=utf-8' );
		echo Json_Ld::encode( $this->graph(), JSON_PRETTY_PRINT ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- JSON encoded with JSON_HEX_TAG.
		exit;
	}

	/**
	 * Build graph.
	 *
	 * @return array
	 */
	public function graph() {
		$org = array(
			'@type' => 'Organization',
			'@id'   => home_url( '/#organization' ),
			'name'  => Options::get( 'organization_name' ) ?: get_bloginfo( 'name' ),
			'url'   => home_url( '/' ),
		);
		$site = array(
			'@type' => 'WebSite',
			'@id'   => home_url( '/#website' ),
			'url'   => home_url( '/' ),
			'name'  => get_bloginfo( 'name' ),
			'publisher' => array( '@id' => $org['@id'] ),
		);
		$graph = array( $org, $site );
		$posts = get_posts(
			array(
				'post_type'      => array( 'post', 'page', LocalSEO::CPT ),
				'post_status'    => 'publish',
				'posts_per_page' => 100,
				'no_found_rows'  => true,
			)
		);
		foreach ( $posts as $post ) {
			$graph[] = array(
				'@type' => 'page' === $post->post_type ? 'WebPage' : ( LocalSEO::CPT === $post->post_type ? 'LocalBusiness' : 'Article' ),
				'@id'   => get_permalink( $post ) . '#webpage',
				'url'   => get_permalink( $post ),
				'name'  => get_the_title( $post ),
				'isPartOf' => array( '@id' => $site['@id'] ),
			);
		}
		return array( '@context' => 'https://schema.org', '@graph' => $graph );
	}
}
