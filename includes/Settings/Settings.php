<?php
/**
 * Settings store — reads, writes and sanitizes plugin options.
 *
 * @package UPO
 */

declare( strict_types=1 );

namespace UPO\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Central options gateway.
 *
 * All option access goes through this class so defaults, caching and
 * sanitization are consistent everywhere.
 */
final class Settings {

	/**
	 * Runtime cache of the merged option array.
	 *
	 * @var array<string, mixed>|null
	 */
	private ?array $cache = null;

	/**
	 * Get the full, defaults-merged settings array.
	 *
	 * @return array<string, mixed>
	 */
	public function all(): array {
		if ( null !== $this->cache ) {
			return $this->cache;
		}

		$stored   = get_option( UPO_OPTION, array() );
		$stored   = is_array( $stored ) ? $stored : array();
		$defaults = Settings_Schema::defaults();

		$this->cache = array_merge( $defaults, array_intersect_key( $stored, $defaults ) );

		return $this->cache;
	}

	/**
	 * Get a single setting value.
	 *
	 * @param string $key     Field id.
	 * @param mixed  $fallback Value to return if the key is unknown.
	 * @return mixed
	 */
	public function get( string $key, $fallback = null ) {
		$all = $this->all();
		return array_key_exists( $key, $all ) ? $all[ $key ] : $fallback;
	}

	/**
	 * Boolean check for a toggle. Always false while Safe Mode is on for
	 * frontend-affecting toggles is handled by the caller; this is the raw value.
	 *
	 * @param string $key Field id.
	 * @return bool
	 */
	public function is_enabled( string $key ): bool {
		return (bool) $this->get( $key, false );
	}

	/**
	 * Persist a full settings array after sanitizing it.
	 *
	 * @param array<string, mixed> $input Raw input (e.g. from $_POST).
	 * @return array<string, mixed> The sanitized, stored values.
	 */
	public function save( array $input ): array {
		$clean = $this->sanitize( $input );
		update_option( UPO_OPTION, $clean, false );
		$this->cache = null;
		return $this->all();
	}

	/**
	 * Update a single value immediately.
	 *
	 * @param string $key   Field id.
	 * @param mixed  $value Value.
	 * @return void
	 */
	public function update( string $key, $value ): void {
		$all         = $this->all();
		$all[ $key ] = $value;
		$this->save( $all );
	}

	/**
	 * Save only a subset of fields (e.g. the fields shown on one admin tab),
	 * leaving every other stored value untouched.
	 *
	 * This is what lets a tabbed UI submit one tab without resetting toggles on
	 * the other tabs to their defaults.
	 *
	 * @param array<string, mixed> $input Raw input (e.g. $_POST).
	 * @param string[]             $ids   Field ids that belong to the submitted tab.
	 * @return array<string, mixed> The merged, stored settings.
	 */
	public function save_fields( array $input, array $ids ): array {
		$current   = $this->all();
		$sanitized = $this->sanitize( $input );

		foreach ( $ids as $id ) {
			if ( array_key_exists( $id, $sanitized ) ) {
				$current[ $id ] = $sanitized[ $id ];
			}
		}

		update_option( UPO_OPTION, $current, false );
		$this->cache = null;

		return $this->all();
	}

	/**
	 * Replace the entire settings array from a validated import.
	 *
	 * @param array<string, mixed> $input Raw imported values.
	 * @return bool True on success.
	 */
	public function import( array $input ): bool {
		$clean = $this->sanitize( $input );
		$this->cache = null;
		return update_option( UPO_OPTION, $clean, false );
	}

	/**
	 * Export current settings as an associative array.
	 *
	 * @return array<string, mixed>
	 */
	public function export(): array {
		return $this->all();
	}

	/**
	 * Seed defaults on activation without clobbering existing values.
	 *
	 * @return void
	 */
	public function maybe_seed_defaults(): void {
		$stored = get_option( UPO_OPTION, null );
		if ( null === $stored ) {
			add_option( UPO_OPTION, Settings_Schema::defaults(), '', false );
		}
	}

	/**
	 * Reset every setting to its schema default.
	 *
	 * @return void
	 */
	public function reset(): void {
		update_option( UPO_OPTION, Settings_Schema::defaults(), false );
		$this->cache = null;
	}

	/**
	 * Sanitize an arbitrary input array against the schema.
	 *
	 * Unknown keys are dropped. Each field is coerced to the correct type.
	 *
	 * @param array<string, mixed> $input Raw input.
	 * @return array<string, mixed>
	 */
	public function sanitize( array $input ): array {
		$schema = Settings_Schema::flat();
		$clean  = array();

		foreach ( $schema as $id => $field ) {
			switch ( $field['type'] ) {
				case Settings_Schema::TYPE_TOGGLE:
					$clean[ $id ] = ! empty( $input[ $id ] );
					break;

				case Settings_Schema::TYPE_NUMBER:
					$value        = isset( $input[ $id ] ) ? (int) $input[ $id ] : (int) $field['default'];
					$min          = (int) $field['min'];
					$max          = (int) $field['max'];
					$value        = max( $min, $value );
					$value        = $max > 0 ? min( $max, $value ) : $value;
					$clean[ $id ] = $value;
					break;

				case Settings_Schema::TYPE_SELECT:
					$value        = isset( $input[ $id ] ) ? (string) $input[ $id ] : (string) $field['default'];
					$clean[ $id ] = array_key_exists( $value, (array) $field['options'] ) ? $value : (string) $field['default'];
					break;

				case Settings_Schema::TYPE_TEXTAREA:
					$value        = isset( $input[ $id ] ) ? (string) wp_unslash( $input[ $id ] ) : '';
					$clean[ $id ] = sanitize_textarea_field( $value );
					break;

				case Settings_Schema::TYPE_TEXT:
				default:
					$value        = isset( $input[ $id ] ) ? (string) wp_unslash( $input[ $id ] ) : '';
					$clean[ $id ] = sanitize_text_field( $value );
					break;
			}
		}

		return $clean;
	}

	/**
	 * Split a textarea/comma value into a clean array of lines.
	 *
	 * @param string $key Field id.
	 * @return string[]
	 */
	public function get_lines( string $key ): array {
		$raw   = (string) $this->get( $key, '' );
		$parts = preg_split( '/[\r\n,]+/', $raw ) ?: array();
		$parts = array_map( 'trim', $parts );
		return array_values( array_filter( $parts, static fn( $v ): bool => '' !== $v ) );
	}
}
