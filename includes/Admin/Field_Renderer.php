<?php
/**
 * Renders schema fields as accessible admin form controls.
 *
 * @package UPO
 */

declare( strict_types=1 );

namespace UPO\Admin;

use UPO\Settings\Settings_Schema;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Stateless renderer that turns a schema field + value into HTML.
 */
final class Field_Renderer {

	/**
	 * Render one field row.
	 *
	 * @param array<string, mixed> $field Schema field.
	 * @param mixed                $value Current value.
	 * @return void
	 */
	public static function render( array $field, $value ): void {
		$id   = (string) $field['id'];
		$name = UPO_OPTION . '[' . $id . ']';

		echo '<div class="upo-field upo-field--' . esc_attr( (string) $field['type'] ) . '">';
		echo '<div class="upo-field__body">';

		printf( '<label class="upo-field__label" for="%s">%s</label>', esc_attr( $id ), esc_html( (string) $field['label'] ) );

		if ( '' !== (string) $field['description'] ) {
			echo '<p class="upo-field__desc">' . esc_html( (string) $field['description'] ) . '</p>';
		}

		if ( empty( $field['safe'] ) ) {
			echo '<span class="upo-badge upo-badge--caution">' . esc_html__( 'Test after enabling', 'ultimate-performance-optimizer' ) . '</span>';
		}

		if ( '' !== (string) $field['note'] ) {
			echo '<p class="upo-field__note">' . esc_html( (string) $field['note'] ) . '</p>';
		}

		echo '</div>';

		echo '<div class="upo-field__control">';
		switch ( (string) $field['type'] ) {
			case Settings_Schema::TYPE_TOGGLE:
				self::toggle( $id, $name, (bool) $value );
				break;
			case Settings_Schema::TYPE_SELECT:
				self::select( $id, $name, (string) $value, (array) $field['options'] );
				break;
			case Settings_Schema::TYPE_NUMBER:
				self::number( $id, $name, (int) $value, (int) $field['min'], (int) $field['max'] );
				break;
			case Settings_Schema::TYPE_TEXTAREA:
				self::textarea( $id, $name, (string) $value );
				break;
			case Settings_Schema::TYPE_TEXT:
			default:
				self::text( $id, $name, (string) $value );
				break;
		}
		echo '</div>';

		echo '</div>';
	}

	/**
	 * Render a toggle switch.
	 *
	 * @param string $id      Field id.
	 * @param string $name    Input name.
	 * @param bool   $checked Checked state.
	 * @return void
	 */
	private static function toggle( string $id, string $name, bool $checked ): void {
		printf(
			'<label class="upo-switch"><input type="checkbox" id="%1$s" name="%2$s" value="1" %3$s><span class="upo-switch__track"></span></label>',
			esc_attr( $id ),
			esc_attr( $name ),
			checked( $checked, true, false )
		);
	}

	/**
	 * Render a select box.
	 *
	 * @param string                $id      Field id.
	 * @param string                $name    Input name.
	 * @param string                $value   Current value.
	 * @param array<string, string> $options Options.
	 * @return void
	 */
	private static function select( string $id, string $name, string $value, array $options ): void {
		printf( '<select id="%1$s" name="%2$s" class="upo-input">', esc_attr( $id ), esc_attr( $name ) );
		foreach ( $options as $key => $label ) {
			printf(
				'<option value="%1$s" %2$s>%3$s</option>',
				esc_attr( (string) $key ),
				selected( $value, (string) $key, false ),
				esc_html( (string) $label )
			);
		}
		echo '</select>';
	}

	/**
	 * Render a number input.
	 *
	 * @param string $id    Field id.
	 * @param string $name  Input name.
	 * @param int    $value Current value.
	 * @param int    $min   Minimum.
	 * @param int    $max   Maximum.
	 * @return void
	 */
	private static function number( string $id, string $name, int $value, int $min, int $max ): void {
		printf(
			'<input type="number" id="%1$s" name="%2$s" value="%3$d" min="%4$d" %5$s class="upo-input upo-input--number">',
			esc_attr( $id ),
			esc_attr( $name ),
			$value,
			$min,
			$max > 0 ? 'max="' . esc_attr( (string) $max ) . '"' : ''
		);
	}

	/**
	 * Render a text input.
	 *
	 * @param string $id    Field id.
	 * @param string $name  Input name.
	 * @param string $value Current value.
	 * @return void
	 */
	private static function text( string $id, string $name, string $value ): void {
		printf(
			'<input type="text" id="%1$s" name="%2$s" value="%3$s" class="upo-input upo-input--text">',
			esc_attr( $id ),
			esc_attr( $name ),
			esc_attr( $value )
		);
	}

	/**
	 * Render a textarea.
	 *
	 * @param string $id    Field id.
	 * @param string $name  Input name.
	 * @param string $value Current value.
	 * @return void
	 */
	private static function textarea( string $id, string $name, string $value ): void {
		printf(
			'<textarea id="%1$s" name="%2$s" rows="4" class="upo-input upo-input--textarea">%3$s</textarea>',
			esc_attr( $id ),
			esc_attr( $name ),
			esc_textarea( $value )
		);
	}
}
