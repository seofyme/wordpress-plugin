<?php
/**
 * SEO workouts — orphaned content & cornerstone improvement flows.
 *
 * @package Seofyme\SEO\Premium
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Guided workouts admin page.
 */
class Seofyme_Workouts {

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function register() {
		// Page rendered via Seofyme_Premium_Admin.
	}

	/**
	 * Render workouts page.
	 *
	 * @return void
	 */
	public function render_page() {
		if ( ! current_user_can( 'edit_others_posts' ) ) {
			return;
		}

		$orphaned = ( new Seofyme_Orphaned_Content() )->find_orphans( 25 );
		$cornerstone = get_posts(
			[
				'post_type'      => [ 'post', 'page' ],
				'post_status'    => 'publish',
				'posts_per_page' => 25,
				'meta_key'       => '_yoast_wpseo_is_cornerstone',
				'meta_value'     => '1',
				'no_found_rows'  => true,
			]
		);
		?>
		<div class="wrap seofyme-workouts">
			<h1><?php esc_html_e( 'SEO Workouts', 'seofyme-seo' ); ?></h1>
			<p><?php esc_html_e( 'Guided tasks to fix orphaned pages and strengthen cornerstone content.', 'seofyme-seo' ); ?></p>

			<section class="seofyme-workout">
				<h2><?php esc_html_e( 'Orphaned content', 'seofyme-seo' ); ?></h2>
				<p><?php esc_html_e( 'These published pages have no inbound internal links from other content we scanned.', 'seofyme-seo' ); ?></p>
				<ol>
					<?php if ( empty( $orphaned ) ) : ?>
						<li><?php esc_html_e( 'Nice — no orphaned posts found in the scan window.', 'seofyme-seo' ); ?></li>
					<?php else : ?>
						<?php foreach ( $orphaned as $item ) : ?>
							<li>
								<a href="<?php echo esc_url( get_edit_post_link( $item['id'] ) ); ?>"><?php echo esc_html( $item['title'] ); ?></a>
								— <a href="<?php echo esc_url( $item['url'] ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'View', 'seofyme-seo' ); ?></a>
							</li>
						<?php endforeach; ?>
					<?php endif; ?>
				</ol>
			</section>

			<section class="seofyme-workout">
				<h2><?php esc_html_e( 'Cornerstone content', 'seofyme-seo' ); ?></h2>
				<p><?php esc_html_e( 'Review cornerstone pieces and ensure they receive internal links from related articles.', 'seofyme-seo' ); ?></p>
				<ol>
					<?php if ( empty( $cornerstone ) ) : ?>
						<li><?php esc_html_e( 'Mark important pages as cornerstone in the editor to track them here.', 'seofyme-seo' ); ?></li>
					<?php else : ?>
						<?php foreach ( $cornerstone as $post ) : ?>
							<li>
								<a href="<?php echo esc_url( get_edit_post_link( $post ) ); ?>"><?php echo esc_html( get_the_title( $post ) ); ?></a>
							</li>
						<?php endforeach; ?>
					<?php endif; ?>
				</ol>
			</section>
		</div>
		<?php
	}
}
