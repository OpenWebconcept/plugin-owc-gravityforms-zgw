<?php
/**
 * MergeTagTranslator trait.
 *
 * Translates merge tags in given strings based on the provided entry object.
 *
 * @package OWC_GravityForms_ZGW
 * @author  Yard | Digital Agency
 * @since   1.0.0
 */

namespace OWCGravityFormsZGW\Traits;

/**
 * Exit when accessed directly.
 */
if ( ! defined( 'ABSPATH' )) {
	exit;
}

use DateTime;
use Exception;
use GF_Field;

/**
 * MergeTagTranslator trait.
 *
 * @since 1.0.0
 */
trait MergeTagTranslator
{
	/**
	 * Translates merge tags in the given string based on the provided entry object.
	 * Merge tags are in the format [field_id].
	 *
	 * @since 1.0.0
	 */
	public function translate_merge_tags(array $entry, string $value ): string
	{
		return preg_replace_callback(
			'/\[[^\]]+\]/',
			function ($matches ) use ($entry ) {
				$field_id    = trim( $matches[0], '[]' );
				$field_value = $this->resolve_field_value( $field_id, $entry );

				if ( ! is_string( $field_value ) || trim( $field_value ) === '') {
					return '';
				}

				$is_date = $this->format_possible_date_in_merge_tag( $field_value );

				return $is_date !== '' ? $is_date : $field_value;
			},
			$value
		);
	}

	protected function resolve_field_value(string $field_id, array $entry ): mixed
	{
		if ($field = $this->field_by_type( $field_id, 'checkbox' )) {
			return $this->implode_with_conjunction( $this->handle_checkbox_field( $entry, $field ) );
		}

		if ($this->field_by_type( $field_id, 'multiselect' )) {
			$value = json_decode( rgar( $entry, $field_id ), true ) ?: array();

			return $this->implode_with_conjunction( $value );
		}

		return rgar( $entry, $field_id );
	}

	protected function handle_checkbox_field(array $entry, GF_Field $field ): array
	{
		$field_value = array();

		foreach ($field->inputs as $input) {
			$field_value[] = rgar( $entry, $input['id'] );
		}

		return array_filter( $field_value, fn ($item ) => trim( $item ) !== '' );
	}

	/**
	 * Returns the field by ID and type if they match.
	 *
	 * @since 1.0.0
	 */
	protected function field_by_type(string $field_id, string $field_type ): ?GF_Field
	{
		$fields = array_filter(
			$this->form['fields'],
			function ($field ) use ($field_id, $field_type ) {
				return $field->id == $field_id && $field_type === $field->type;
			}
		);

		$field = reset( $fields );

		return $field instanceof GF_Field ? $field : null;
	}

	/**
	 * Converts an array of items to a string, separating them with commas and 'en' for the last item.
	 *
	 * @since 1.0.0
	 */
	protected function implode_with_onjunction(array $items, string $conjunction = 'en' ): ?string
	{
		$items = array_filter( $items, fn ($item ) => trim( $item ) !== '' );

		if (empty( $items )) {
			return null;
		}

		if (count( $items ) === 1) {
			return reset( $items );
		}

		$lastItem = array_pop( $items );

		return sprintf( '%s %s %s', implode( ', ', $items ), $conjunction, $lastItem );
	}

	/**
	 * Converts a date within a merge tag to the specified format.
	 *
	 * @since 1.0.0
	 */
	protected function format_possible_date_in_merge_tag(string $value, string $format = 'd-m-Y' ): string
	{
		/**
		 * A valid date string has a length of 10.
		 * Housenumber additions could be 'B' for example, 'B' also corrensponds with a timezone.
		 */
		if (strlen( $value ) !== 10) {
			return '';
		}

		try {
			$date = new DateTime( $value );
		} catch (Exception $e) {
			return '';
		}

		return $date->format( $format );
	}
}
