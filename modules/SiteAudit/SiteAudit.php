<?php
/**
 * Site-wide SEO audit checklist.
 *
 * @package SeofymeSEO
 */

namespace SeofymeSEO\Modules\SiteAudit;

use SeofymeSEO\Admin\Page_Shell;
use SeofymeSEO\Support\Options;
use SeofymeSEO\Support\Post_Meta;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Runs technical + content health checks.
 */
class SiteAudit {

	/**
	 * Register.
	 *
	 * @return void
	 */
	public function register() {
		// Page via Admin.
	}

	/**
	 * Run checks.
	 *
	 * @return array
	 */
	public function run() {
		$checks = array();

		$checks[] = array(
			'label'  => __( 'Site is public (not discouraging search engines)', 'seofyme-seo' ),
			'status' => ( '1' !== get_option( 'blog_public' ) ) ? 'bad' : 'good',
		);
		$checks[] = array(
			'label'  => __( 'XML sitemaps enabled', 'seofyme-seo' ),
			'status' => Options::get( 'xml_sitemap' ) ? 'good' : 'bad',
		);
		$checks[] = array(
			'label'  => __( 'Organization name set', 'seofyme-seo' ),
			'status' => Options::get( 'organization_name' ) ? 'good' : 'ok',
		);
		$checks[] = array(
			'label'  => __( 'Homepage meta description set', 'seofyme-seo' ),
			'status' => Options::get( 'homepage_description' ) ? 'good' : 'ok',
		);
		$checks[] = array(
			'label'  => __( 'Pretty permalinks enabled', 'seofyme-seo' ),
			'status' => get_option( 'permalink_structure' ) ? 'good' : 'bad',
		);

		$missing_desc = 0;
		$q = new \WP_Query(
			array(
				'post_type'      => array( 'post', 'page' ),
				'post_status'    => 'publish',
				'posts_per_page' => 50,
				'no_found_rows'  => true,
			)
		);
		foreach ( $q->posts as $post ) {
			if ( ! Post_Meta::get( $post->ID, Post_Meta::DESCRIPTION ) ) {
				++$missing_desc;
			}
		}
		$checks[] = array(
			'label'  => sprintf( /* translators: %d */ __( 'Recent posts/pages missing meta description: %d', 'seofyme-seo' ), $missing_desc ),
			'status' => ( 0 === $missing_desc ) ? 'good' : ( $missing_desc > 10 ? 'bad' : 'ok' ),
		);

		$forwarded = isset( $_SERVER['HTTP_X_FORWARDED_PROTO'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_FORWARDED_PROTO'] ) ) : '';
		$ssl       = is_ssl() || ( 'https' === $forwarded );
		$checks[] = array(
			'label'  => __( 'HTTPS detected for admin request', 'seofyme-seo' ),
			'status' => $ssl ? 'good' : 'ok',
		);

		$tagline = get_bloginfo( 'description' );
		$checks[] = array(
			'label'  => __( 'Site tagline is customized', 'seofyme-seo' ),
			'status' => ( $tagline && false === stripos( $tagline, 'Just another WordPress' ) ) ? 'good' : 'ok',
		);

		return $checks;
	}

	/**
	 * Render page.
	 *
	 * @return void
	 */
	public function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$checks = $this->run();
		$good   = count( array_filter( $checks, static function ( $c ) { return 'good' === $c['status']; } ) );
		Page_Shell::open(
			__( 'Site audit', 'seofyme-seo' ),
			sprintf( /* translators: 1 good 2 total */ __( '%1$d of %2$d checks look healthy.', 'seofyme-seo' ), $good, count( $checks ) )
		);
		?>
		<section class="sf-card">
			<header class="sf-card__header">
				<h2><?php esc_html_e( 'Health checks', 'seofyme-seo' ); ?></h2>
				<p><?php esc_html_e( 'Technical SEO signals across your site configuration.', 'seofyme-seo' ); ?></p>
			</header>
			<div class="sf-card__body sf-card__body--flush">
				<ul class="sf-checks">
					<?php foreach ( $checks as $check ) : ?>
						<li class="sf-check sf-check--<?php echo esc_attr( $check['status'] ); ?>"><?php echo esc_html( $check['label'] ); ?></li>
					<?php endforeach; ?>
				</ul>
			</div>
		</section>
		<?php
		Page_Shell::close();
	}
}
