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

use OWC\ZGW\Entities\Zaakinformatieobject;
use OWCGravityFormsZGW\Contracts\AbstractCreateUploadedDocumentsAction;
use Throwable;

/**
 * Create uploaded documents action.
 *
 * @since 1.0.0
 */
class CreateUploadedDocumentsAction extends AbstractCreateUploadedDocumentsAction
{
	public function add_uploaded_documents(): ?bool
	{
		$mapped_args = $this->get_mapped_required_information_object_creation_args();

		if ( ! is_array( $mapped_args['informatieobject'] ?? false ) || array() === $mapped_args['informatieobject'] ) {
			return null; // No files mapped → not an error
		}

		// Stops at the first failing upload, a retry recreates the whole zaak including all uploads.
		foreach ( $mapped_args['informatieobject'] as $object ) {
			if ( '' === (string) ( $object['type'] ?? '' ) ) {
				$this->set_upload_error( $object['url'], 'No document type (informatieobjecttype) is configured.' );

				return false;
			}

			try {
				$args = $this->prepare_information_object_args(
					$object['url'],
					$object['type'],
					$object['description'],
					$object['file_name'] ?? ''
				);

				if ( array() === $args ) {
					$this->set_upload_error( $object['url'], 'File could not be retrieved.' );

					return false;
				}

				$connection_result = $this->connect_zaak_to_information_object(
					$this->create_information_object( $args )
				);
			} catch ( Throwable $e ) {
				$this->set_upload_error( $object['url'], $e->getMessage() );

				throw $e; // Rethrown so callers can still handle the original (API) exception.
			}

			if ( ! $connection_result instanceof Zaakinformatieobject ) {
				$this->set_upload_error( $object['url'], 'Upload could not be connected to the zaak.' );

				return false;
			}
		}

		return true;
	}
}
