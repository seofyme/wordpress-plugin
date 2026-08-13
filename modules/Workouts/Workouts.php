<?php
/**
 * Guided SEO workouts.
 *
 * @package SeofymeSEO
 */

namespace SeofymeSEO\Modules\Workouts;

use SeofymeSEO\Admin\Page_Shell;
use SeofymeSEO\Modules\InternalLinking\OrphanedContent;
use SeofymeSEO\Support\Post_Meta;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Orphaned + cornerstone workouts.
 */
class Workouts {

	/**
	 * Days before cornerstone is considered stale.
	 */
	public const STALE_DAYS = 90;

	/**
	 * Register.
	 *
	 * @return void
	 */
	public function register() {
		// Rendered via Admin.
	}

	/**
	 * Render page.
	 *
	 * @return void
	 */
	public function render_page() {
		if ( ! current_user_can( 'edit_others_posts' ) ) {
			return;
		}
		$orphans = ( new OrphanedContent() )->find( 25 );
		$stones  = get_posts(
			array(
				'post_type'      => array( 'post', 'page' ),
				'post_status'    => 'publish',
				'posts_per_page' => 25,
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Required meta lookup.
				'meta_query'     => array(
					array(
						'key'   => Post_Meta::CORNERSTONE,
						'value' => '1',
					),
				),
				'no_found_rows'  => true,
			)
		);
		$stale = get_posts(
			array(
				'post_type'      => array( 'post', 'page' ),
				'post_status'    => 'publish',
				'posts_per_page' => 25,
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Required meta lookup.
				'meta_query'     => array(
					array(
						'key'   => Post_Meta::CORNERSTONE,
						'value' => '1',
					),
				),
				'date_query'     => array(
					array(
						'column' => 'post_modified',
						'before' => self::STALE_DAYS . ' days ago',
					),
				),
				'orderby'        => 'modified',
				'order'          => 'ASC',
				'no_found_rows'  => true,
			)
		);
		Page_Shell::open(
			__( 'Workouts', 'seofyme-seo' ),
			__( 'Guided tasks for orphaned pages, cornerstone content, and stale updates.', 'seofyme-seo' )
		);
		?>
			<div class="sf-grid">
				<section class="sf-card">
					<header class="sf-card__header">
						<h2><?php esc_html_e( 'Orphaned content', 'seofyme-seo' ); ?></h2>
						<p><?php esc_html_e( 'Published pages with no internal links pointing to them.', 'seofyme-seo' ); ?></p>
					</header>
					<div class="sf-card__body sf-card__body--flush">
						<ol class="sf-list sf-list--numbered">
							<?php if ( empty( $orphans ) ) : ?>
								<li><?php esc_html_e( 'No orphaned posts found in the scan window.', 'seofyme-seo' ); ?></li>
							<?php else : ?>
								<?php foreach ( $orphans as $item ) : ?>
									<li><a href="<?php echo esc_url( get_edit_post_link( $item['id'] ) ); ?>"><?php echo esc_html( $item['title'] ); ?></a></li>
								<?php endforeach; ?>
							<?php endif; ?>
						</ol>
					</div>
				</section>
				<section class="sf-card">
					<header class="sf-card__header">
						<h2><?php esc_html_e( 'Cornerstone content', 'seofyme-seo' ); ?></h2>
						<p><?php esc_html_e( 'Pages marked as cornerstone in the editor.', 'seofyme-seo' ); ?></p>
					</header>
					<div class="sf-card__body sf-card__body--flush">
						<ol class="sf-list sf-list--numbered">
							<?php if ( empty( $stones ) ) : ?>
								<li><?php esc_html_e( 'Mark pages as cornerstone in the editor to track them here.', 'seofyme-seo' ); ?></li>
							<?php else : ?>
								<?php foreach ( $stones as $post ) : ?>
									<li><a href="<?php echo esc_url( get_edit_post_link( $post ) ); ?>"><?php echo esc_html( get_the_title( $post ) ); ?></a></li>
								<?php endforeach; ?>
							<?php endif; ?>
						</ol>
					</div>
				</section>
				<section class="sf-card">
					<header class="sf-card__header">
						<h2><?php esc_html_e( 'Stale cornerstone content', 'seofyme-seo' ); ?></h2>
						<p>
							<?php
							printf(
								/* translators: %d days */
								esc_html__( 'Cornerstone pages not updated in the last %d days — refresh them to keep rankings healthy.', 'seofyme-seo' ),
								(int) self::STALE_DAYS
							);
							?>
						</p>
					</header>
					<div class="sf-card__body sf-card__body--flush">
						<ol class="sf-list sf-list--numbered">
							<?php if ( empty( $stale ) ) : ?>
								<li><?php esc_html_e( 'All cornerstone content looks fresh.', 'seofyme-seo' ); ?></li>
							<?php else : ?>
								<?php foreach ( $stale as $post ) : ?>
									<li>
										<a href="<?php echo esc_url( get_edit_post_link( $post ) ); ?>"><?php echo esc_html( get_the_title( $post ) ); ?></a>
										<span class="sf-field__hint"> — <?php echo esc_html( human_time_diff( get_post_modified_time( 'U', true, $post ), time() ) ); ?> <?php esc_html_e( 'ago', 'seofyme-seo' ); ?></span>
									</li>
								<?php endforeach; ?>
							<?php endif; ?>
						</ol>
					</div>
				</section>
			</div>
		<?php
		Page_Shell::close();
	}
}
