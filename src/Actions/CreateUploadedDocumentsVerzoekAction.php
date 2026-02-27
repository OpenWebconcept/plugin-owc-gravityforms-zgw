<?php

declare(strict_types=1);

/**
 * Create uploaded documents action.
 *
 * @package OWC_GravityForms_ZGW
 * @author  Yard | Digital Agency
 * @since   1.0.0
 */

namespace OWCGravityFormsZGW\Actions;

/**
 * Exit when accessed directly.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use OWCGravityFormsZGW\Contracts\AbstractCreateUploadedDocumentsVerzoekAction;

/**
 * Create uploaded documents action.
 *
 * @since 1.0.0
 */
class CreateUploadedDocumentsVerzoekAction extends AbstractCreateUploadedDocumentsVerzoekAction
{
	protected array $entry;
	protected array $form;

	public function __construct(array $entry, array $form )
	{
		$this->entry   = $entry;
		$this->form    = $form;
	}

	public function add_uploaded_documents(): ?array
	{
		$mapped_args = $this->get_mapped_required_information_object_creation_args();

		if ( ! is_array( $mapped_args['informatieobject'] ?? false ) || array() === $mapped_args['informatieobject'] ) {
			return null; // No files mapped → not an error
		}

		$count   = count( $mapped_args['informatieobject'] );
		$success = 0;
		$uploaded_objects = array();

		foreach ( $mapped_args['informatieobject'] as $object ) {
			$args = $this->prepare_information_object_args(
				$object['url'],
				$object['type'],
				$object['description']
			);

			$connection_result = $this->connect_object_to_information_object(
				$this->create_information_object( $args )
			);

			if ( isset( $connection_result['uuid'] ) && '' !== $connection_result['uuid'] ) {
				++$success;
				$uploaded_objects[] = $connection_result;
			}
		}

		return [
			'result' => $count === $success, 'uploaded_objects' => $uploaded_objects,
			'uploaded_objects' => $uploaded_objects,
		];
	}
}
