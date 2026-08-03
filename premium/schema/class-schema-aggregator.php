<?php
/**
 * Schema aggregation — single deduplicated site graph endpoint.
 *
 * @package Seofyme\SEO\Premium
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Exposes /wp-json/seofyme/v1/schema-graph and optional head injection.
 */
class Seofyme_Schema_Aggregator {

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'rest_api_init', [ $this, 'register_rest' ] );
		add_action( 'init', [ $this, 'rewrite' ] );
		add_action( 'template_redirect', [ $this, 'render_endpoint' ] );
	}

	/**
	 * Pretty endpoint.
	 *
	 * @return void
	 */
	public function rewrite() {
		add_rewrite_rule( '^seofyme-schema\.json$', 'index.php?seofyme_schema_graph=1', 'top' );
		add_rewrite_tag( '%seofyme_schema_graph%', '1' );
	}

	/**
	 * REST.
	 *
	 * @return void
	 */
	public function register_rest() {
		register_rest_route(
			'seofyme/v1',
			'/schema-graph',
			[
				'methods'             => 'GET',
				'permission_callback' => '__return_true',
				'callback'            => function () {
					if ( ! get_option( 'seofyme_schema_aggregate', true ) ) {
						return new WP_Error( 'disabled', 'Schema aggregation disabled', [ 'status' => 404 ] );
					}
					return rest_ensure_response( $this->build_graph() );
				},
			]
		);
	}

	/**
	 * Render pretty URL.
	 *
	 * @return void
	 */
	public function render_endpoint() {
		if ( ! get_query_var( 'seofyme_schema_graph' ) ) {
			return;
		}
		if ( ! get_option( 'seofyme_schema_aggregate', true ) ) {
			status_header( 404 );
			exit;
		}
		header( 'Content-Type: application/ld+json; charset=utf-8' );
		echo wp_json_encode( $this->build_graph(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT );
		exit;
	}

	/**
	 * Build aggregated graph.
	 *
	 * @return array
	 */
	public function build_graph() {
		$graph = [];
		$seen  = [];

		$org = [
			'@type' => 'Organization',
			'@id'   => home_url( '/#organization' ),
			'name'  => get_bloginfo( 'name' ),
			'url'   => home_url( '/' ),
		];
		$graph[] = $org;
		$seen[ $org['@id'] ] = true;

		$website = [
			'@type' => 'WebSite',
			'@id'   => home_url( '/#website' ),
			'url'   => home_url( '/' ),
			'name'  => get_bloginfo( 'name' ),
			'publisher' => [ '@id' => $org['@id'] ],
			'potentialAction' => [
				'@type'       => 'SearchAction',
				'target'      => home_url( '/?s={search_term_string}' ),
				'query-input' => 'required name=search_term_string',
			],
		];
		$graph[] = $website;

		$posts = get_posts(
			[
				'post_type'      => [ 'post', 'page', Seofyme_Local_SEO::CPT ],
				'post_status'    => 'publish',
				'posts_per_page' => 100,
				'no_found_rows'  => true,
			]
		);

		foreach ( $posts as $post ) {
			$id = get_permalink( $post ) . '#webpage';
			if ( isset( $seen[ $id ] ) ) {
				continue;
			}
			$seen[ $id ] = true;
			$node        = [
				'@type'       => $post->post_type === 'page' ? 'WebPage' : ( $post->post_type === Seofyme_Local_SEO::CPT ? 'LocalBusiness' : 'Article' ),
				'@id'         => $id,
				'url'         => get_permalink( $post ),
				'name'        => get_the_title( $post ),
				'isPartOf'    => [ '@id' => $website['@id'] ],
				'datePublished' => get_the_date( 'c', $post ),
				'dateModified'  => get_the_modified_date( 'c', $post ),
			];
			$author = get_the_author_meta( 'display_name', (int) $post->post_author );
			if ( $author ) {
				$node['author'] = [
					'@type' => 'Person',
					'name'  => $author,
				];
			}
			$graph[] = $node;
		}

		return [
			'@context' => 'https://schema.org',
			'@graph'   => $graph,
		];
	}
}
