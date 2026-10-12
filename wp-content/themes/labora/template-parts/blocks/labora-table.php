<?php
/**
 * Block: Comparison table (inc/blog-blocks.php). The article's data table, with yes / no / partial marks and an
 * optional highlighted column. On phones each row becomes a labeled card (blog.css, .data-table).
 *
 * @package Labora
 * @var bool $is_preview
 */

defined( 'ABSPATH' ) || exit;

$labora_cols = array();
for ( $labora_c = 1; $labora_c <= 4; $labora_c++ ) {
	$labora_head = trim( (string) get_field( "col{$labora_c}" ) );
	if ( '' !== $labora_head ) {
		$labora_cols[ $labora_c ] = $labora_head;
	}
}
$labora_rows = array_values( array_filter( (array) get_field( 'rows' ), fn( $r ) => '' !== trim( (string) ( $r['feature'] ?? '' ) ) ) );
if ( count( $labora_cols ) < 1 || ! $labora_rows ) {
	if ( $is_preview ) {
		echo '<p class="labora-block-empty">' . esc_html__( 'Comparison table: add column headings and rows in the block settings.', 'labora' ) . '</p>';
	}
	return;
}
$labora_hi    = (int) get_field( 'highlight' );
$labora_first = trim( (string) get_field( 'feature_label' ) ) ?: __( 'What matters', 'labora' );
$labora_marks = array(
	'yes'     => array( 'check', __( 'Yes', 'labora' ) ),
	'no'      => array( 'x', __( 'No', 'labora' ) ),
	'partial' => array( '', __( 'Partial', 'labora' ) ),
);
$labora_cell = function ( string $value ) use ( $labora_marks ): string {
	$key = strtolower( trim( $value ) );
	if ( ! isset( $labora_marks[ $key ] ) ) {
		return esc_html( $value );
	}
	list( $icon, $word ) = $labora_marks[ $key ];
	$mark = $icon ? '<svg class="icon" aria-hidden="true"><use href="#i-' . $icon . '"/></svg>' : '<span class="mark-half" aria-hidden="true"></span>';
	return '<span class="mark mark--' . $key . '">' . $mark . '<span class="mark-word">' . esc_html( $word ) . '</span></span>';
};
?>
<figure class="ctable<?php echo $labora_hi && isset( $labora_cols[ $labora_hi ] ) ? ' ctable--hi' : ''; ?>">
        <div class="table-wrap">
          <table class="data-table">
            <thead><tr><th scope="col"><?php echo esc_html( $labora_first ); ?></th><?php foreach ( $labora_cols as $labora_c => $labora_head ) : ?><th scope="col"<?php echo $labora_c === $labora_hi ? ' class="is-hi"' : ''; ?>><?php echo esc_html( $labora_head ); ?></th><?php endforeach; ?></tr></thead>
            <tbody>
<?php foreach ( $labora_rows as $labora_row ) : ?>
              <tr><td><?php echo esc_html( trim( (string) $labora_row['feature'] ) ); ?></td><?php foreach ( $labora_cols as $labora_c => $labora_head ) : ?><td data-label="<?php echo esc_attr( $labora_head ); ?>"<?php echo $labora_c === $labora_hi ? ' class="is-hi"' : ''; ?>><?php echo $labora_cell( (string) ( $labora_row[ "c{$labora_c}" ] ?? '' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?></td><?php endforeach; ?></tr>
<?php endforeach; ?>
            </tbody>
          </table>
        </div>
<?php if ( $labora_note = trim( (string) get_field( 'note' ) ) ) : ?>
        <figcaption class="table-note"><?php echo esc_html( $labora_note ); ?></figcaption>
<?php endif; ?>
      </figure>
