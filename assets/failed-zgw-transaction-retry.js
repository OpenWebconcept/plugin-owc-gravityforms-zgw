document.addEventListener('DOMContentLoaded', function () {
	// Event delegation handles buttons inserted via column updates.
	document.addEventListener('click', function (e) {
		const retryButton  = e.target.closest('.owc-gravityforms-zgw-btn-retry');
		const toggleButton = e.target.closest('.owc-btn-toggle-details');

		if (retryButton)  handleRetryClick.call(retryButton);
		if (toggleButton) handleToggleDetails.call(toggleButton);
	});
});

async function handleRetryClick() {
	const button  = this;
	const entryId = button.dataset.entryId;

	setLoadingState(button, true);
	clearRetryMessage(button);

	try {
		const formData = new FormData();
		formData.append('action', 'retry_submission');
		formData.append('entry_id', entryId);
		formData.append('_ajax_nonce', owcZGW.nonce);

		const response = await fetch(owcZGW.url, {
			method: 'POST',
			credentials: 'same-origin',
			body: formData
		});

		const result = await response.json();

		if (result.data?.columns) {
			updateColumns(button, result.data.columns);
		}

		if (result.success) {
			// Replace the button with the success message — retry is no longer needed.
			const successMsg     = document.createElement('span');
			successMsg.className = 'owc-retry-message owc-retry-message--success';
			successMsg.textContent = result.data?.message ?? 'Opnieuw uitvoeren gelukt.';
			button.replaceWith(successMsg);
		} else {
			showRetryMessage(button, result.data?.message ?? 'Onbekende fout.', 'error');
		}
	} catch (err) {
		showRetryMessage(button, 'Netwerkfout of server error.', 'error');
	} finally {
		// Restores button state on error. On success the button is already replaced — harmless.
		setLoadingState(button, false);
	}
}

function handleToggleDetails() {
	const details  = this.nextElementSibling;
	const isHidden = details.hasAttribute('hidden');

	details.toggleAttribute('hidden', !isHidden);
	this.textContent = isHidden ? this.dataset.labelHide : this.dataset.labelShow;
}

function updateColumns(button, columns) {
	const row = button.closest('tr');

	if (!row) return;

	Object.entries(columns).forEach(function ([column, html]) {
		const cell = row.querySelector('.column-' + column);
		if (cell) cell.innerHTML = html;
	});
}

function setLoadingState(button, loading) {
	button.disabled = loading;
	button.classList.toggle('is-loading', loading);
}

function clearRetryMessage(button) {
	button.parentNode.querySelector('.owc-retry-message')?.remove();
}

function showRetryMessage(button, text, type) {
	const div       = document.createElement('div');
	div.className   = 'owc-retry-message owc-retry-message--' + type;
	div.textContent = text;
	button.parentNode.appendChild(div);
}
