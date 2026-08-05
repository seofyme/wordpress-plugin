<?php
/**
 * Shared admin page chrome.
 *
 * @package SeofymeSEO
 */

namespace SeofymeSEO\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Renders consistent page headers.
 */
class Page_Shell {

	/**
	 * Open page with branded header.
	 *
	 * @param string $title Title.
	 * @param string $subtitle Subtitle.
	 * @return void
	 */
	public static function open( $title, $subtitle = '' ) {
		?>
		<div class="wrap seofyme-wrap">
			<header class="seofyme-hero">
				<div class="seofyme-hero__glow" aria-hidden="true"></div>
				<div class="seofyme-hero__inner">
					<p class="seofyme-brand">Seofyme</p>
					<h1 class="seofyme-hero__title"><?php echo esc_html( $title ); ?></h1>
					<?php if ( $subtitle ) : ?>
						<p class="seofyme-hero__sub"><?php echo esc_html( $subtitle ); ?></p>
					<?php endif; ?>
				</div>
			</header>
			<div class="seofyme-content">
		<?php
	}

	/**
	 * Close page shell.
	 *
	 * @return void
	 */
	public static function close() {
		echo '</div></div>';
	}
}
