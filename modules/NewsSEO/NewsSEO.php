<?php
/**
 * News sitemap + NewsArticle schema.
 *
 * @package SeofymeSEO
 */

namespace SeofymeSEO\Modules\NewsSEO;

use SeofymeSEO\Schema\Json_Ld;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * News SEO module.
 */
class NewsSEO {

	/**
	 * Register.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'init', array( $this, 'rewrite' ) );
		add_action( 'template_redirect', array( $this, 'render_sitemap' ) );
		add_action( 'add_meta_boxes', array( $this, 'box' ) );
		add_action( 'save_post_post', array( $this, 'save' ) );
		add_action( 'wp_head', array( $this, 'schema' ), 7 );
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
	 * Box.
	 *
	 * @return void
	 */
	public function box() {
		add_meta_box( 'seofyme_news', __( 'News SEO', 'seofyme-seo' ), array( $this, 'render' ), 'post', 'side' );
	}

	/**
	 * Render.
	 *
	 * @param \WP_Post $post Post.
	 * @return void
	 */
	public function render( $post ) {
		wp_nonce_field( 'seofyme_news', 'seofyme_news_nonce' );
		$include = get_post_meta( $post->ID, '_seofyme_news_include', true );
		$include = '' === $include ? '1' : $include;
		?>
		<p><label><input type="checkbox" name="seofyme_news_include" value="1" <?php checked( $include, '1' ); ?> /> <?php esc_html_e( 'Include in news sitemap', 'seofyme-seo' ); ?></label></p>
		<p><label><?php esc_html_e( 'Genres', 'seofyme-seo' ); ?><br><input type="text" class="widefat" name="seofyme_news_genres" value="<?php echo esc_attr( (string) get_post_meta( $post->ID, '_seofyme_news_genres', true ) ); ?>" /></label></p>
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
	}

	/**
	 * Schema for recent posts.
	 *
	 * @return void
	 */
	public function schema() {
		if ( ! is_singular( 'post' ) ) {
			return;
		}
		$id = get_queried_object_id();
		if ( '0' === get_post_meta( $id, '_seofyme_news_include', true ) ) {
			return;
		}
		if ( get_post_time( 'U', true, $id ) < ( time() - 2 * DAY_IN_SECONDS ) ) {
			return;
		}
		$data = array(
			'@context'      => 'https://schema.org',
			'@type'         => 'NewsArticle',
			'headline'      => get_the_title( $id ),
			'datePublished' => get_the_date( 'c', $id ),
			'dateModified'  => get_the_modified_date( 'c', $id ),
			'author'        => array( '@type' => 'Person', 'name' => get_the_author_meta( 'display_name', (int) get_post_field( 'post_author', $id ) ) ),
			'publisher'     => array( '@type' => 'Organization', 'name' => get_bloginfo( 'name' ) ),
		);
		Json_Ld::print_script( $data );
	}

	/**
	 * News sitemap.
	 *
	 * @return void
	 */
	public function render_sitemap() {
		if ( ! get_query_var( 'seofyme_news_sitemap' ) ) {
			return;
		}
		header( 'Content-Type: application/xml; charset=UTF-8' );
		echo '<?xml version="1.0" encoding="UTF-8"?>';
		echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:news="http://www.google.com/schemas/sitemap-news/0.9">';
		$q = new \WP_Query(
			array(
				'post_type'      => 'post',
				'post_status'    => 'publish',
				'posts_per_page' => 1000,
				'date_query'     => array( array( 'after' => '2 days ago' ) ),
				'no_found_rows'  => true,
			)
		);
		foreach ( $q->posts as $post ) {
			if ( '0' === get_post_meta( $post->ID, '_seofyme_news_include', true ) ) {
				continue;
			}
			echo '<url><loc>' . esc_url( get_permalink( $post ) ) . '</loc><news:news>';
			echo '<news:publication><news:name>' . esc_html( get_bloginfo( 'name' ) ) . '</news:name><news:language>' . esc_html( get_bloginfo( 'language' ) ) . '</news:language></news:publication>';
			echo '<news:publication_date>' . esc_html( get_the_date( 'c', $post ) ) . '</news:publication_date>';
			echo '<news:title>' . esc_html( get_the_title( $post ) ) . '</news:title>';
			echo '</news:news></url>';
		}
		echo '</urlset>';
		exit;
	}
}
