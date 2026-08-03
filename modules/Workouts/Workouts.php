<?php
/**
 * Guided SEO workouts.
 *
 * @package SeofymeSEO
 */

namespace SeofymeSEO\Modules\Workouts;

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
				'meta_key'       => Post_Meta::CORNERSTONE,
				'meta_value'     => '1',
				'no_found_rows'  => true,
			)
		);
		?>
		<div class="wrap seofyme-wrap">
			<h1><?php esc_html_e( 'SEO Workouts', 'seofyme-seo' ); ?></h1>
			<section class="seofyme-card">
				<h2><?php esc_html_e( 'Orphaned content', 'seofyme-seo' ); ?></h2>
				<ol>
					<?php if ( empty( $orphans ) ) : ?>
						<li><?php esc_html_e( 'No orphaned posts found in the scan window.', 'seofyme-seo' ); ?></li>
					<?php else : ?>
						<?php foreach ( $orphans as $item ) : ?>
							<li><a href="<?php echo esc_url( get_edit_post_link( $item['id'] ) ); ?>"><?php echo esc_html( $item['title'] ); ?></a></li>
						<?php endforeach; ?>
					<?php endif; ?>
				</ol>
			</section>
			<section class="seofyme-card">
				<h2><?php esc_html_e( 'Cornerstone content', 'seofyme-seo' ); ?></h2>
				<ol>
					<?php if ( empty( $stones ) ) : ?>
						<li><?php esc_html_e( 'Mark pages as cornerstone in the editor to track them here.', 'seofyme-seo' ); ?></li>
					<?php else : ?>
						<?php foreach ( $stones as $post ) : ?>
							<li><a href="<?php echo esc_url( get_edit_post_link( $post ) ); ?>"><?php echo esc_html( get_the_title( $post ) ); ?></a></li>
						<?php endforeach; ?>
					<?php endif; ?>
				</ol>
			</section>
		</div>
		<?php
	}
}
