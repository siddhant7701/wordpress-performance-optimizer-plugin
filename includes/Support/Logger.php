<?php
/**
 * Simple, bounded logger.
 *
 * @package UPO
 */

declare( strict_types=1 );

namespace UPO\Support;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Stores a rolling log of plugin events in an option.
 *
 * We keep at most self::MAX_ENTRIES lines to avoid unbounded growth, and we
 * never log personal data.
 */
final class Logger {

	/**
	 * Option key for the log store.
	 */
	private const OPTION = 'upo_log';

	/**
	 * Maximum number of retained entries.
	 */
	private const MAX_ENTRIES = 300;

	/**
	 * Log levels.
	 */
	public const INFO    = 'info';
	public const NOTICE  = 'notice';
	public const WARNING = 'warning';
	public const ERROR   = 'error';

	/**
	 * Whether logging is enabled.
	 *
	 * @var bool
	 */
	private bool $enabled;

	/**
	 * Constructor.
	 *
	 * @param bool $enabled Whether logging is active.
	 */
	public function __construct( bool $enabled = true ) {
		$this->enabled = $enabled;
	}

	/**
	 * Add a log entry.
	 *
	 * @param string $message Message (already translated / plain).
	 * @param string $level   One of the level constants.
	 * @param string $context Optional short context tag.
	 * @return void
	 */
	public function log( string $message, string $level = self::INFO, string $context = '' ): void {
		if ( ! $this->enabled ) {
			return;
		}

		$entries   = $this->all();
		$entries[] = array(
			'time'    => time(),
			'level'   => $level,
			'context' => sanitize_key( $context ),
			'message' => sanitize_text_field( $message ),
		);

		if ( count( $entries ) > self::MAX_ENTRIES ) {
			$entries = array_slice( $entries, -self::MAX_ENTRIES );
		}

		update_option( self::OPTION, $entries, false );
	}

	/**
	 * Convenience wrappers.
	 *
	 * @param string $message Message.
	 * @param string $context Context.
	 * @return void
	 */
	public function info( string $message, string $context = '' ): void {
		$this->log( $message, self::INFO, $context );
	}

	/**
	 * Log a warning.
	 *
	 * @param string $message Message.
	 * @param string $context Context.
	 * @return void
	 */
	public function warning( string $message, string $context = '' ): void {
		$this->log( $message, self::WARNING, $context );
	}

	/**
	 * Log an error.
	 *
	 * @param string $message Message.
	 * @param string $context Context.
	 * @return void
	 */
	public function error( string $message, string $context = '' ): void {
		$this->log( $message, self::ERROR, $context );
	}

	/**
	 * Return every stored entry (newest last).
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public function all(): array {
		$entries = get_option( self::OPTION, array() );
		return is_array( $entries ) ? $entries : array();
	}

	/**
	 * Clear the log.
	 *
	 * @return void
	 */
	public function clear(): void {
		delete_option( self::OPTION );
	}
}
