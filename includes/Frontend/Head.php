<?php
/**
 * Front-end head output.
 *
 * @package SeofymeSEO
 */

namespace SeofymeSEO\Frontend;

use SeofymeSEO\Support\Options;
use SeofymeSEO\Support\Post_Meta;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Titles, descriptions, canonicals.
 */
class Head {

	/**
	 * Register.
	 *
	 * @return void
	 */
	public function register() {
		add_filter( 'pre_get_document_title', array( $this, 'document_title' ), 20 );
		add_filter( 'wp_title', array( $this, 'document_title' ), 20 );
		add_action( 'wp_head', array( $this, 'output' ), 1 );
	}

	/**
	 * Document title.
	 *
	 * @param string $title Title.
	 * @return string
	 */
	public function document_title( $title ) {
		if ( is_singular() ) {
			return Post_Meta::resolved_title( get_queried_object_id() );
		}
		if ( is_front_page() ) {
			$custom = Options::get( 'homepage_title' );
			if ( $custom ) {
				return $custom;
			}
		}
		return $title;
	}

	/**
	 * Meta tags.
	 *
	 * @return void
	 */
	public function output() {
		if ( is_singular() ) {
			$id          = get_queried_object_id();
			$description = Post_Meta::resolved_description( $id );
			$canonical   = Post_Meta::get( $id, Post_Meta::CANONICAL );
			$robots      = Post_Meta::get( $id, Post_Meta::ROBOTS, 'index,follow' );

			if ( $description ) {
				echo '<meta name="description" content="' . esc_attr( $description ) . '" />' . "\n";
			}
			echo '<link rel="canonical" href="' . esc_url( $canonical ? $canonical : get_permalink( $id ) ) . '" />' . "\n";
			echo '<meta name="robots" content="' . esc_attr( $robots ) . '" />' . "\n";
			return;
		}

		if ( is_front_page() ) {
			$description = Options::get( 'homepage_description' );
			if ( $description ) {
				echo '<meta name="description" content="' . esc_attr( $description ) . '" />' . "\n";
			}
			echo '<link rel="canonical" href="' . esc_url( home_url( '/' ) ) . '" />' . "\n";
		}

		if ( is_search() && Options::get( 'noindex_search' ) ) {
			echo '<meta name="robots" content="noindex,follow" />' . "\n";
		}
		if ( is_author() && Options::get( 'noindex_author' ) ) {
			echo '<meta name="robots" content="noindex,follow" />' . "\n";
		}
	}
}
