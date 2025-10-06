<?php
/**
 * Zaak uploads controller.
 *
 * This controller is responsible for handling form submissions before (pre) form submission
 * and delegates multiple actions towards the ZGW API.
 *
 * @package OWC_GravityForms_ZGW
 * @author  Yard | Digital Agency
 * @since   1.0.0
 */

namespace OWCGravityFormsZGW\GravityForms\Controllers;

/**
 * Exit when accessed directly.
 */
if ( ! defined( 'ABSPATH' )) {
	exit;
}

use Exception;
use GFFormsModel;
use OWCGravityFormsZGW\Actions\CreateSubmissionPDFAction;
use OWCGravityFormsZGW\Contracts\AbstractZaakFormController;
use OWC\ZGW\Entities\Zaak;
use OWC\ZGW\Entities\Zaakinformatieobject;

/**
 * Zaak uploads controller.
 *
 * @since 1.0.0
 */
class ZaakControllerSubmissionPDF extends AbstractZaakFormController
{
	public function handle(array $entry, array $form ): void
	{
		$this->set_class_properties( $form, $entry );

		if ( ! $this->form_is_zgw()) {
			return;
		}

		try {
			if ( ! count( $entry )) {
				throw new Exception( $this->failed_messages['transient'], 400 );
			}

			$zaak = $this->restore_serialized_zaak_from_transient( failed_message_type: 'transient', delete_transient: true );

			$this->handle_zaak_submission_pdf( $zaak );
		} catch (Exception $e) {
			GFFormsModel::add_note(
				$this->entry['id'],
				0,
				'OWC_GravityForms_ZGW',
				$e->getMessage()
			);
		}
	}
	protected function set_failed_messages_property(): array
	{
		return array(
			'transient'      => __(
				'Het aanmaken van uw zaak is gelukt. Het toevoegen van de geüploade bestanden en de vereiste PDF van uw inzending konden helaas niet aan de zaak gekoppeld worden.',
				'owc-gravityforms-zgw'
			),
			'submission_pdf' => __(
				'Het aanmaken van uw zaak is gelukt. De vereiste PDF van uw inzending kon helaas niet aan de zaak gekoppeld worden.',
				'owc-gravityforms-zgw'
			),
		);
	}

	/**
	 * @throws Exception
	 */
	protected function handle_zaak_submission_pdf( Zaak $zaak ): void
	{
		try {
			$action = ( new CreateSubmissionPDFAction(
				$this->entry,
				$this->form,
				$this->supplier_name,
				$this->supplier_key,
				$zaak
			) );

			$result = $action->add_submission_pdf();

			if ( ! $result instanceof Zaakinformatieobject) {
				throw new Exception(
					sprintf(
						'something went wrong with connecting the submission PDF to zaak "%s"',
						$zaak->getValue( 'identificatie', '' )
					),
					400
				);
			}
		} catch (Exception $e) {
			$this->logger->error(
				sprintf(
					'OWC_GravityForms_ZGW: %s',
					$e->getMessage()
				)
			);

			throw new Exception( $this->failed_messages['submission_pdf'], $e->getCode() );
		}
	}
}
