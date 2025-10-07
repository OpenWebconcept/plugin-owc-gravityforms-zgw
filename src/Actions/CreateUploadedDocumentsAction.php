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
use OWCGravityFormsZGW\Traits\CheckURL;

/**
 * Create uploaded documents action.
 *
 * @since 1.0.0
 */
class CreateUploadedDocumentsAction extends AbstractCreateUploadedDocumentsAction
{
	use CheckURL;

	public function add_uploaded_documents(): ?bool
	{
		$mapped_args = $this->get_mapped_required_information_object_creation_args();

		if (empty( $mapped_args['informatieobject'] )) {
			return null;
		}

		$count   = count( $mapped_args['informatieobject'] );
		$success = 0;

		foreach ($mapped_args['informatieobject'] as $object) {

			if (empty( $object['url']->tmp_url ) || empty( $object['type'] )) {
				continue;
			}

			if ( ! $this->check_url( $object['url']->tmp_url )) {
				continue;
			}

			$args              = $this->prepare_information_object_args( $object['url']->tmp_url, $object['type'], $object['description'] );
			$connection_result = $this->connect_zaak_to_information_object( $this->create_information_object( $args ) );

			if ($connection_result instanceof Zaakinformatieobject) {
				++$success;
			}
		}

		return $count === $success;
	}
}
