<?php
/**
 * Create a submission PDF action.
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
use OWCGravityFormsZGW\Contracts\AbstractCreateSubmissionPDFAction;

/**
 * Create a submission PDF action.
 *
 * @since 1.0.0
 */
class CreateSubmissionPDFAction extends AbstractCreateSubmissionPDFAction
{
	public function add_submission_pdf(): ?Zaakinformatieobject
	{
		$args = $this->get_submission_args_pdf();

		if ( ! count( $args ) ) {
			return null;
		}

		return $this->connect_pdf_to_zaak( $this->create_submission_pdf( $args ) );
	}
}
