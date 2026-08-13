<?php
/**
 * Shared admin page chrome (CacheRocket-style shell).
 *
 * @package SeofymeSEO
 */

namespace SeofymeSEO\Admin;

use SeofymeSEO\Modules\WhiteLabel\WhiteLabel;
use SeofymeSEO\Support\Cloud_Account;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Renders branded sidebar + main content layout.
 */
class Page_Shell {

	/**
	 * Navigation items for the sidebar.
	 *
	 * @return array<int, array{slug:string,label:string,icon:string,cap:string}>
	 */
	public static function nav_items() {
		return array(
			array(
				'slug'  => 'seofyme-seo',
				'label' => __( 'Dashboard', 'seofyme-seo' ),
				'icon'  => 'dashicons-dashboard',
				'cap'   => 'manage_options',
			),
			array(
				'slug'  => 'seofyme-seo-settings',
				'label' => __( 'Settings', 'seofyme-seo' ),
				'icon'  => 'dashicons-admin-generic',
				'cap'   => 'manage_options',
			),
			array(
				'slug'  => 'seofyme-seo-audit',
				'label' => __( 'Site audit', 'seofyme-seo' ),
				'icon'  => 'dashicons-yes-alt',
				'cap'   => 'manage_options',
			),
			array(
				'slug'  => 'seofyme-seo-redirects',
				'label' => __( 'Redirects', 'seofyme-seo' ),
				'icon'  => 'dashicons-randomize',
				'cap'   => 'edit_others_posts',
			),
			array(
				'slug'  => 'seofyme-seo-404',
				'label' => __( '404 monitor', 'seofyme-seo' ),
				'icon'  => 'dashicons-warning',
				'cap'   => 'edit_others_posts',
			),
			array(
				'slug'  => 'seofyme-seo-links',
				'label' => __( 'Link assistant', 'seofyme-seo' ),
				'icon'  => 'dashicons-admin-links',
				'cap'   => 'edit_others_posts',
			),
			array(
				'slug'  => 'seofyme-seo-ranks',
				'label' => __( 'Rank tracker', 'seofyme-seo' ),
				'icon'  => 'dashicons-chart-line',
				'cap'   => 'edit_others_posts',
			),
			array(
				'slug'  => 'seofyme-seo-bulk',
				'label' => __( 'Bulk editor', 'seofyme-seo' ),
				'icon'  => 'dashicons-editor-table',
				'cap'   => 'edit_others_posts',
			),
			array(
				'slug'  => 'seofyme-seo-images',
				'label' => __( 'Image SEO', 'seofyme-seo' ),
				'icon'  => 'dashicons-format-image',
				'cap'   => 'upload_files',
			),
			array(
				'slug'  => 'seofyme-seo-workouts',
				'label' => __( 'Workouts', 'seofyme-seo' ),
				'icon'  => 'dashicons-superhero',
				'cap'   => 'edit_others_posts',
			),
			array(
				'slug'  => 'seofyme-seo-account',
				'label' => __( 'Account', 'seofyme-seo' ),
				'icon'  => 'dashicons-admin-users',
				'cap'   => 'manage_options',
			),
		);
	}

	/**
	 * Open page with branded shell.
	 *
	 * @param string $title Title.
	 * @param string $subtitle Subtitle.
	 * @return void
	 */
	public static function open( $title, $subtitle = '' ) {
		$brand   = class_exists( WhiteLabel::class ) ? WhiteLabel::brand() : 'Seofyme SEO';
		$current = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : 'seofyme-seo'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$logo    = SEOFYME_SEO_URL . 'assets/seofyme-logo.png';
		?>
		<div class="wrap sf-wrap">
			<?php settings_errors( 'seofyme_messages' ); ?>
			<?php settings_errors( 'general' ); ?>
			<div class="sf-shell">
				<aside class="sf-sidebar">
					<div class="sf-brand">
						<img
							class="sf-brand__logo"
							src="<?php echo esc_url( $logo ); ?>"
							alt="<?php echo esc_attr( $brand ); ?>"
							width="40"
							height="49"
						/>
						<div class="sf-brand__text">
							<strong><?php echo esc_html( $brand ); ?></strong>
							<span><?php esc_html_e( 'SEO suite', 'seofyme-seo' ); ?></span>
						</div>
					</div>

					<ul class="sf-nav">
						<?php foreach ( self::nav_items() as $item ) : ?>
							<?php
							if ( ! current_user_can( $item['cap'] ) ) {
								continue;
							}
							$active = ( $current === $item['slug'] ) ? ' is-active' : '';
							?>
							<li>
								<a class="<?php echo esc_attr( trim( $active ) ); ?>" href="<?php echo esc_url( admin_url( 'admin.php?page=' . $item['slug'] ) ); ?>">
									<span class="dashicons <?php echo esc_attr( $item['icon'] ); ?>" aria-hidden="true"></span>
									<?php echo esc_html( $item['label'] ); ?>
								</a>
							</li>
						<?php endforeach; ?>
					</ul>

					<div class="sf-sidebar__foot">
						<?php
						$status = Cloud_Account::get_status();
						printf(
							/* translators: %s: Seofyme subscription plan name. */
							esc_html__( 'Plan: %s', 'seofyme-seo' ),
							esc_html( isset( $status['planName'] ) ? (string) $status['planName'] : 'Free' )
						);
						?>
						<br />
						<a href="https://seofyme.com" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Open Seofyme.com', 'seofyme-seo' ); ?></a>
					</div>
				</aside>

				<main class="sf-main">
					<header class="sf-main__header">
						<div>
							<h1><?php echo esc_html( $title ); ?></h1>
							<?php if ( $subtitle ) : ?>
								<p><?php echo esc_html( $subtitle ); ?></p>
							<?php endif; ?>
						</div>
					</header>
					<div class="sf-content seofyme-content">
		<?php
	}

	/**
	 * Close page shell.
	 *
	 * @return void
	 */
	public static function close() {
		echo '</div></main></div></div>';
	}
}
