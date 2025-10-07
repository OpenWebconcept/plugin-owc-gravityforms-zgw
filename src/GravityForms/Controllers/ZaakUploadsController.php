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
use OWCGravityFormsZGW\Actions\CreateUploadedDocumentsAction;
use OWCGravityFormsZGW\Contracts\AbstractZaakFormController;
use OWC\ZGW\Entities\Zaak;

/**
 * Zaak uploads controller.
 *
 * @since 1.0.0
 */
class ZaakUploadsController extends AbstractZaakFormController
{
	public function handle(array $form ): array
	{
		$this->set_class_properties( $form );

		if ( ! $this->form_is_zgw()) {
			return $form;
		}

		try {
			if ( ! count( $this->entry )) {
				throw new Exception( $this->failed_messages['transient'], 400 );
			}

			$this->handle_zaak_uploads( $this->restore_serialized_zaak_from_transient( failed_message_type: 'transient', delete_transient: false ) );
		} catch (Exception $e) {
			// The value of this transient is used in the form confirmation message.
			set_transient( sprintf( '%s_%s', OWC_GRAVITYFORMS_ZGW_TRANSIENT_KEY_FAILED_SUBMISSION, md5( $this->entry['ip'] ) ), $e->getMessage(), 30 );
		}

		return $form;
	}

	protected function set_failed_messages_property(): array
	{
		return array(
			'transient' => __(
				'Het aanmaken van uw zaak is gelukt. Het toevoegen van de geüploade bestanden en de vereiste PDF van uw inzending konden helaas niet aan de zaak gekoppeld worden.',
				'owc-gravityforms-zgw'
			),
			'uploads'   => __(
				'Het aanmaken van uw zaak is gelukt. Het toevoegen van de geüploade bestanden aan uw zaak helaas niet.',
				'owc-gravityforms-zgw'
			),
		);
	}

	/**
	 * @throws Exception
	 */
	protected function handle_zaak_uploads( Zaak $zaak ): void
	{
		try {
			$action = ( new CreateUploadedDocumentsAction(
				$this->entry,
				$this->form,
				$this->supplier_name,
				$this->supplier_key,
				$zaak
			) );

			$result = $action->add_uploaded_documents();

			if ( false === $result) {
				throw new Exception(
					sprintf(
						'Something went wrong with connecting the uploads to zaak "%s"',
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

			throw new Exception( $this->failed_messages['uploads'], $e->getCode() );
		}
	}
}
