<?php
/**
 * Create submission PDF action.
 *
 * @package OWC_GravityForms_ZGW
 * @author  Yard | Digital Agency
 * @since   1.0.0
 */

namespace OWCGravityFormsZGW\Clients\Procura\Actions;

/**
 * Exit when accessed directly.
 */
if ( ! defined( 'ABSPATH' )) {
	exit;
}

use OWCGravityFormsZGW\Contracts\AbstractCreateSubmissionPDFAction;
use OWC\ZGW\Entities\Zaakinformatieobject;

/**
 * Create submission PDF action.
 *
 * @since 1.0.0
 */
class CreateSubmissionPDFAction extends AbstractCreateSubmissionPDFAction
{
	/**
	 * @since 1.0.0
	 */
	public function add_submission_pdf(): ?Zaakinformatieobject
	{
		$args = $this->get_submission_args_pdf();

		if ( ! count( $args )) {
			return null;
		}

		return $this->connect_pdf_to_zaak( $this->create_submission_pdf( $args ) );
	}
}
