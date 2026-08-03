<?php
/**
 * News SEO — Google News sitemap + NewsArticle schema.
 *
 * @package Seofyme\SEO\Premium
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * News sitemap and per-article controls.
 */
class Seofyme_News_SEO {

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'init', [ $this, 'rewrite' ] );
		add_action( 'template_redirect', [ $this, 'render_sitemap' ] );
		add_action( 'add_meta_boxes', [ $this, 'meta_box' ] );
		add_action( 'save_post_post', [ $this, 'save' ] );
		add_action( 'wp_head', [ $this, 'output_schema' ], 7 );
		add_filter( 'wpseo_sitemap_index', [ $this, 'add_to_index' ] );
	}

	/**
	 * Rewrite.
	 *
	 * @return void
	 */
	public function rewrite() {
		add_rewrite_rule( '^news-sitemap\.xml$', 'index.php?seofyme_news_sitemap=1', 'top' );
		add_rewrite_tag( '%seofyme_news_sitemap%', '1' );
	}

	/**
	 * Meta box.
	 *
	 * @return void
	 */
	public function meta_box() {
		add_meta_box(
			'seofyme_news',
			__( 'Seofyme — News SEO', 'seofyme-seo' ),
			[ $this, 'render' ],
			'post',
			'side',
			'default'
		);
	}

	/**
	 * Render.
	 *
	 * @param WP_Post $post Post.
	 * @return void
	 */
	public function render( $post ) {
		wp_nonce_field( 'seofyme_news', 'seofyme_news_nonce' );
		$include = get_post_meta( $post->ID, '_seofyme_news_include', true );
		$include = $include === '' ? '1' : $include;
		$genres  = get_post_meta( $post->ID, '_seofyme_news_genres', true );
		$stock   = get_post_meta( $post->ID, '_seofyme_news_stock', true );
		?>
		<p>
			<label>
				<input type="checkbox" name="seofyme_news_include" value="1" <?php checked( $include, '1' ); ?> />
				<?php esc_html_e( 'Include in news sitemap', 'seofyme-seo' ); ?>
			</label>
		</p>
		<p>
			<label><?php esc_html_e( 'Genres', 'seofyme-seo' ); ?><br>
				<input type="text" class="widefat" name="seofyme_news_genres" value="<?php echo esc_attr( (string) $genres ); ?>" placeholder="Blog, OpEd" />
			</label>
		</p>
		<p>
			<label><?php esc_html_e( 'Stock tickers', 'seofyme-seo' ); ?><br>
				<input type="text" class="widefat" name="seofyme_news_stock" value="<?php echo esc_attr( (string) $stock ); ?>" placeholder="NASDAQ:GOOG" />
			</label>
		</p>
		<?php
	}

	/**
	 * Save.
	 *
	 * @param int $post_id Post ID.
	 * @return void
	 */
	public function save( $post_id ) {
		if ( ! isset( $_POST['seofyme_news_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['seofyme_news_nonce'] ) ), 'seofyme_news' ) ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}
		update_post_meta( $post_id, '_seofyme_news_include', isset( $_POST['seofyme_news_include'] ) ? '1' : '0' );
		update_post_meta( $post_id, '_seofyme_news_genres', isset( $_POST['seofyme_news_genres'] ) ? sanitize_text_field( wp_unslash( $_POST['seofyme_news_genres'] ) ) : '' );
		update_post_meta( $post_id, '_seofyme_news_stock', isset( $_POST['seofyme_news_stock'] ) ? sanitize_text_field( wp_unslash( $_POST['seofyme_news_stock'] ) ) : '' );
	}

	/**
	 * NewsArticle schema.
	 *
	 * @return void
	 */
	public function output_schema() {
		if ( ! is_singular( 'post' ) ) {
			return;
		}
		$id = get_queried_object_id();
		if ( get_post_meta( $id, '_seofyme_news_include', true ) === '0' ) {
			return;
		}
		$published = get_post_time( 'U', true, $id );
		if ( $published < ( time() - 2 * DAY_IN_SECONDS ) ) {
			// Google News typically cares about recent articles.
			return;
		}
		$data = [
			'@context'         => 'https://schema.org',
			'@type'            => 'NewsArticle',
			'headline'         => get_the_title( $id ),
			'datePublished'    => get_the_date( 'c', $id ),
			'dateModified'     => get_the_modified_date( 'c', $id ),
			'mainEntityOfPage' => get_permalink( $id ),
			'author'           => [
				'@type' => 'Person',
				'name'  => get_the_author_meta( 'display_name', (int) get_post_field( 'post_author', $id ) ),
			],
			'publisher'        => [
				'@type' => 'Organization',
				'name'  => get_bloginfo( 'name' ),
			],
			'image'            => get_the_post_thumbnail_url( $id, 'full' ) ?: [],
			'description'      => wp_trim_words( wp_strip_all_tags( get_post_field( 'post_content', $id ) ), 40 ),
		];
		echo '<script type="application/ld+json">' . wp_json_encode( $data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . '</script>' . "\n";
	}

	/**
	 * Render news sitemap (last 48h).
	 *
	 * @return void
	 */
	public function render_sitemap() {
		if ( ! get_query_var( 'seofyme_news_sitemap' ) ) {
			return;
		}
		header( 'Content-Type: application/xml; charset=UTF-8' );
		$publication = get_bloginfo( 'name' );
		$lang        = get_bloginfo( 'language' );
		echo '<?xml version="1.0" encoding="UTF-8"?>';
		echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:news="http://www.google.com/schemas/sitemap-news/0.9">';
		$q = new WP_Query(
			[
				'post_type'      => 'post',
				'post_status'    => 'publish',
				'posts_per_page' => 1000,
				'date_query'     => [ [ 'after' => '2 days ago' ] ],
				'no_found_rows'  => true,
			]
		);
		foreach ( $q->posts as $post ) {
			if ( get_post_meta( $post->ID, '_seofyme_news_include', true ) === '0' ) {
				continue;
			}
			$genres = get_post_meta( $post->ID, '_seofyme_news_genres', true );
			$stock  = get_post_meta( $post->ID, '_seofyme_news_stock', true );
			echo '<url>';
			echo '<loc>' . esc_url( get_permalink( $post ) ) . '</loc>';
			echo '<news:news>';
			echo '<news:publication><news:name>' . esc_html( $publication ) . '</news:name><news:language>' . esc_html( $lang ) . '</news:language></news:publication>';
			echo '<news:publication_date>' . esc_html( get_the_date( 'c', $post ) ) . '</news:publication_date>';
			echo '<news:title>' . esc_html( get_the_title( $post ) ) . '</news:title>';
			if ( $genres ) {
				echo '<news:genres>' . esc_html( $genres ) . '</news:genres>';
			}
			if ( $stock ) {
				echo '<news:stock_tickers>' . esc_html( $stock ) . '</news:stock_tickers>';
			}
			echo '</news:news></url>';
		}
		echo '</urlset>';
		exit;
	}

	/**
	 * Sitemap index entry.
	 *
	 * @param string $xml XML.
	 * @return string
	 */
	public function add_to_index( $xml ) {
		$loc     = home_url( '/news-sitemap.xml' );
		$lastmod = gmdate( 'c' );
		$xml    .= "<sitemap><loc>{$loc}</loc><lastmod>{$lastmod}</lastmod></sitemap>\n";
		return $xml;
	}
}
