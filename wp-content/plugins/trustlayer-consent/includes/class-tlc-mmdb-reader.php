<?php
/**
 * Minimal reader for MaxMind DB files (.mmdb: GeoLite2 / GeoIP2 Country and City), with no dependencies.
 * Implements the MaxMind DB format spec 2.0 (https://maxmind.github.io/MaxMind-DB/) for lookups only.
 *
 *   $reader = new TLC_Mmdb_Reader( '/path/GeoLite2-Country.mmdb' );
 *   $record = $reader->get( '81.2.69.142' );   // array (country, subdivisions, ...) or null
 *
 * @package TrustLayer_Consent
 */

defined( 'ABSPATH' ) || exit;

final class TLC_Mmdb_Reader {

	private const METADATA_MARKER = "\xAB\xCD\xEFMaxMind.com";

	/** @var resource */
	private $fh;
	private int $node_count;
	private int $record_size;
	private int $ip_version;
	private int $tree_size;
	private int $data_start;
	private int $ipv4_start = -1;
	public array $metadata;

	public function __construct( string $file ) {
		if ( ! is_readable( $file ) ) {
			throw new RuntimeException( 'The MaxMind database file cannot be read.' );
		}
		$this->fh = fopen( $file, 'rb' ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		$size     = filesize( $file );
		// The metadata section is at most 128 KiB from the end, after the last marker
		$tail_len = min( $size, 128 * 1024 );
		fseek( $this->fh, $size - $tail_len );
		$tail = fread( $this->fh, $tail_len ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		$pos  = strrpos( $tail, self::METADATA_MARKER );
		if ( false === $pos ) {
			throw new RuntimeException( 'This is not a MaxMind database file.' );
		}
		$meta_start = $size - $tail_len + $pos + strlen( self::METADATA_MARKER );
		list( $meta )   = $this->decode( $meta_start, $meta_start );
		$this->metadata = (array) $meta;

		$this->node_count  = (int) ( $meta['node_count'] ?? 0 );
		$this->record_size = (int) ( $meta['record_size'] ?? 0 );
		$this->ip_version  = (int) ( $meta['ip_version'] ?? 0 );
		if ( ! in_array( $this->record_size, array( 24, 28, 32 ), true ) || $this->node_count < 1 ) {
			throw new RuntimeException( 'Unsupported MaxMind database.' );
		}
		$this->tree_size  = intdiv( $this->record_size * 2, 8 ) * $this->node_count;
		$this->data_start = $this->tree_size + 16;
	}

	public function __destruct() {
		if ( is_resource( $this->fh ) ) {
			fclose( $this->fh ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		}
	}

	/** The record for an IP address, or null when the database has none. */
	public function get( string $ip ): ?array {
		$packed = @inet_pton( $ip ); // phpcs:ignore WordPress.PHP.NoSilencedErrors
		if ( false === $packed ) {
			return null;
		}
		$bits = strlen( $packed ) * 8;
		if ( 128 === $bits && 4 === $this->ip_version ) {
			return null; // IPv6 address, IPv4-only database
		}
		$node = ( 32 === $bits && 6 === $this->ip_version ) ? $this->ipv4_start_node() : 0;
		for ( $i = 0; $i < $bits && $node < $this->node_count; $i++ ) {
			$bit  = 1 & ( ord( $packed[ $i >> 3 ] ) >> ( 7 - ( $i % 8 ) ) );
			$node = $this->read_node( $node, $bit );
		}
		if ( $node === $this->node_count || $node < $this->node_count ) {
			return null; // no data for this address
		}
		$offset = $node - $this->node_count + $this->tree_size; // file offset of the record
		list( $record ) = $this->decode( $offset, $this->data_start );
		return is_array( $record ) ? $record : null;
	}

	/** IPv4 addresses live under 96 zero bits in an IPv6 tree. */
	private function ipv4_start_node(): int {
		if ( $this->ipv4_start < 0 ) {
			$node = 0;
			for ( $i = 0; $i < 96 && $node < $this->node_count; $i++ ) {
				$node = $this->read_node( $node, 0 );
			}
			$this->ipv4_start = $node;
		}
		return $this->ipv4_start;
	}

	private function read_node( int $node, int $index ): int {
		$base = $node * intdiv( $this->record_size * 2, 8 );
		switch ( $this->record_size ) {
			case 24:
				$b = $this->read( $base + $index * 3, 3 );
				return unpack( 'N', "\0" . $b )[1];
			case 28:
				$b = $this->read( $base, 7 );
				if ( 0 === $index ) {
					return ( ( ord( $b[3] ) & 0xF0 ) << 20 ) | unpack( 'N', "\0" . substr( $b, 0, 3 ) )[1];
				}
				return ( ( ord( $b[3] ) & 0x0F ) << 24 ) | unpack( 'N', "\0" . substr( $b, 4, 3 ) )[1];
			default: // 32
				return unpack( 'N', $this->read( $base + $index * 4, 4 ) )[1];
		}
	}

	private function read( int $offset, int $length ): string {
		if ( 0 === $length ) {
			return '';
		}
		fseek( $this->fh, $offset );
		$data = fread( $this->fh, $length ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		if ( false === $data || strlen( $data ) !== $length ) {
			throw new RuntimeException( 'Unexpected end of the MaxMind database.' );
		}
		return $data;
	}

	/**
	 * Decode one value at a file offset. $section_start is where pointers are relative to (data or metadata section).
	 * Returns [ value, offset after the value ].
	 */
	private function decode( int $offset, int $section_start ): array {
		$ctrl = ord( $this->read( $offset, 1 ) );
		++$offset;
		$type = $ctrl >> 5;

		if ( 1 === $type ) { // pointer
			$ss    = ( $ctrl >> 3 ) & 0x3;
			$vvv   = $ctrl & 0x7;
			$bytes = $this->read( $offset, $ss + 1 );
			$offset += $ss + 1;
			switch ( $ss ) {
				case 0:
					$ptr = ( $vvv << 8 ) | ord( $bytes );
					break;
				case 1:
					$ptr = ( ( $vvv << 16 ) | unpack( 'n', $bytes )[1] ) + 2048;
					break;
				case 2:
					$ptr = ( ( $vvv << 24 ) | unpack( 'N', "\0" . $bytes )[1] ) + 526336;
					break;
				default:
					$ptr = unpack( 'N', $bytes )[1];
			}
			list( $value ) = $this->decode( $section_start + $ptr, $section_start );
			return array( $value, $offset );
		}

		if ( 0 === $type ) { // extended type
			$type = 7 + ord( $this->read( $offset, 1 ) );
			++$offset;
		}

		$size = $ctrl & 0x1F;
		if ( $size >= 29 ) {
			$extra = $size - 28;
			$b     = $this->read( $offset, $extra );
			$offset += $extra;
			$n     = unpack( 'N', str_pad( $b, 4, "\0", STR_PAD_LEFT ) )[1];
			$size  = 29 === $size ? 29 + $n : ( 30 === $size ? 285 + $n : 65821 + $n );
		}

		switch ( $type ) {
			case 2: // UTF-8 string
				return array( $this->read( $offset, $size ), $offset + $size );
			case 3: // double
				return array( unpack( 'E', $this->read( $offset, 8 ) )[1], $offset + 8 );
			case 4: // bytes
				return array( $this->read( $offset, $size ), $offset + $size );
			case 5: // uint16
			case 6: // uint32
			case 8: // int32
			case 9: // uint64
			case 10: // uint128
				$b = $this->read( $offset, $size );
				$n = 0;
				for ( $i = 0; $i < $size; $i++ ) {
					$n = ( $n << 8 ) | ord( $b[ $i ] ); // large values overflow; only small ints are used here
				}
				if ( 8 === $type && 4 === $size && $n & 0x80000000 ) {
					$n -= 0x100000000;
				}
				return array( $n, $offset + $size );
			case 7: // map
				$map = array();
				for ( $i = 0; $i < $size; $i++ ) {
					list( $key, $offset )   = $this->decode( $offset, $section_start );
					list( $value, $offset ) = $this->decode( $offset, $section_start );
					$map[ (string) $key ]   = $value;
				}
				return array( $map, $offset );
			case 11: // array
				$list = array();
				for ( $i = 0; $i < $size; $i++ ) {
					list( $list[], $offset ) = $this->decode( $offset, $section_start );
				}
				return array( $list, $offset );
			case 14: // boolean (the value is the size)
				return array( 0 !== $size, $offset );
			case 15: // float
				return array( unpack( 'G', $this->read( $offset, 4 ) )[1], $offset + 4 );
			default: // 12 data cache container, 13 end marker: not used in lookups
				return array( null, $offset + $size );
		}
	}
}
