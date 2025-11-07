/**
 * This file needs a refactor by someone who knows JS better than me.
 * For now, it works.
 */
document.addEventListener('DOMContentLoaded', function () {
	document.querySelectorAll('.owc-gravityforms-zgw-btn-retry').forEach(button => {
		button.addEventListener('click', async function () {
			const ajaxUrl = owcZGW.url;
			const ajaxNonce = owcZGW.nonce;
			const entryId = this.dataset.entryId;
			const spinnerIcon = this.dataset.spinnerIcon;
			const img = this.querySelector('img');

			const originalSrc = img.src;
			img.src = spinnerIcon;
			this.disabled = true;

			try {
				const formData = new FormData();
				formData.append('action', 'retry_submission');
				formData.append('entry_id', entryId);
				formData.append('_ajax_nonce', ajaxNonce);

				const response = await fetch(ajaxUrl, {
					method: 'POST',
					credentials: 'same-origin',
					body: formData
				});

				const result = await response.json();

				const parentRow = this.closest('tr');
				if (parentRow) {
					const oldSuccess = parentRow.querySelector('#retry-success-message');
					const oldError = parentRow.querySelector('#retry-error-message');
					if (oldSuccess) oldSuccess.remove();
					if (oldError) oldError.remove();
				}

				if (result.success) {
					const successDiv = document.createElement('div');
					successDiv.id = 'retry-success-message';
					successDiv.style.color = 'green';
					successDiv.style.marginTop = '10px';
					successDiv.textContent = result.data.message ?? 'Opnieuw uitvoeren gelukt.';
					if (parentRow) {
						this.parentNode.appendChild(successDiv);
					}
				} else {
					const errorDiv = document.createElement('div');
					errorDiv.id = 'retry-error-message';
					errorDiv.style.color = 'red';
					errorDiv.style.marginTop = '10px';
					errorDiv.textContent = 'Fout bij opnieuw uitvoeren: ' + (result.data.message ?? 'Onbekende fout.');
					if (parentRow) {
						this.parentNode.appendChild(errorDiv);
					}
				}
			} catch (err) {
				const parentRow = this.closest('tr');
				if (parentRow) {
					const oldSuccess = parentRow.querySelector('#retry-success-message');
					const oldError = parentRow.querySelector('#retry-error-message');
					if (oldSuccess) oldSuccess.remove();
					if (oldError) oldError.remove();

					const errorDiv = document.createElement('div');
					errorDiv.id = 'retry-error-message';
					errorDiv.style.color = 'red';
					errorDiv.style.marginTop = '10px';
					errorDiv.textContent = 'Netwerkfout of server error.';
					this.parentNode.appendChild(errorDiv);
				}
			}

			img.src = originalSrc;
			this.disabled = false;
		});
	});
});
