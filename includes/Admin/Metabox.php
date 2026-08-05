<?php
/**
 * Post editor SEO metabox.
 *
 * @package SeofymeSEO
 */

namespace SeofymeSEO\Admin;

use SeofymeSEO\Analysis\Analyzer;
use SeofymeSEO\Support\Post_Meta;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Classic/block editor metabox for Seofyme fields.
 */
class Metabox {

	/**
	 * Register.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'add_meta_boxes', array( $this, 'add' ) );
		add_action( 'save_post', array( $this, 'save' ), 10, 2 );
	}

	/**
	 * Add metabox.
	 *
	 * @return void
	 */
	public function add() {
		foreach ( get_post_types( array( 'public' => true ), 'names' ) as $type ) {
			if ( 'attachment' === $type ) {
				continue;
			}
			add_meta_box(
				'seofyme_seo',
				__( 'Seofyme SEO', 'seofyme-seo' ),
				array( $this, 'render' ),
				$type,
				'normal',
				'high'
			);
		}
	}

	/**
	 * Render fields + live analysis.
	 *
	 * @param \WP_Post $post Post.
	 * @return void
	 */
	public function render( $post ) {
		wp_nonce_field( 'seofyme_seo_metabox', 'seofyme_seo_metabox_nonce' );

		$title       = Post_Meta::get( $post->ID, Post_Meta::TITLE );
		$description = Post_Meta::get( $post->ID, Post_Meta::DESCRIPTION );
		$focus       = Post_Meta::get( $post->ID, Post_Meta::FOCUS_KW );
		$canonical   = Post_Meta::get( $post->ID, Post_Meta::CANONICAL );
		$robots      = Post_Meta::get( $post->ID, Post_Meta::ROBOTS, 'index,follow' );
		$cornerstone = Post_Meta::get( $post->ID, Post_Meta::CORNERSTONE );

		$analysis = ( new Analyzer() )->analyze_post( $post->ID );
		$score    = (int) $analysis['score'];
		?>
		<div class="seofyme-metabox">
			<div class="seofyme-metabox__grid">
				<div>
					<div class="seofyme-serp-preview">
						<span class="seofyme-serp-preview__label"><?php esc_html_e( 'Search preview', 'seofyme-seo' ); ?></span>
						<div class="seofyme-serp-title"><?php echo esc_html( $title ? $title : get_the_title( $post ) ); ?></div>
						<div class="seofyme-serp-url"><?php echo esc_html( get_permalink( $post ) ); ?></div>
						<div class="seofyme-serp-desc"><?php echo esc_html( $description ? $description : __( 'Meta description preview appears here.', 'seofyme-seo' ) ); ?></div>
					</div>

					<div class="seofyme-field">
						<label for="seofyme_focus_keyphrase"><?php esc_html_e( 'Focus keyphrase', 'seofyme-seo' ); ?></label>
						<input type="text" class="widefat" id="seofyme_focus_keyphrase" name="seofyme_focus_keyphrase" value="<?php echo esc_attr( $focus ); ?>" />
					</div>
					<div class="seofyme-field">
						<label for="seofyme_title"><?php esc_html_e( 'SEO title', 'seofyme-seo' ); ?></label>
						<input type="text" class="widefat" id="seofyme_title" name="seofyme_title" value="<?php echo esc_attr( $title ); ?>" />
						<span class="description"><?php esc_html_e( 'Vars: %%title%% %%sitename%% %%sep%% %%focuskw%%', 'seofyme-seo' ); ?></span>
					</div>
					<div class="seofyme-field">
						<label for="seofyme_description"><?php esc_html_e( 'Meta description', 'seofyme-seo' ); ?></label>
						<textarea class="widefat" rows="3" id="seofyme_description" name="seofyme_description"><?php echo esc_textarea( $description ); ?></textarea>
					</div>
					<div class="seofyme-field-row">
						<div class="seofyme-field">
							<label for="seofyme_canonical"><?php esc_html_e( 'Canonical URL', 'seofyme-seo' ); ?></label>
							<input type="url" class="widefat" id="seofyme_canonical" name="seofyme_canonical" value="<?php echo esc_attr( $canonical ); ?>" />
						</div>
						<div class="seofyme-field">
							<label for="seofyme_robots"><?php esc_html_e( 'Robots', 'seofyme-seo' ); ?></label>
							<select class="widefat" id="seofyme_robots" name="seofyme_robots">
								<option value="index,follow" <?php selected( $robots, 'index,follow' ); ?>>index, follow</option>
								<option value="noindex,follow" <?php selected( $robots, 'noindex,follow' ); ?>>noindex, follow</option>
								<option value="index,nofollow" <?php selected( $robots, 'index,nofollow' ); ?>>index, nofollow</option>
								<option value="noindex,nofollow" <?php selected( $robots, 'noindex,nofollow' ); ?>>noindex, nofollow</option>
							</select>
						</div>
					</div>
					<label class="seofyme-toggle">
						<input type="checkbox" name="seofyme_cornerstone" value="1" <?php checked( $cornerstone, '1' ); ?> />
						<?php esc_html_e( 'Cornerstone content', 'seofyme-seo' ); ?>
					</label>
				</div>

				<aside class="seofyme-analysis">
					<div class="seofyme-analysis__top">
						<div class="seofyme-score-ring" style="--sf-score: <?php echo esc_attr( (string) $score ); ?>;"><?php echo esc_html( (string) $score ); ?></div>
						<div>
							<h3><?php esc_html_e( 'Content analysis', 'seofyme-seo' ); ?></h3>
							<p class="seofyme-score seofyme-score--<?php echo esc_attr( $analysis['label'] ); ?>">
								<?php
								printf(
									/* translators: %d score */
									esc_html__( '%d / 100', 'seofyme-seo' ),
									$score
								);
								?>
							</p>
						</div>
					</div>
					<ul>
						<?php foreach ( $analysis['checks'] as $check ) : ?>
							<li class="seofyme-check seofyme-check--<?php echo esc_attr( $check['status'] ); ?>">
								<?php echo esc_html( $check['message'] ); ?>
							</li>
						<?php endforeach; ?>
					</ul>
				</aside>
			</div>
		</div>
		<?php
	}

