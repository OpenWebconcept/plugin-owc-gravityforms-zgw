<?php
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
if ( ! defined( 'ABSPATH' )) {
	exit;
}

use OWC\ZGW\Entities\Zaakinformatieobject;
use OWCGravityFormsZGW\Contracts\AbstractCreateUploadedDocumentsAction;

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

		if (empty( $mapped_args['informatieobject'] )) {
			return null; // No files mapped → not an error
		}

		$count   = count( $mapped_args['informatieobject'] );
		$success = 0;

		foreach ($mapped_args['informatieobject'] as $object) {
			$args              = $this->prepare_information_object_args(
				$object['url'],
				$object['type'],
				$object['description']
			);

			$connection_result = $this->connect_zaak_to_information_object(
                $this->create_information_object( $args )
            );

			if ($connection_result instanceof Zaakinformatieobject) {
				++$success;
			}
		}

		return $count === $success; // true = all succeeded, false = partial failure
	}
}
