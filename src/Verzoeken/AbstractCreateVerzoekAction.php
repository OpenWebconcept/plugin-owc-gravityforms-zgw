<?php

declare(strict_types=1);

/**
 * Abstract create "zaak" action.
 *
 * @package OWC_GravityForms_ZGW
 * @author  Yard | Digital Agency
 * @since   1.0.0
 */

namespace OWCGravityFormsZGW\Verzoeken;

/**
 * Exit when accessed directly.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use DateTime;
use GF_Field;
use OWCGravityFormsZGW\ContainerResolver;
use OWCGravityFormsZGW\GravityForms\FormUtils;
use OWCGravityFormsZGW\LoggerZGW;
use OWCGravityFormsZGW\Traits\MergeTagTranslator;

/**
 * Abstract create "zaak" action.
 *
 * @since 1.0.0
 */
abstract class AbstractCreateVerzoekAction
{
	use MergeTagTranslator;

	protected array $entry;
	protected array $form;
	protected LoggerZGW $logger;

	public function __construct(array $entry, array $form )
	{
		$this->entry  = $entry;
		$this->form   = $form;
		$this->logger = ContainerResolver::make()->get( 'logger.zgw' );
	}

	abstract public function create();

	/**
	 * Merge mapped arguments with defaults.
	 */
	protected function get_mapped_required_zaak_creation_args(): array
	{
		$mapped_args = array(
			'start_at'       => array(
				'value'    => date( 'Y-m-d' ),
				'location' => 'record.startAt',
			),
			'naam'           => array(
				'value'    => '',
				'location' => 'record.data.data.naam',
			),
			'omschrijving'   => array(
				'value'    => '',
				'location' => 'record.data.data.omschrijving',
			),
			'telefoonnummer' => array(
				'value'    => '',
				'location' => 'record.data.data.telefoonnummer',
			),
		);

		$mapped_args = $this->map_required_zaak_creation_args( $mapped_args );

		$payload = array(
			'type'   => FormUtils::object_type_identifier_verzoeken_form_setting( $this->form ),
			'record' => array(
				'typeVersion' => 1,
				'startAt'     => '',
				'data'        => array(
					'bsn'           => $this->get_bsn_for_zaak_rol(),
					'submission_id' => '1',
					'type'          => 'terugbelnotitie',
					'data'          => array(
						'naam'           => '',
						'omschrijving'   => '',
						'telefoonnummer' => '',
					),
				),
			),
		);

		foreach ( $mapped_args as $arg ) {
			$location_segments = explode( '.', $arg['location'] );
			$ref               = &$payload;

			foreach ( $location_segments as $segment ) {
				// creëer nested arrays indien nodig
				if ( ! isset( $ref[ $segment ] ) ) {
					$ref[ $segment ] = array();
				}

				$ref = &$ref[ $segment ];
			}

			$ref = $arg['value'];
			unset( $ref );
		}

		return $payload;
	}

	/**
	 * Add form field values to arguments required for creating a "zaak".
	 * The mapping is based on the relationship between argument keys
	 * and form fields via their mappedFieldValueZGW values.
	 */
	protected function map_required_zaak_creation_args(array $args ): array
	{
		foreach ( $this->form['fields'] as $field ) {
			if ( ! isset( $field->mappedFieldValueZGW ) || ! is_string( $field->mappedFieldValueZGW ) || '' === $field->mappedFieldValueZGW || ! isset( $args[ $field->mappedFieldValueZGW ] ) ) {
				continue;
			}

			$field_value = $this->handle_verzoek_creation_arg_value( $field );

			if ( empty( $field_value ) ) {
				continue;
			}

			if ( 'date' === $field->type ) {
				$field_value = ( new DateTime( $field_value ) )->format( 'Y-m-d' );
			}

			$args[ $field->mappedFieldValueZGW ]['value'] = $this->translate_merge_tags( $this->entry, $this->form, $field_value );
		}

		return $args;
	}

	/**
	 * Handle getting the value for a "zaak" creation argument from multiple form fields and types.
	 * Checkboxes for example can have multiple inputs.
	 *
	 * @since NEXT
	 */
	protected function handle_verzoek_creation_arg_value(GF_Field $field ): string
	{
		if ( isset( $field->inputs ) && is_array( $field->inputs ) ) {
			$field_value  = '';
			$input_values = array();

			foreach ( $field->inputs as $input ) {
				$input_id = (string) ( $input['id'] ?? '' );

				if ( '' === $input_id ) {
					continue;
				}

				$input_value = rgar( $this->entry, $input_id );

				if ( ! is_string( $input_value ) || '' === $input_value ) {
					continue;
				}

				$input_values[] = trim( $input_value );
			}

			$count = count( $input_values );

			$field_value = match ( true ) {
				0 === $count => '',
				1 === $count => $input_values[0],
				2 === $count => implode( ' en ', $input_values ),
				2 < $count => implode( ', ', array_slice( $input_values, 0, -1 ) ) . ' en ' . end( $input_values ),
			};
		} else {
			$field_value = rgar( $this->entry, (string) $field->id );
		}

		return is_string( $field_value ) && '' !== $field_value ? $field_value : '';
	}

	/**
	 * Retrieve BSN from the current user or from the overwrite form setting.
	 *
	 * @since NEXT
	 */
	private function get_bsn_for_zaak_rol(): ?string
	{
		$bsn_overwrite = FormUtils::overwrite_bsn_form_setting_verzoeken( $this->form );

		if ( is_string( $bsn_overwrite ) && '' !== $bsn_overwrite ) {
			return $bsn_overwrite;
		}

		return ContainerResolver::make()->get( 'digid.current_user_bsn' );
	}

	/**
	 * Retrieve KVK from the current user or from the overwrite form setting.
	 *
	 * @since NEXT
	 */
	private function get_kvk_for_zaak_rol(): ?string
	{
		$kvk_overwrite = FormUtils::overwrite_kvk_form_setting_verzoeken( $this->form );

		if ( is_string( $kvk_overwrite ) && '' !== $kvk_overwrite ) {
			return $kvk_overwrite;
		}

		return ContainerResolver::make()->get( 'eherkenning.current_user_kvk' );
	}

	/**
	 * Store generated "verzoek" information in the entry's metadata.
	 * Could be used for further processing or debugging.
	 */
	protected function add_created_verzoek_as_entry_meta(array $verzoek ): void
	{
		add_action(
			'gform_after_submission',
			function (array $entry, array $form ) use ($verzoek ) {
				gform_update_meta( $entry['id'], 'owc_gz_created_verzoek_url', $verzoek['url'] ?? null );
				gform_update_meta( $entry['id'], 'owc_gz_created_verzoek_uuid', $verzoek['uuid'] ?? null );
			},
			10,
			2
		);
	}
}
