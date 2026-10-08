<?php
/**
 * The consent log list (TrustLayer > Consent log): newest first, filter by choice and date, search by consent ID.
 *
 * @package TrustLayer_Consent
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

final class TLC_Logs_Table extends WP_List_Table {

	private array $titles = array();

	public function __construct() {
		parent::__construct( array( 'singular' => 'consent', 'plural' => 'consents', 'ajax' => false ) );
		foreach ( (array) ( TLC_Settings::saved()['categories'] ?? array() ) as $cat ) {
			$this->titles[ $cat['id'] ] = $cat['title'];
		}
	}

	/** The current filters, also used for the CSV export link. */
	public function filters(): array {
		// phpcs:disable WordPress.Security.NonceVerification
		return array_filter( array(
			's'              => sanitize_text_field( wp_unslash( $_GET['s'] ?? '' ) ),
			'consent_action' => sanitize_key( $_GET['consent_action'] ?? '' ),
			'from'           => sanitize_text_field( wp_unslash( $_GET['from'] ?? '' ) ),
			'to'             => sanitize_text_field( wp_unslash( $_GET['to'] ?? '' ) ),
		) );
		// phpcs:enable
	}

	public function get_columns() {
		return array(
			'created_at' => __( 'Time', 'trustlayer-consent' ),
			'consent_id' => __( 'Consent ID', 'trustlayer-consent' ),
			'action'     => __( 'Choice', 'trustlayer-consent' ),
			'categories' => __( 'Allowed', 'trustlayer-consent' ),
			'model'      => __( 'Model and region', 'trustlayer-consent' ),
			'url'        => __( 'Page', 'trustlayer-consent' ),
			'ip'         => __( 'IP (shortened)', 'trustlayer-consent' ),
		);
	}

	public function prepare_items() {
		$per_page = 30;
		$f        = $this->filters();
		$result   = TLC_Log::query( array(
			'search'   => $f['s'] ?? '',
			'action'   => $f['consent_action'] ?? '',
			'from'     => $f['from'] ?? '',
			'to'       => $f['to'] ?? '',
			'per_page' => $per_page,
			'page'     => $this->get_pagenum(),
		) );
		$this->items           = $result['rows'];
		$this->_column_headers = array( $this->get_columns(), array(), array() );
		$this->set_pagination_args( array( 'total_items' => $result['total'], 'per_page' => $per_page ) );
	}

	protected function extra_tablenav( $which ) {
		if ( 'top' !== $which ) {
			return;
		}
		$f = $this->filters();
		echo '<div class="alignleft actions"><label class="screen-reader-text" for="tlc-filter-action">' . esc_html__( 'Choice', 'trustlayer-consent' ) . '</label>';
		echo '<select name="consent_action" id="tlc-filter-action"><option value="">' . esc_html__( 'All choices', 'trustlayer-consent' ) . '</option>';
		foreach ( TLC_Log::action_labels() as $value => $label ) {
			printf( '<option value="%s" %s>%s</option>', esc_attr( $value ), selected( $f['consent_action'] ?? '', $value, false ), esc_html( $label ) );
		}
		echo '</select> ';
		printf( '<label>%s <input type="date" name="from" value="%s"></label> ', esc_html__( 'From', 'trustlayer-consent' ), esc_attr( $f['from'] ?? '' ) );
		printf( '<label>%s <input type="date" name="to" value="%s"></label> ', esc_html__( 'to', 'trustlayer-consent' ), esc_attr( $f['to'] ?? '' ) );
		submit_button( __( 'Filter', 'trustlayer-consent' ), '', 'filter_action', false );
		echo '</div>';
	}

	public function no_items() {
		esc_html_e( 'No consent choices recorded yet.', 'trustlayer-consent' );
	}

	protected function column_default( $item, $column_name ) {
		switch ( $column_name ) {
			case 'created_at':
				$time = strtotime( $item['created_at'] . ' UTC' );
				return esc_html( wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $time ) );
			case 'consent_id':
				return '<code>' . esc_html( $item['consent_id'] ) . '</code>';
			case 'action':
				return esc_html( TLC_Log::action_labels()[ $item['action'] ] ?? $item['action'] );
			case 'categories':
				$cats = json_decode( (string) $item['categories'], true );
				if ( ! is_array( $cats ) ) {
					return '';
				}
				$out = '';
				foreach ( $cats as $id => $on ) {
					$out .= sprintf( '<span class="tlc-chip %s">%s</span> ', $on ? 'is-on' : 'is-off', esc_html( $this->titles[ $id ] ?? $id ) );
				}
				return $out;
			case 'model':
				/* translators: %d: consent revision */
				return esc_html( trim( $item['model'] . ' ' . $item['region'] ) ) . '<br><span class="description">' . esc_html( sprintf( __( 'revision %d', 'trustlayer-consent' ), (int) $item['revision'] ) ) . '</span>';
			case 'url':
				return '<code>' . esc_html( $item['url'] ?: '/' ) . '</code>';
			case 'ip':
				return esc_html( $item['ip'] ) . ( $item['user_agent'] ? '<br><span class="description tlc-ua" title="' . esc_attr( $item['user_agent'] ) . '">' . esc_html( wp_trim_words( $item['user_agent'], 6, '...' ) ) . '</span>' : '' );
		}
		return '';
	}
}
