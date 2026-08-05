<?php
/**
 * Author SEO / E-E-A-T profile fields + Person schema.
 *
 * @package SeofymeSEO
 */

namespace SeofymeSEO\Modules\AuthorSEO;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Adds expertise signals for authors.
 */
class AuthorSEO {

	/**
	 * Register.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'show_user_profile', array( $this, 'fields' ) );
		add_action( 'edit_user_profile', array( $this, 'fields' ) );
		add_action( 'personal_options_update', array( $this, 'save' ) );
		add_action( 'edit_user_profile_update', array( $this, 'save' ) );
		add_action( 'wp_head', array( $this, 'schema' ), 9 );
	}

	/**
	 * Profile fields.
	 *
	 * @param \WP_User $user User.
	 * @return void
	 */
	public function fields( $user ) {
		if ( ! current_user_can( 'edit_user', $user->ID ) ) {
			return;
		}
		$job     = get_user_meta( $user->ID, 'seofyme_job_title', true );
		$knows   = get_user_meta( $user->ID, 'seofyme_knows_about', true );
		$same    = get_user_meta( $user->ID, 'seofyme_same_as', true );
		$edu     = get_user_meta( $user->ID, 'seofyme_education', true );
		$exp     = get_user_meta( $user->ID, 'seofyme_experience', true );
		?>
		<h2><?php esc_html_e( 'Seofyme Author SEO (E-E-A-T)', 'seofyme-seo' ); ?></h2>
		<table class="form-table" role="presentation">
			<tr><th><label for="seofyme_job_title"><?php esc_html_e( 'Job title', 'seofyme-seo' ); ?></label></th>
				<td><input type="text" class="regular-text" name="seofyme_job_title" id="seofyme_job_title" value="<?php echo esc_attr( $job ); ?>" /></td></tr>
			<tr><th><label for="seofyme_knows_about"><?php esc_html_e( 'Knows about', 'seofyme-seo' ); ?></label></th>
				<td><input type="text" class="regular-text" name="seofyme_knows_about" id="seofyme_knows_about" value="<?php echo esc_attr( $knows ); ?>" placeholder="SEO, content strategy" /></td></tr>
			<tr><th><label for="seofyme_same_as"><?php esc_html_e( 'SameAs profiles (comma URLs)', 'seofyme-seo' ); ?></label></th>
				<td><input type="text" class="regular-text" name="seofyme_same_as" id="seofyme_same_as" value="<?php echo esc_attr( $same ); ?>" /></td></tr>
			<tr><th><label for="seofyme_education"><?php esc_html_e( 'Education', 'seofyme-seo' ); ?></label></th>
				<td><input type="text" class="regular-text" name="seofyme_education" id="seofyme_education" value="<?php echo esc_attr( $edu ); ?>" /></td></tr>
			<tr><th><label for="seofyme_experience"><?php esc_html_e( 'Experience summary', 'seofyme-seo' ); ?></label></th>
				<td><textarea class="large-text" rows="3" name="seofyme_experience" id="seofyme_experience"><?php echo esc_textarea( $exp ); ?></textarea></td></tr>
		</table>
		<?php
	}

	/**
	 * Save.
	 *
	 * @param int $user_id User ID.
	 * @return void
	 */
	public function save( $user_id ) {
		if ( ! current_user_can( 'edit_user', $user_id ) ) {
			return;
		}
		foreach ( array( 'seofyme_job_title', 'seofyme_knows_about', 'seofyme_same_as', 'seofyme_education' ) as $key ) {
			if ( isset( $_POST[ $key ] ) ) {
				update_user_meta( $user_id, $key, sanitize_text_field( wp_unslash( $_POST[ $key ] ) ) );
			}
		}
		if ( isset( $_POST['seofyme_experience'] ) ) {
			update_user_meta( $user_id, 'seofyme_experience', sanitize_textarea_field( wp_unslash( $_POST['seofyme_experience'] ) ) );
		}
	}

	/**
	 * Person schema on singular authored content.
	 *
	 * @return void
	 */
	public function schema() {
		if ( ! is_singular( array( 'post', 'page' ) ) ) {
			return;
		}
		$author_id = (int) get_post_field( 'post_author', get_queried_object_id() );
		if ( ! $author_id ) {
			return;
		}
		$same = array_filter( array_map( 'trim', explode( ',', (string) get_user_meta( $author_id, 'seofyme_same_as', true ) ) ) );
		$data = array(
			'@context'    => 'https://schema.org',
			'@type'       => 'Person',
			'@id'         => get_author_posts_url( $author_id ) . '#person',
			'name'        => get_the_author_meta( 'display_name', $author_id ),
			'url'         => get_author_posts_url( $author_id ),
			'jobTitle'    => get_user_meta( $author_id, 'seofyme_job_title', true ),
			'knowsAbout'  => array_filter( array_map( 'trim', explode( ',', (string) get_user_meta( $author_id, 'seofyme_knows_about', true ) ) ) ),
			'description' => get_user_meta( $author_id, 'seofyme_experience', true ),
		);
		if ( $same ) {
			$data['sameAs'] = array_values( $same );
		}
		echo '<script type="application/ld+json">' . wp_json_encode( $data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . '</script>' . "\n";
	}
}
