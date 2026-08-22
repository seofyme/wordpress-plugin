<?php
/**
 * Per-page Schema.org JSON-LD.
 *
 * @package SeofymeSEO
 */

namespace SeofymeSEO\Schema;

use SeofymeSEO\Support\Options;
use SeofymeSEO\Support\Post_Meta;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Outputs WebSite / Organization / Article|WebPage graph.
 */
class Graph {

	/**
	 * Register.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'wp_head', array( $this, 'output' ), 5 );
	}

	/**
	 * Print JSON-LD.
	 *
	 * @return void
	 */
	public function output() {
		if ( ! Options::get( 'schema_enabled' ) ) {
			return;
		}

		$org_name = Options::get( 'organization_name' ) ?: get_bloginfo( 'name' );
		$org_logo = Options::get( 'organization_logo' );
		$graph    = array();

		$graph[] = array(
			'@type' => 'Organization',
			'@id'   => home_url( '/#organization' ),
			'name'  => $org_name,
			'url'   => home_url( '/' ),
		);
		if ( $org_logo ) {
			$graph[0]['logo'] = array(
				'@type' => 'ImageObject',
				'url'   => $org_logo,
			);
		}

		$graph[] = array(
			'@type'           => 'WebSite',
			'@id'             => home_url( '/#website' ),
			'url'             => home_url( '/' ),
			'name'            => get_bloginfo( 'name' ),
			'publisher'       => array( '@id' => home_url( '/#organization' ) ),
			'potentialAction' => array(
				'@type'       => 'SearchAction',
				'target'      => home_url( '/?s={search_term_string}' ),
				'query-input' => 'required name=search_term_string',
			),
		);

		if ( is_singular() ) {
			$id   = get_queried_object_id();
			$type = is_singular( 'post' ) ? 'Article' : 'WebPage';
			$node = array(
				'@type'            => $type,
				'@id'              => get_permalink( $id ) . '#webpage',
				'url'              => get_permalink( $id ),
				'name'             => Post_Meta::resolved_title( $id ),
				'description'      => Post_Meta::resolved_description( $id ),
				'isPartOf'         => array( '@id' => home_url( '/#website' ) ),
				'datePublished'    => get_the_date( 'c', $id ),
				'dateModified'     => get_the_modified_date( 'c', $id ),
				'author'           => array(
					'@type' => 'Person',
					'name'  => get_the_author_meta( 'display_name', (int) get_post_field( 'post_author', $id ) ),
				),
			);
			$image = get_the_post_thumbnail_url( $id, 'full' );
			if ( $image ) {
				$node['image'] = array( $image );
			}
			$graph[] = $node;
		}

		$data = array(
			'@context' => 'https://schema.org',
			'@graph'   => $graph,
		);

		Json_Ld::print_script( $data );
	}
}
