<?php
/**
 * Form field helpers for the settings tabs. Field names are tlc[section][key] (or tlc[section][key][sub]);
 * values come from the saved option, and a "Set in code" badge shows when TLC_CONFIG or a filter overrides one.
 *
 * @package TrustLayer_Consent
 */

defined( 'ABSPATH' ) || exit;

final class TLC_Admin_Fields {

	public static function name( string $section, string $key, ?string $sub = null ): string {
		return "tlc[{$section}][{$key}]" . ( null !== $sub ? "[{$sub}]" : '' );
	}

	public static function id( string $section, string $key, ?string $sub = null ): string {
		return 'tlc-' . $section . '-' . $key . ( null !== $sub ? '-' . $sub : '' );
	}

	public static function value( string $section, string $key, ?string $sub = null ) {
		$saved = TLC_Settings::saved();
		$value = $saved[ $section ][ $key ] ?? '';
		return null !== $sub ? ( $value[ $sub ] ?? '' ) : $value;
	}

	public static function badge( string $path ): string {
		return TLC_Settings::is_overridden( $path ) ? ' <span class="tlc-badge" title="' . esc_attr__( 'TLC_CONFIG or the tlc_settings filter sets this value; the saved value below is not used.', 'trustlayer-consent' ) . '">' . esc_html__( 'Set in code', 'trustlayer-consent' ) . '</span>' : '';
	}

	/** A form-table row. */
	public static function row( string $label, string $field, string $help = '', string $for = '', string $path = '' ): void {
		echo '<tr><th scope="row">' . ( $for ? '<label for="' . esc_attr( $for ) . '">' . esc_html( $label ) . '</label>' : esc_html( $label ) ) . '</th><td>';
		echo $field; // phpcs:ignore WordPress.Security.EscapeOutput -- built by the helpers below
		if ( $path ) {
			echo self::badge( $path ); // phpcs:ignore WordPress.Security.EscapeOutput
		}
		if ( $help ) {
			echo '<p class="description">' . wp_kses( $help, array( 'code' => array(), 'a' => array( 'href' => true, 'target' => true, 'rel' => true ), 'strong' => array(), 'br' => array() ) ) . '</p>';
		}
		echo '</td></tr>';
	}

	public static function checkbox( string $section, string $key, string $label, string $help = '' ): void {
		$field = sprintf(
			'<label><input type="checkbox" name="%s" id="%s" value="1" %s> %s</label>',
			esc_attr( self::name( $section, $key ) ),
			esc_attr( self::id( $section, $key ) ),
			checked( ! empty( self::value( $section, $key ) ), true, false ),
			esc_html( $label )
		);
		echo '<fieldset>' . $field . self::badge( "{$section}.{$key}" ) . '</fieldset>'; // phpcs:ignore WordPress.Security.EscapeOutput
		if ( $help ) {
			echo '<p class="description">' . wp_kses( $help, array( 'code' => array(), 'strong' => array(), 'a' => array( 'href' => true, 'target' => true, 'rel' => true ) ) ) . '</p>';
		}
	}

	public static function text( string $section, string $key, array $attrs = array(), ?string $sub = null ): string {
		$attrs = array_merge( array( 'type' => 'text', 'class' => 'regular-text' ), $attrs );
		$html  = '';
		foreach ( $attrs as $name => $value ) {
			$html .= sprintf( ' %s="%s"', esc_attr( $name ), esc_attr( (string) $value ) );
		}
		return sprintf( '<input name="%s" id="%s" value="%s"%s>', esc_attr( self::name( $section, $key, $sub ) ), esc_attr( self::id( $section, $key, $sub ) ), esc_attr( (string) self::value( $section, $key, $sub ) ), $html );
	}

	public static function number( string $section, string $key, int $min, int $max, string $suffix = '' ): string {
		return self::text( $section, $key, array( 'type' => 'number', 'class' => 'small-text', 'min' => $min, 'max' => $max ) ) . ( $suffix ? ' ' . esc_html( $suffix ) : '' );
	}

	public static function select( string $section, string $key, array $options, ?string $sub = null ): string {
		$current = (string) self::value( $section, $key, $sub );
		$html    = sprintf( '<select name="%s" id="%s">', esc_attr( self::name( $section, $key, $sub ) ), esc_attr( self::id( $section, $key, $sub ) ) );
		foreach ( $options as $value => $label ) {
			$html .= sprintf( '<option value="%s" %s>%s</option>', esc_attr( (string) $value ), selected( $current, (string) $value, false ), esc_html( $label ) );
		}
		return $html . '</select>';
	}

	public static function textarea( string $section, string $key, int $rows = 4, string $class = 'large-text', ?string $sub = null, bool $disabled = false ): string {
		return sprintf(
			'<textarea name="%s" id="%s" rows="%d" class="%s"%s>%s</textarea>',
			esc_attr( self::name( $section, $key, $sub ) ),
			esc_attr( self::id( $section, $key, $sub ) ),
			$rows,
			esc_attr( $class ),
			$disabled ? ' disabled' : '',
			esc_textarea( (string) self::value( $section, $key, $sub ) )
		);
	}

	public static function color( string $section, string $key, string $sub ): string {
		$value = (string) self::value( $section, $key, $sub );
		return sprintf(
			'<input type="color" name="%s" id="%s" value="%s" class="tlc-color"> <code class="tlc-color-value">%s</code>',
			esc_attr( self::name( $section, $key, $sub ) ),
			esc_attr( self::id( $section, $key, $sub ) ),
			esc_attr( $value ),
			esc_html( $value )
		);
	}

	/** Options for a category select (rules, handles). */
	public static function category_options( bool $optional_only = true ): array {
		$out = array();
		foreach ( (array) ( TLC_Settings::saved()['categories'] ?? array() ) as $cat ) {
			if ( $optional_only && ! empty( $cat['locked'] ) ) {
				continue;
			}
			$out[ $cat['id'] ] = $cat['title'] . ( empty( $cat['enabled'] ) ? ' ' . __( '(off)', 'trustlayer-consent' ) : '' );
		}
		return $out;
	}

	public static function options_html( array $options, string $current ): string {
		$html = '';
		foreach ( $options as $value => $label ) {
			$html .= sprintf( '<option value="%s" %s>%s</option>', esc_attr( (string) $value ), selected( $current, (string) $value, false ), esc_html( $label ) );
		}
		return $html;
	}
}