	/**
	 * Save.
	 *
	 * @param int      $post_id Post ID.
	 * @param \WP_Post $post Post.
	 * @return void
	 */
	public function save( $post_id, $post ) {
		if ( ! isset( $_POST['seofyme_seo_metabox_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['seofyme_seo_metabox_nonce'] ) ), 'seofyme_seo_metabox' ) ) {
			return;
		}
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		Post_Meta::set( $post_id, Post_Meta::FOCUS_KW, isset( $_POST['seofyme_focus_keyphrase'] ) ? sanitize_text_field( wp_unslash( $_POST['seofyme_focus_keyphrase'] ) ) : '' );
		Post_Meta::set( $post_id, Post_Meta::TITLE, isset( $_POST['seofyme_title'] ) ? sanitize_text_field( wp_unslash( $_POST['seofyme_title'] ) ) : '' );
		Post_Meta::set( $post_id, Post_Meta::DESCRIPTION, isset( $_POST['seofyme_description'] ) ? sanitize_textarea_field( wp_unslash( $_POST['seofyme_description'] ) ) : '' );
		Post_Meta::set( $post_id, Post_Meta::CANONICAL, isset( $_POST['seofyme_canonical'] ) ? esc_url_raw( wp_unslash( $_POST['seofyme_canonical'] ) ) : '' );
		Post_Meta::set( $post_id, Post_Meta::ROBOTS, isset( $_POST['seofyme_robots'] ) ? sanitize_text_field( wp_unslash( $_POST['seofyme_robots'] ) ) : 'index,follow' );
		Post_Meta::set( $post_id, Post_Meta::CORNERSTONE, isset( $_POST['seofyme_cornerstone'] ) ? '1' : '0' );
	}
}
