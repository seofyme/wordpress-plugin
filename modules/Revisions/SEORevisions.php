<?php
/**
 * SEO revisions — history of title/description/focus changes.
 *
 * @package SeofymeSEO
 */

namespace SeofymeSEO\Modules\Revisions;

use SeofymeSEO\Support\Post_Meta;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Stores SEO field snapshots.
 */
class SEORevisions {

	public const META = '_seofyme_seo_revisions';

	/**
	 * Register.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'save_post', array( $this, 'snapshot' ), 40, 2 );
		add_action( 'add_meta_boxes', array( $this, 'box' ) );
		add_action( 'wp_ajax_seofyme_restore_revision', array( $this, 'ajax_restore' ) );
	}

	/**
	 * Snapshot on save.
	 *
	 * @param int      $post_id Post ID.
	 * @param \WP_Post $post Post.
	 * @return void
	 */
	public function snapshot( $post_id, $post ) {
		if ( wp_is_post_revision( $post_id ) || ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}
		$snap = array(
			'time'        => time(),
			'user'        => get_current_user_id(),
			'title'       => Post_Meta::get( $post_id, Post_Meta::TITLE ),
			'description' => Post_Meta::get( $post_id, Post_Meta::DESCRIPTION ),
			'focus'       => Post_Meta::get( $post_id, Post_Meta::FOCUS_KW ),
		);
		$history = get_post_meta( $post_id, self::META, true );
		if ( ! is_array( $history ) ) {
			$history = array();
		}
		$last = $history[0] ?? null;
		if ( $last && $last['title'] === $snap['title'] && $last['description'] === $snap['description'] && $last['focus'] === $snap['focus'] ) {
			return;
		}
		array_unshift( $history, $snap );
		$history = array_slice( $history, 0, 25 );
		update_post_meta( $post_id, self::META, $history );
	}

	/**
	 * Box.
	 *
	 * @return void
	 */
	public function box() {
		foreach ( get_post_types( array( 'public' => true ), 'names' ) as $type ) {
			if ( 'attachment' === $type ) {
				continue;
			}
			add_meta_box( 'seofyme_revisions', __( 'SEO revisions', 'seofyme-seo' ), array( $this, 'render' ), $type, 'side' );
		}
	}

	/**
	 * Render.
	 *
	 * @param \WP_Post $post Post.
	 * @return void
	 */
	public function render( $post ) {
		$history = get_post_meta( $post->ID, self::META, true );
		if ( empty( $history ) || ! is_array( $history ) ) {
			echo '<p>' . esc_html__( 'No SEO revisions yet.', 'seofyme-seo' ) . '</p>';
			return;
		}
		echo '<ul class="seofyme-suggestion-list">';
		foreach ( array_slice( $history, 0, 8 ) as $i => $row ) {
			$user = get_userdata( (int) $row['user'] );
			printf(
				'<li><strong>%s</strong><br><span class="description">%s</span><br><button type="button" class="button seofyme-restore-rev" data-post-id="%d" data-index="%d">%s</button></li>',
				esc_html( $row['title'] ?: __( '(empty title)', 'seofyme-seo' ) ),
				esc_html( ( $user ? $user->display_name . ' · ' : '' ) . gmdate( 'Y-m-d H:i', (int) $row['time'] ) ),
				(int) $post->ID,
				(int) $i,
				esc_html__( 'Restore', 'seofyme-seo' )
			);
		}
		echo '</ul>';
	}

	/**
	 * Restore.
	 *
	 * @return void
	 */
	public function ajax_restore() {
		check_ajax_referer( 'seofyme_seo', 'nonce' );
		$post_id = isset( $_POST['post_id'] ) ? (int) $_POST['post_id'] : 0;
		$index   = isset( $_POST['index'] ) ? (int) $_POST['index'] : -1;
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			wp_send_json_error( null, 403 );
		}
		$history = get_post_meta( $post_id, self::META, true );
		if ( ! isset( $history[ $index ] ) ) {
			wp_send_json_error( array( 'message' => 'missing' ) );
		}
		$row = $history[ $index ];
		Post_Meta::set( $post_id, Post_Meta::TITLE, $row['title'] );
		Post_Meta::set( $post_id, Post_Meta::DESCRIPTION, $row['description'] );
		Post_Meta::set( $post_id, Post_Meta::FOCUS_KW, $row['focus'] );
		wp_send_json_success( $row );
	}
}
