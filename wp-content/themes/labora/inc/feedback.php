<?php
/**
 * "Was this article helpful?" under every post.
 *
 *   Readers answer Yes or No (assets/js/wp-blog.js); after No they can say what was missing (optional, 500 characters).
 *   The answers are counted on the post (hidden meta: _labora_helpful_yes, _labora_helpful_no, _labora_helpful_notes)
 *   and shown in the Posts list ("Helpful" column) and in a "Reader feedback" box on the post's edit screen.
 *   REST: POST /wp-json/labora/v1/helpful { post, vote: yes|no } and { post, note }.
 *   One answer per visitor and post a day: the browser remembers it, and the server keeps a hash of the visitor's
 *   IP address and the post (never the address itself) for a day. Works on cached pages (no nonce in the page).
 *
 * @package Labora
 */

defined( 'ABSPATH' ) || exit;

const LABORA_HELPFUL_NOTES_KEPT = 100;

/** The yes / no counts of a post. */
function labora_helpful_counts( int $post_id ): array {
	return array(
		'yes' => (int) get_post_meta( $post_id, '_labora_helpful_yes', true ),
		'no'  => (int) get_post_meta( $post_id, '_labora_helpful_no', true ),
	);
}

/** Transient key for one visitor and post (a salted hash: the IP address is not stored). */
function labora_helpful_key( int $post_id, string $kind ): string {
	$ip = (string) ( $_SERVER['REMOTE_ADDR'] ?? '' ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
	return 'lbr_hf_' . $kind . '_' . substr( hash_hmac( 'sha256', $ip . '|' . $post_id, wp_salt( 'nonce' ) ), 0, 32 );
}

/** The box under the article (single.php). */
function labora_helpful_box(): void {
	if ( ! is_singular( 'post' ) ) {
		return;
	}
	?>
      <section class="helpful" data-helpful="<?php echo (int) get_the_ID(); ?>" data-endpoint="<?php echo esc_url( rest_url( 'labora/v1/helpful' ) ); ?>" aria-labelledby="helpful-q">
        <p class="helpful-q" id="helpful-q"><?php esc_html_e( 'Was this article helpful?', 'labora' ); ?></p>
        <div class="helpful-btns">
          <button type="button" class="btn btn--ghost btn--sm" data-vote="yes"><?php echo labora_icon( 'check', 'icon icon-sm' ); // phpcs:ignore WordPress.Security.EscapeOutput ?><?php esc_html_e( 'Yes', 'labora' ); ?></button>
          <button type="button" class="btn btn--ghost btn--sm" data-vote="no"><?php echo labora_icon( 'x', 'icon icon-sm' ); // phpcs:ignore WordPress.Security.EscapeOutput ?><?php esc_html_e( 'No', 'labora' ); ?></button>
        </div>
        <form class="helpful-note" hidden>
          <label for="helpful-text"><?php esc_html_e( 'What was missing or unclear? (optional)', 'labora' ); ?></label>
          <textarea id="helpful-text" name="note" rows="3" maxlength="500"></textarea>
          <button type="submit" class="btn btn--primary btn--sm"><?php esc_html_e( 'Send', 'labora' ); ?></button>
        </form>
        <p class="helpful-msg" role="status" aria-live="polite"></p>
      </section>
	<?php
}

add_action( 'rest_api_init', function () {
	register_rest_route( 'labora/v1', '/helpful', array(
		'methods'             => 'POST',
		'permission_callback' => '__return_true',
		'args'                => array(
			'post' => array( 'required' => true, 'type' => 'integer', 'minimum' => 1 ),
			'vote' => array( 'type' => 'string', 'enum' => array( 'yes', 'no' ) ),
			'note' => array( 'type' => 'string', 'maxLength' => 2000 ),
		),
		'callback'            => function ( WP_REST_Request $req ) {
			$post_id = (int) $req['post'];
			if ( 'post' !== get_post_type( $post_id ) || 'publish' !== get_post_status( $post_id ) ) {
				return new WP_Error( 'labora_helpful_post', __( 'Unknown article.', 'labora' ), array( 'status' => 404 ) );
			}
			$vote = (string) $req['vote'];
			if ( $vote ) {
				if ( get_transient( labora_helpful_key( $post_id, 'vote' ) ) ) {
					return array( 'ok' => true, 'counted' => false );
				}
				set_transient( labora_helpful_key( $post_id, 'vote' ), $vote, DAY_IN_SECONDS );
				update_post_meta( $post_id, "_labora_helpful_{$vote}", labora_helpful_counts( $post_id )[ $vote ] + 1 );
				return array( 'ok' => true, 'counted' => true );
			}
			// A note: once, and only after a "No" from the same visitor
			$note = trim( sanitize_textarea_field( (string) $req['note'] ) );
			if ( '' === $note || 'no' !== get_transient( labora_helpful_key( $post_id, 'vote' ) ) || get_transient( labora_helpful_key( $post_id, 'note' ) ) ) {
				return array( 'ok' => true, 'saved' => false );
			}
			set_transient( labora_helpful_key( $post_id, 'note' ), 1, DAY_IN_SECONDS );
			$notes   = (array) get_post_meta( $post_id, '_labora_helpful_notes', true );
			$notes[] = array( 'date' => current_time( 'mysql' ), 'text' => mb_substr( $note, 0, 500 ) );
			update_post_meta( $post_id, '_labora_helpful_notes', array_slice( array_filter( $notes ), -LABORA_HELPFUL_NOTES_KEPT ) );
			return array( 'ok' => true, 'saved' => true );
		},
	) );
} );

/* ---------- Admin: "Helpful" column and "Reader feedback" box ---------- */

/** "12 yes · 3 no (80%)", or a dash. */
function labora_helpful_summary( int $post_id ): string {
	$c     = labora_helpful_counts( $post_id );
	$total = $c['yes'] + $c['no'];
	if ( ! $total ) {
		return '—';
	}
	/* translators: 1: yes answers, 2: no answers, 3: share of yes answers */
	return sprintf( __( '%1$d yes · %2$d no (%3$d%%)', 'labora' ), $c['yes'], $c['no'], round( 100 * $c['yes'] / $total ) );
}

add_filter( 'manage_post_posts_columns', function ( array $cols ) {
	$cols['labora_helpful'] = __( 'Helpful', 'labora' );
	return $cols;
} );
add_action( 'manage_post_posts_custom_column', function ( string $col, int $post_id ) {
	if ( 'labora_helpful' === $col ) {
		echo esc_html( labora_helpful_summary( $post_id ) );
	}
}, 10, 2 );

add_action( 'add_meta_boxes_post', function ( WP_Post $post ) {
	add_meta_box( 'labora-helpful', __( 'Reader feedback', 'labora' ), function ( WP_Post $post ) {
		echo '<p><strong>' . esc_html__( 'Was this article helpful?', 'labora' ) . '</strong><br>' . esc_html( labora_helpful_summary( $post->ID ) ) . '</p>';
		$notes = array_reverse( array_filter( (array) get_post_meta( $post->ID, '_labora_helpful_notes', true ) ) );
		if ( ! $notes ) {
			echo '<p class="description">' . esc_html__( 'Notes readers leave after answering No appear here.', 'labora' ) . '</p>';
			return;
		}
		echo '<p><strong>' . esc_html__( 'What was missing (newest first)', 'labora' ) . '</strong></p><ul style="max-height:240px;overflow:auto;margin:0">';
		foreach ( array_slice( $notes, 0, 20 ) as $n ) {
			printf( '<li style="padding:.4rem 0;border-top:1px solid #dcdcde"><small>%s</small><br>%s</li>', esc_html( mysql2date( get_option( 'date_format' ), $n['date'] ?? '' ) ), esc_html( $n['text'] ?? '' ) );
		}
		echo '</ul>';
	}, 'post', 'side', 'low' );
} );
