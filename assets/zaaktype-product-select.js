document.addEventListener('DOMContentLoaded', function () {
	const zaaktypeSelect = document.querySelector('.owc-gf-zgw-zaaktype-select');
	const productSelect  = document.querySelector('.owc-gf-zgw-product-select');

	if (!zaaktypeSelect || !productSelect) return;

	let controller;

	zaaktypeSelect.addEventListener('change', function () {
		clearProductMessage();

		const zaaktype = zaaktypeSelect.value;

		if (!zaaktype) {
			resetProductSelect(owcZGWProductSelect.selectZaaktype);
			return;
		}

		fetchProducten(zaaktype);
	});

	async function fetchProducten(zaaktype) {
		controller?.abort();
		const thisController = controller = new AbortController();

		setLoadingState(true);

		// Derived from the field's own id ("...-form-setting-{supplier}-identifier") so it
		// always matches the supplier this zaaktype select belongs to, even if the (separate)
		// supplier dropdown was changed without saving yet.
		const supplier = zaaktypeSelect.id.replace(/^.*-form-setting-/, '').replace(/-identifier$/, '');

		try {
			const formData = new FormData();
			formData.append('action', 'owcgfzgw_get_producten_of_diensten');
			formData.append('supplier', supplier);
			formData.append('zaaktype', zaaktype);
			formData.append('_ajax_nonce', owcZGWProductSelect.nonce);

			const response = await fetch(owcZGWProductSelect.url, {
				method: 'POST',
				credentials: 'same-origin',
				body: formData,
				signal: thisController.signal
			});

			const result = await response.json();

			if (!result.success) {
				throw new Error(result.data?.message ?? owcZGWProductSelect.requestError);
			}

			populateProductSelect(result.data.products ?? []);
		} catch (err) {
			if (err.name === 'AbortError') return;
			resetProductSelect(owcZGWProductSelect.requestError);
			showProductMessage(err.message ?? owcZGWProductSelect.requestError);
		} finally {
			// A superseded (aborted) request's controller no longer matches the
			// active one, so it must not clear the loading state the newer request set.
			if (controller === thisController) {
				setLoadingState(false);
			}
		}
	}

	function populateProductSelect(products) {
		const placeholder = products.length ? owcZGWProductSelect.selectProduct : owcZGWProductSelect.noProducts;

		productSelect.innerHTML = '';
		productSelect.appendChild(createOption('', placeholder));
		products.forEach(function (product) {
			productSelect.appendChild(createOption(product, product));
		});

		productSelect.disabled = products.length === 0;
	}

	function resetProductSelect(placeholder) {
		productSelect.innerHTML = '';
		productSelect.appendChild(createOption('', placeholder));
		productSelect.disabled = true;
	}

	function createOption(value, label) {
		const option = document.createElement('option');
		option.value = value;
		option.textContent = label;
		return option;
	}

	function setLoadingState(loading) {
		productSelect.disabled = loading;
		productSelect.classList.toggle('is-loading', loading);
	}

	function showProductMessage(text) {
		const message       = document.createElement('div');
		message.className   = 'owc-zgw-product-message owc-zgw-product-message--error';
		message.textContent = text;
		productSelect.parentNode.appendChild(message);
	}

	function clearProductMessage() {
		productSelect.parentNode.querySelector('.owc-zgw-product-message')?.remove();
	}
});
