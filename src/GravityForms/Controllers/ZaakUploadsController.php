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
	/**
	 * Init Zaak uploads and handle accordingly.
	 *
	 * @throws Exception
	 */
	public function handle( Zaak $zaak, string $supplier_name, string $supplier_key ): void
	{
		$this->handle_zaak_uploads( $zaak, $supplier_name, $supplier_key );
	}

	/**
	 * Add uploads to Zaak using the supplier-specific Action class.
	 *
	 * @throws Exception
	 */
	protected function handle_zaak_uploads( Zaak $zaak, string $supplier_name, string $supplier_key ): void
	{
		try {
			$action = ( new CreateUploadedDocumentsAction(
				$this->entry,
				$this->form,
				$supplier_name,
				$supplier_key,
				$zaak
			) );

			$action->add_uploaded_documents();
		} catch ( Exception $e ) {
			$this->logger->error(
				sprintf( 'OWC_GravityForms_ZGW: Error adding uploads to zaak. Error: %s', $e->getMessage() )
			);
			throw $e;
		}
	}
}
