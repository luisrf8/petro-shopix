
        var selectedItems = [];
        var totalAmount = 0;
        var subTotalAmount = 0;
        let payments = []; 
        let totalPaid = 0; 
        let scanCodeDebounceTimer = null;
        let scanCodeRequestInFlight = false;
        let qrScannerInstance = null;
        let qrScannerRunning = false;
        let qrScannerLock = false;
        let qrScannerStartInFlight = false;
        let qrScannerPermissionGranted = false;
        let qrScannerCameraId = null;
        const igtfTax = null;

        const baseCurrencyCode = null;
        const normalizedBaseCurrencyCode = String(baseCurrencyCode || 'USD').toUpperCase();
        const baseCurrencySymbol = null;
        const baseRateToBs = Number(null);
        const dollarRateToBs = Number(null);
        const euroRateToBs = Number(null);
        const tenantElectronicInvoicingEnabled = null;
        const tenantSpecialTaxpayer = null;
        const tenantDeliveryConfig = null;
        const existingCustomersForSale = null;
        
        const authUser = null;
        let selectedExistingCustomerId = Number(existingCustomersForSale?.[0]?.id || 0);
        let currentDeliveryFee = 0;

        function initializeExistingCustomerSelect() {
            const selectElement = document.getElementById('existingCustomerSelect');
            if (!selectElement || !window.jQuery || !window.jQuery.fn?.select2) {
                return;
            }

            const $select = window.jQuery(selectElement);
            if ($select.hasClass('select2-hidden-accessible')) {
                return;
            }

            $select.select2({
                width: '100%',
                placeholder: 'Busca por nombre, correo o telÃ©fono',
                allowClear: true,
            });

            $select.on('change', function () {
                selectedExistingCustomerId = Number(this.value || 0);
                if (!document.getElementById('step3').classList.contains('d-none')) {
                    renderSummary();
                }
            });
        }

        function appendFormDataValue(formData, key, value) {
            if (value === undefined || value === null) {
                return;
            }

            if (value instanceof File) {
                formData.append(key, value);
                return;
            }

            if (Array.isArray(value)) {
                value.forEach((item, index) => appendFormDataValue(formData, `${key}[${index}]`, item));
                return;
            }

            if (typeof value === 'object') {
                Object.entries(value).forEach(([childKey, childValue]) => {
                    appendFormDataValue(formData, `${key}[${childKey}]`, childValue);
                });
                return;
            }

            if (typeof value === 'boolean') {
                formData.append(key, value ? '1' : '0');
                return;
            }

            formData.append(key, String(value));
        }
        
        const materialPackages = null;
        let pendingPackageSelection = null;

        function calculateTaxRateFromTaxes(taxes) {
            return (taxes || []).reduce((sum, tax) => sum + (parseFloat(tax.rate) || 0), 0);
        }

        function isBolivarCurrencyCode(currencyCode) {
            const normalized = String(currencyCode || '').toUpperCase().trim();
            return ['BS', 'VES', 'VED', 'VEF', 'BOLIVAR', 'BOLIVARES'].includes(normalized);
        }

        function roundMoney(value) {
            const numeric = Number(value || 0);

            if (!Number.isFinite(numeric)) {
                return 0;
            }

            return Math.round((numeric + Number.EPSILON) * 100) / 100;
        }

        function convertAmountToBaseCurrency(amount, currency) {
            const value = Number(amount || 0);
            const sourceCurrency = String(currency || '').toUpperCase().trim();

            if (!Number.isFinite(value) || value <= 0) {
                return 0;
            }

            if (sourceCurrency === normalizedBaseCurrencyCode) {
                return roundMoney(value);
            }

            if (isBolivarCurrencyCode(sourceCurrency)) {
                return baseRateToBs > 0 ? roundMoney(value / baseRateToBs) : 0;
            }

            if (sourceCurrency === 'USD' && normalizedBaseCurrencyCode === 'EUR') {
                return (dollarRateToBs > 0 && euroRateToBs > 0) ? roundMoney((value * dollarRateToBs) / euroRateToBs) : 0;
            }

            if (sourceCurrency === 'EUR' && normalizedBaseCurrencyCode === 'USD') {
                return (euroRateToBs > 0 && dollarRateToBs > 0) ? roundMoney((value * euroRateToBs) / dollarRateToBs) : 0;
            }

            return roundMoney(value);
        }

        function resolvePaymentRateToBase(currencyCode) {
            const sourceCurrency = String(currencyCode || '').toUpperCase().trim();

            if (sourceCurrency === normalizedBaseCurrencyCode) {
                return 1;
            }

            if (isBolivarCurrencyCode(sourceCurrency)) {
                return baseRateToBs > 0 ? (1 / baseRateToBs) : 0;
            }

            if (sourceCurrency === 'USD' && normalizedBaseCurrencyCode === 'EUR') {
                return (dollarRateToBs > 0 && euroRateToBs > 0) ? (dollarRateToBs / euroRateToBs) : 0;
            }

            if (sourceCurrency === 'EUR' && normalizedBaseCurrencyCode === 'USD') {
                return (euroRateToBs > 0 && dollarRateToBs > 0) ? (euroRateToBs / dollarRateToBs) : 0;
            }

            return 1;
        }

        function getSelectableVariantsForComponent(component) {
            const selectableVariants = Array.isArray(component.selectable_variants) ? component.selectable_variants : [];
            if (selectableVariants.length > 0) {
                return selectableVariants;
            }

            return [{
                variant_id: Number(component.variant_id),
                variant_size: component.variant_size || '',
                variant_stock: Number(component.variant_stock || 0),
                variant_price: Number(component.variant_price || 0),
                product_name: component.product_name || 'Producto',
                taxes: Array.isArray(component.taxes) ? component.taxes : [],
            }];
        }

        function openPackageFlavorModal(packageId) {
            const pkg = materialPackages.find(p => Number(p.id) === Number(packageId));
            if (!pkg) {
                alert('No se encontrÃ³ el paquete seleccionado.');
                return;
            }

            const qtyInput = document.getElementById(`packageQty_${packageId}`);
            const packQty = Math.max(1, parseInt(qtyInput?.value || '1', 10));

            const hasFlexibleComponents = (pkg.items || []).some(component => String(component.selection_mode || 'variant') === 'product');
            if (!hasFlexibleComponents) {
                addFixedOnlyMaterialPackageToSale(pkg, packQty);
                return;
            }

            const components = (pkg.items || []).map((component, index) => {
                const requiredQty = (parseFloat(component.quantity) || 0) * packQty;
                const choices = getSelectableVariantsForComponent(component)
                    .filter(choice => Number(choice.variant_stock || 0) > 0)
                    .map(choice => ({
                        variant_id: Number(choice.variant_id),
                        variant_size: String(choice.variant_size || ''),
                        variant_stock: Number(choice.variant_stock || 0),
                        variant_price: Number(choice.variant_price || 0),
                        product_name: choice.product_name || component.product_name || 'Producto',
                        image_src: choice.image_src || component.image_src || null,
                        taxes: Array.isArray(choice.taxes) ? choice.taxes : [],
                        quantity: 0,
                    }));

                if (choices.length > 0 && requiredQty > 0) {
                    const preferredIndex = choices.findIndex(choice => Number(choice.variant_id) === Number(component.variant_id));
                    if (preferredIndex >= 0) {
                        choices[preferredIndex].quantity = requiredQty;
                    } else {
                        choices[0].quantity = requiredQty;
                    }
                }

                return {
                    component_id: `${pkg.id}_${index}`,
                    product_name: component.product_name || 'Producto',
                    required_qty: requiredQty,
                    choices,
                };
            }).filter(component => component.required_qty > 0 && component.choices.length > 0);

            if (components.length === 0) {
                alert('Este paquete no tiene variantes disponibles con stock.');
                return;
            }

            pendingPackageSelection = {
                package: pkg,
                packageQty: packQty,
                components,
            };

            renderPackageFlavorModal();
            const modalElement = document.getElementById('packageFlavorModal');
            bootstrap.Modal.getOrCreateInstance(modalElement).show();
        }

        function renderPackageFlavorModal() {
            if (!pendingPackageSelection) {
                return;
            }

            const summary = document.getElementById('packageFlavorSummary');
            const rows = document.getElementById('packageFlavorRows');
            if (!summary || !rows) {
                return;
            }

            const pkg = pendingPackageSelection.package;
            summary.innerHTML = `
                <div class="alert alert-light border mb-0">
                    <strong>${pkg.name}</strong><br>
                    Cantidad de paquetes: ${pendingPackageSelection.packageQty}
                </div>
            `;

            rows.innerHTML = '';
            pendingPackageSelection.components.forEach((component, componentIndex) => {
                const choicesHtml = component.choices.map((choice, choiceIndex) => `
                    <div class="row g-2 align-items-center mb-2">
                        <div class="col-12 col-md-6">
                            <div class="d-flex align-items-center gap-2">
                                <img src="${choice.image_src || '/assets/img/shopix5.png'}" alt="${choice.product_name || 'Producto'}" style="width:56px;height:56px;object-fit:cover;border-radius:12px;border:1px solid #e5e7eb;flex-shrink:0;" onerror="this.onerror=null;this.src='/assets/img/shopix5.png';">
                                <div>
                                    <small class="text-muted d-block">Variante</small>
                                    <strong>${choice.product_name} ${choice.variant_size || ''}</strong>
                                </div>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <small class="text-muted d-block">Stock</small>
                            <span>${choice.variant_stock}</span>
                        </div>
                        <div class="col-6 col-md-3">
                            <small class="text-muted d-block">Cantidad</small>
                            <input
                                type="number"
                                min="0"
                                max="${choice.variant_stock}"
                                step="0.01"
                                class="form-control form-control-sm"
                                value="${choice.quantity}"
                                data-package-component-index="${componentIndex}"
                                data-package-choice-index="${choiceIndex}"
                            >
                        </div>
                    </div>
                `).join('');

                rows.insertAdjacentHTML('beforeend', `
                    <div class="card border mb-2">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <h6 class="mb-0">${component.product_name}</h6>
                                <span class="badge bg-dark">Requerido: ${component.required_qty}</span>
                            </div>
                            ${choicesHtml}
                        </div>
                    </div>
                `);
            });
        }

        function confirmPackageFlavorSelection() {
            if (!pendingPackageSelection) {
                return;
            }

            const pkg = pendingPackageSelection.package;
            const packageDiscount = Math.max(0, Math.min(100, parseFloat(pkg.discount_percentage || 0)));
            const selectedRows = [];

            for (let componentIndex = 0; componentIndex < pendingPackageSelection.components.length; componentIndex += 1) {
                const component = pendingPackageSelection.components[componentIndex];

                for (let choiceIndex = 0; choiceIndex < component.choices.length; choiceIndex += 1) {
                    const choice = component.choices[choiceIndex];
                    const qtyInput = document.querySelector(`[data-package-component-index="${componentIndex}"][data-package-choice-index="${choiceIndex}"]`);
                    const qty = parseFloat(qtyInput?.value || '0') || 0;

                    if (qty <= 0) {
                        continue;
                    }

                    if (qty > Number(choice.variant_stock || 0)) {
                        alert(`La cantidad para ${choice.product_name} ${choice.variant_size || ''} supera el stock.`);
                        return;
                    }

                    selectedRows.push({
                        variant_id: Number(choice.variant_id),
                        qty,
                        stock: Number(choice.variant_stock || 0),
                        product_name: choice.product_name,
                        variant_size: choice.variant_size,
                        variant_price: Number(choice.variant_price || 0),
                        image_src: choice.image_src || null,
                        taxes: Array.isArray(choice.taxes) ? choice.taxes : [],
                    });
                }
            }

            if (selectedRows.length === 0) {
                alert('Debes seleccionar al menos una variante para continuar.');
                return;
            }

            const packageBaseTotal = selectedRows.reduce((sum, row) => {
                return sum + (row.variant_price * ((100 - packageDiscount) / 100) * row.qty);
            }, 0);

            const targetPackageTotal = (pkg.package_price !== null && pkg.package_price !== undefined)
                ? (Number(pkg.package_price) || 0)
                : packageBaseTotal;

            const priceScale = packageBaseTotal > 0 ? (targetPackageTotal / packageBaseTotal) : 1;
            const combinedLineMultiplier = ((100 - packageDiscount) / 100) * priceScale;
            const combinedLineDiscount = (1 - combinedLineMultiplier) * 100;

            for (const row of selectedRows) {
                const variantId = String(row.variant_id);
                const price = row.variant_price * combinedLineMultiplier;
                const taxRate = calculateTaxRateFromTaxes(row.taxes || []);
                const taxAmount = price * (taxRate / 100);
                const totalPrice = price + taxAmount;

                const existing = selectedItems.find(item => String(item.id) === variantId);
                if (existing) {
                    const nextQty = Number(existing.quantity || 0) + row.qty;
                    if (nextQty > Number(row.stock || existing.stock || 0)) {
                        alert(`Stock insuficiente para ${row.product_name} ${row.variant_size || ''}.`);
                        return;
                    }
                    existing.quantity = nextQty;
                } else {
                    selectedItems.push({
                        id: variantId,
                        productName: `${row.product_name} [${pkg.name}]`,
                        productSize: row.variant_size,
                        price,
                        stock: Number(row.stock || 0),
                        quantity: row.qty,
                        line_discount_percentage: combinedLineDiscount,
                        imageSrc: row.image_src || null,
                        taxes: row.taxes || [],
                        taxRate,
                        taxAmount,
                        totalPrice,
                    });
                }

                const checkbox = document.getElementById(`variant_${variantId}`);
                if (checkbox) {
                    checkbox.checked = true;
                }
            }

            recalcSubtotals();
            renderCart();
            bootstrap.Modal.getOrCreateInstance(document.getElementById('packageFlavorModal')).hide();
            alert(`Paquete "${pkg.name}" agregado al carrito.`);
            pendingPackageSelection = null;
        }

        function addMaterialPackageToSale(packageId) {
            openPackageFlavorModal(packageId);
        }

        function addFixedOnlyMaterialPackageToSale(pkg, packQty) {
            (pkg.items || []).forEach(component => {
                const variantId = String(component.variant_id);
                const componentQty = (parseFloat(component.quantity) || 0) * packQty;
                if (componentQty <= 0) {
                    return;
                }

                const taxes = component.taxes || [];
                const taxRate = calculateTaxRateFromTaxes(taxes);
                const packageDiscount = Math.max(0, Math.min(100, parseFloat(pkg.discount_percentage || 0)));
                const priceBeforePackageDiscount = parseFloat(component.variant_price) || 0;
                const baseLineMultiplier = ((100 - packageDiscount) / 100);

                const packageBaseTotal = (pkg.items || []).reduce((sum, row) => {
                    const rowQty = parseFloat(row.quantity) || 0;
                    const rowBasePrice = parseFloat(row.variant_price) || 0;
                    return sum + (rowBasePrice * ((100 - packageDiscount) / 100) * rowQty);
                }, 0);

                const targetPackageTotal = (pkg.package_price !== null && pkg.package_price !== undefined)
                    ? (parseFloat(pkg.package_price) || 0)
                    : packageBaseTotal;

                const priceScale = packageBaseTotal > 0 ? (targetPackageTotal / packageBaseTotal) : 1;
                const combinedLineMultiplier = baseLineMultiplier * priceScale;
                const price = priceBeforePackageDiscount * combinedLineMultiplier;
                const taxAmount = price * (taxRate / 100);
                const totalPrice = price + taxAmount;
                const combinedLineDiscount = (1 - combinedLineMultiplier) * 100;

                const existing = selectedItems.find(item => String(item.id) === variantId);
                if (existing) {
                    existing.quantity = Number(existing.quantity || 0) + componentQty;
                } else {
                    selectedItems.push({
                        id: variantId,
                        productName: `${component.product_name} [${pkg.name}]`,
                        productSize: component.variant_size,
                        price,
                        stock: Number(component.variant_stock || 999999),
                        quantity: componentQty,
                        line_discount_percentage: combinedLineDiscount,
                        imageSrc: component.image_src || null,
                        taxes,
                        taxRate,
                        taxAmount,
                        totalPrice,
                    });
                }

                const checkbox = document.getElementById(`variant_${variantId}`);
                if (checkbox) {
                    checkbox.checked = true;
                }
            });

            recalcSubtotals();
            renderCart();
            alert(`Paquete "${pkg.name}" agregado al carrito.`);
        }

        document.addEventListener('DOMContentLoaded', function () {
            // Escuchar todos los checkboxes
            const checkboxes = document.querySelectorAll('input[name="selectedVariants[]"]');
            initializeExistingCustomerSelect();
            checkboxes.forEach(checkbox => {
                checkbox.addEventListener('change', handleCheckboxChange);
            });

            const variantRows = document.querySelectorAll('.variant-row');
            variantRows.forEach(row => {
                row.addEventListener('click', function (event) {
                    if (event.target.closest('.material-symbols-rounded') || event.target.closest('a') || event.target.closest('button')) {
                        return;
                    }

                    const checkbox = row.querySelector('.variant-checkbox');
                    if (!checkbox) {
                        return;
                    }

                    if (event.target !== checkbox) {
                        checkbox.click();
                    }
                });
            });

            const paymentMethodRows = document.querySelectorAll('.payment-method-row');
            paymentMethodRows.forEach(row => {
                row.addEventListener('click', function (event) {
                    if (event.target.closest('.payment-method-fields, img, a, button, input, select, textarea, label')) {
                        return;
                    }

                    const checkbox = row.querySelector('.payment-method-checkbox');
                    if (!checkbox || event.target === checkbox || event.target.closest('.payment-method-checkbox')) {
                        return;
                    }

                    checkbox.click();
                });
            });

            const cartElement = document.getElementById('cart');
            if (cartElement) {
                cartElement.addEventListener('hidden.bs.offcanvas', function () {
                    document.body.classList.remove('modal-open');
                    document.body.style.removeProperty('overflow');
                    document.body.style.removeProperty('padding-right');
                    document.querySelectorAll('.offcanvas-backdrop').forEach(el => el.remove());
                });
            }
        });

        function closeCartOffcanvas(forceCleanup = false) {
            const cartElement = document.getElementById('cart');
            if (!cartElement) return;

            const isMobileOffcanvas = window.innerWidth < 1200;

            if (isMobileOffcanvas) {
                const offcanvas = bootstrap.Offcanvas.getInstance(cartElement);
                if (offcanvas) {
                    offcanvas.hide();
                }
            }

            if (forceCleanup) {
                setTimeout(() => {
                    document.body.classList.remove('modal-open');
                    document.body.style.removeProperty('overflow');
                    document.body.style.removeProperty('padding-right');
                    document.querySelectorAll('.offcanvas-backdrop').forEach(el => el.remove());
                }, 120);
            }
        }
        function handleCheckboxChange(e) {
            const checkbox = e.target;
            const id = checkbox.value;
            const productName = checkbox.getAttribute('data-product-name');
            const productSize = checkbox.getAttribute('data-size');
            const stock = parseInt(checkbox.getAttribute('data-stock')) || 0;
            const price = parseFloat(checkbox.getAttribute('data-price')) || 0;
            const taxesString = checkbox.getAttribute('data-taxes');
            const imageSrc = checkbox.getAttribute('data-image-src') || '';
            const selectedQty = Math.max(1, parseInt(checkbox.dataset.selectedQty || '1', 10) || 1);
            delete checkbox.dataset.selectedQty;

            // Convertir a JSON
            const taxes = taxesString ? JSON.parse(taxesString) : [];

            // Sumar tasas de impuestos
            const totalTaxRate = taxes.reduce((sum, tax) => sum + parseFloat(tax.rate), 0);

            // CÃ¡lculo del impuesto
            const taxAmount = price * (totalTaxRate / 100);

            // Precio final = precio base + impuestos
            const totalPrice = price + taxAmount;

            console.log(`Tasas acumuladas (${id}): ${totalTaxRate}%`);
            console.log(`Precio base: ${price} => Total con impuestos: ${totalPrice}`);

            if (checkbox.checked) {
                selectedItems.push({
                    id,
                    productName,
                    productSize,
                    price,
                    stock,
                    quantity: selectedQty,
                    line_discount_percentage: 0,
                    imageSrc,
                    taxes,
                    taxRate: totalTaxRate,
                    taxAmount,
                    totalPrice // <--- Guardar precio con impuestos
                });
            } else {
                selectedItems = selectedItems.filter(item => item.id !== id);
            }

            recalcSubtotals();
            renderCart();
        }

        function getAdminDeliveryModeLabel(mode, distanceKm = null) {
            if (mode === 'distance') {
                return distanceKm && distanceKm > 0
                    ? `Delivery por km (${distanceKm.toFixed(2)} km)`
                    : 'Delivery por km';
            }

            if (mode === 'fixed') {
                return 'Delivery con tarifa fija';
            }

            if (mode === 'free') {
                return 'Delivery gratis';
            }

            return 'Retiro en sede';
        }

        function getAdminDeliveryChargeContext(strict = false) {
            const selectedType = document.querySelector('input[name="delivery_type"]:checked')?.value || 'pickup';
            const distanceInput = document.getElementById('deliveryDistanceKm');
            const distanceValue = Number(distanceInput?.value || 0);

            if (selectedType !== 'shipping') {
                return {
                    valid: true,
                    fee: 0,
                    mode: 'pickup',
                    distanceKm: null,
                    label: 'Retiro en sede',
                };
            }

            if (!tenantDeliveryConfig?.enabled) {
                return {
                    valid: true,
                    fee: 0,
                    mode: 'free',
                    distanceKm: null,
                    label: 'Delivery gratis',
                };
            }

            const normalizedMode = tenantDeliveryConfig.mode || 'free';
            if (normalizedMode === 'fixed') {
                return {
                    valid: true,
                    fee: Number(tenantDeliveryConfig.fixed_fee || 0),
                    mode: normalizedMode,
                    distanceKm: null,
                    label: getAdminDeliveryModeLabel(normalizedMode),
                };
            }

            if (normalizedMode === 'distance') {
                if (distanceValue <= 0) {
                    return {
                        valid: !strict,
                        fee: 0,
                        mode: normalizedMode,
                        distanceKm: null,
                        label: getAdminDeliveryModeLabel(normalizedMode),
                        message: 'Debes indicar la distancia estimada del delivery en kilÃ³metros.',
                    };
                }

                return {
                    valid: true,
                    fee: Number(tenantDeliveryConfig.fee_per_km || 0) * distanceValue,
                    mode: normalizedMode,
                    distanceKm: distanceValue,
                    label: getAdminDeliveryModeLabel(normalizedMode, distanceValue),
                };
            }

            return {
                valid: true,
                fee: 0,
                mode: 'free',
                distanceKm: null,
                label: getAdminDeliveryModeLabel('free'),
            };
        }

function updateQuantity(id, newQty) {
    const item = selectedItems.find(item => item.id === id);
    if (!item) return;

    newQty = parseInt(newQty) || 1;
    if (newQty < 1) newQty = 1;
    if (newQty > item.stock) newQty = item.stock;

    // Restar el subtotal y los impuestos anteriores
    subTotalAmount -= (item.price * item.quantity);

    // Actualizar cantidad
    item.quantity = newQty;

    // Sumar precio base nuevo
    subTotalAmount += (item.price * item.quantity);

    renderCart();
}


        function renderCart() {
            const cartList = document.getElementById('cartList');
            const cartTotal = document.getElementById('cartTotal');
            const cartSubTotal = document.getElementById('cartSubTotal');
            const adminCartCount = document.getElementById('adminCartCount');
            const cartTotalBs = document.getElementById('cartTotalBs');
            const cartSubTotalBs = document.getElementById('cartSubTotalBs');
            const cartTotalIGTF = document.getElementById('cartTotalIGTF');
            const cartDeliveryFee = document.getElementById('cartDeliveryFee');
            const cartDeliveryMode = document.getElementById('cartDeliveryMode');
            const totalAmountValue = document.getElementById('totalAmountValue');
            const totalAmountBsValue = document.getElementById('totalAmountBsValue');
            const toStep2Btn = document.getElementById('toStep2');

            cartList.innerHTML = '';

            selectedItems.forEach(item => {
                const li = document.createElement('li');
                li.className = 'list-group-item d-flex justify-content-between align-items-start flex-column';

                const textDiv = document.createElement('div');
                textDiv.className = 'd-flex gap-2 w-100';
                textDiv.innerHTML = `
                    <img src="${item.imageSrc || '/assets/img/shopix5.png'}" alt="${item.productName}" style="width:64px;height:64px;object-fit:cover;border-radius:12px;border:1px solid #e5e7eb;flex-shrink:0;" onerror="this.onerror=null;this.src='/assets/img/shopix5.png';">
                    <div>
                        <strong>${item.productName} ${item.productSize}</strong><br>
                        Subtotal: ${(item.price * item.quantity).toFixed(2)} ${baseCurrencyCode}
                        <br>
                        Impuestos:<br>
                        ${item.taxes.map(tax => `â€¢ ${tax.name} (${parseFloat(tax.rate)}%)`).join('<br>')}
                        <br>
                        <strong>Total con Impuestos: ${(item.totalPrice * item.quantity).toFixed(2)} ${baseCurrencyCode}</strong>
                    </div>
                `;


                const controlsDiv = document.createElement('div');
                controlsDiv.className = 'd-flex align-items-center justify-content-between w-100 mt-2';

                // Div para cantidad y el input con gap-2
                const quantityDiv = document.createElement('div');
                quantityDiv.className = 'd-flex align-items-center gap-2';

                const quantityLabel = document.createElement('label');
                quantityLabel.className = 'd-flex align-items-center mt-2';
                quantityLabel.textContent = 'Cantidad: ';
                // quantityLabel.className = 'me-2';

                const quantityInput = document.createElement('input');
                quantityInput.type = 'number';
                quantityInput.min = '1';
                quantityInput.max = item.stock;
                quantityInput.value = item.quantity;
                quantityInput.className = 'form-control qty-edit';
                quantityInput.style.width = '80px';
                quantityInput.style.height = 'fit-content';
                quantityInput.style.padding = '0.25rem 0.5rem';
                quantityInput.style.border = '1px solid #ced4da';
                quantityInput.oninput = () => updateQuantity(item.id, parseInt(quantityInput.value));

                quantityDiv.appendChild(quantityLabel);
                quantityDiv.appendChild(quantityInput);

                const removeBtn = document.createElement('button');
                removeBtn.className = 'btn btn-sm btn-danger mt-3 delete-button';
                removeBtn.innerText = 'X';
                removeBtn.onclick = () => removeFromCart(item.id);

                controlsDiv.appendChild(quantityDiv); // Agregar el div de cantidad e input
                controlsDiv.appendChild(removeBtn);  // BotÃ³n de eliminar al extremo derecho

                li.appendChild(textDiv);
                li.appendChild(controlsDiv);
                cartList.appendChild(li);
            });

            const cartTotalQty = selectedItems.reduce((sum, item) => sum + Number(item.quantity || 0), 0);
            if (adminCartCount) {
                adminCartCount.textContent = cartTotalQty;
            }
            const taxesContainer = document.getElementById('taxesContainer');

            let totalItemsWithTaxes = selectedItems.reduce((acc, item) => {
                return acc + (item.totalPrice * item.quantity);
            }, 0);

            const deliveryContext = getAdminDeliveryChargeContext(false);
            currentDeliveryFee = Number(deliveryContext.fee || 0);
            const totalWithoutIgtf = roundMoney(totalItemsWithTaxes + currentDeliveryFee);
            const { totalBaseCurrency, cappedBase, tax } = calculateIgtfTax(totalWithoutIgtf);

            taxesContainer.innerHTML = `
                <div class="mt-3 igtf-class" style="display: none;">
                    <strong>Base IGTF (${baseCurrencyCode}):</strong> ${baseCurrencySymbol}${cappedBase.toFixed(2)}
                </div>
                <div class="mt-1 text-danger igtf-class" style="display: none;">
                    <strong>Impuesto IGTF:</strong> ${baseCurrencySymbol}${tax.toFixed(2)}
                </div>
            `;

            if(isIgtfEnabledForSale() && totalBaseCurrency > 0) {
                document.querySelectorAll('.igtf-class').forEach(el => el.style.display = 'block');
            } else {
                document.querySelectorAll('.igtf-class').forEach(el => el.style.display = 'none');
            }

            totalAmount = totalItemsWithTaxes + currentDeliveryFee + tax;
            totalSinIGTF = totalItemsWithTaxes + currentDeliveryFee;
            console.log("Total sin IGTF:", totalSinIGTF);
            cartTotal.textContent = totalAmount.toFixed(2); 
            cartSubTotal.textContent = subTotalAmount.toFixed(2);
            cartTotalBs.textContent = (totalAmount * baseRateToBs ).toFixed(2); 
            cartSubTotalBs.textContent = (subTotalAmount * baseRateToBs ).toFixed(2);
            cartTotalIGTF.textContent = totalSinIGTF.toFixed(2);
            if (cartDeliveryFee) {
                cartDeliveryFee.textContent = currentDeliveryFee.toFixed(2);
            }
            if (cartDeliveryMode) {
                cartDeliveryMode.textContent = deliveryContext.label || 'Retiro en sede';
            }
            totalAmountValue.textContent = totalAmount.toFixed(2); // AsegÃºrate de mostrar un nÃºmero vÃ¡lido
            totalAmountBsValue.textContent = (totalAmount * baseRateToBs ).toFixed(2); // AsegÃºrate de mostrar un nÃºmero vÃ¡lido
            toStep2Btn.disabled = selectedItems.length === 0;
        }

        function removeFromCart(id) {
            const item = selectedItems.find(item => item.id === id);
            if (item) {
                totalAmount -= item.price * item.quantity;
                selectedItems = selectedItems.filter(i => i.id !== id);
            }

            const checkbox = document.getElementById(`variant_${id}`);
            if (checkbox) checkbox.checked = false;
            
            recalcSubtotals();
            renderCart();
        }
        function recalcSubtotals() {
            subTotalAmount = selectedItems.reduce((acc, item) => {
                return acc + (item.price * item.quantity);
            }, 0);

            totalAmount = selectedItems.reduce((acc, item) => {
                return acc + (item.totalPrice * item.quantity);
            }, 0);
        }

        let activeCategory = 'all';

        function filterProductsByCategory(categoryId) {
            activeCategory = String(categoryId || 'all').trim();
            const productItems = document.querySelectorAll('.product-item');
            const categoryButtons = document.querySelectorAll('.category-filter.sale-category-pill');

            categoryButtons.forEach(button => button.classList.remove('is-active'));
            const activeCategoryItem = document.querySelector(`.category-item[data-category="${activeCategory}"] .category-filter.sale-category-pill`);
            if (activeCategoryItem) {
                activeCategoryItem.classList.add('is-active');
            }

            const isAll = activeCategory === 'all';
            const isPackages = activeCategory === 'packages';

            productItems.forEach(item => {
                const itemCategory = String(item.getAttribute('data-category') || '').trim();
                let shouldShow = false;

                if (itemCategory === 'packages') {
                    const packageCategoriesRaw = String(item.getAttribute('data-package-categories') || '').trim();
                    const packageCategories = packageCategoriesRaw === ''
                        ? []
                        : packageCategoriesRaw.split(',').map(v => v.trim()).filter(Boolean);

                    shouldShow = isAll || isPackages || (!isPackages && packageCategories.includes(activeCategory));
                } else {
                    shouldShow = isAll || (!isPackages && itemCategory === activeCategory);
                }

                item.classList.toggle('d-none', !shouldShow);
            });

            // Limpiar el campo de bÃºsqueda de productos al cambiar de categorÃ­a
            const searchInput = document.getElementById('searchInput');
            if (searchInput) {
                searchInput.value = '';
            }
        }

        function toggleProductVariantPanel(productId) {
            const card = document.getElementById(`productCard_${productId}`);
            if (!card) {
                return;
            }

            card.classList.toggle('is-expanded');
        }

        function addVariantFromProductCard(variantId, qtyInputId) {
            const checkbox = document.getElementById(`variant_${variantId}`);
            const qtyInput = document.getElementById(qtyInputId);
            const qty = Math.max(1, parseInt(qtyInput?.value || '1', 10) || 1);

            if (!checkbox) {
                return;
            }

            if (checkbox.checked) {
                const existing = selectedItems.find(item => String(item.id) === String(variantId));
                if (existing) {
                    existing.quantity = Number(existing.quantity || 0) + qty;
                    recalcSubtotals();
                    renderCart();
                }
                return;
            }

            checkbox.dataset.selectedQty = String(qty);
            checkbox.checked = true;
            checkbox.dispatchEvent(new Event('change'));
        }
        
        function filterProducts() {
            const searchInput = document.getElementById('searchInput');
            const filter = String(searchInput?.value || '').toLowerCase().trim();
            const productItems = document.querySelectorAll('.product-item');

            const isAll = activeCategory === 'all';
            const isPackages = activeCategory === 'packages';

            productItems.forEach(item => {
                const name = String(item.getAttribute('data-name') || '');
                const searchableText = String(item.getAttribute('data-search') || name).toLowerCase();
                const itemCategory = String(item.getAttribute('data-category') || '').trim();
                let matchCategory = false;

                if (itemCategory === 'packages') {
                    const packageCategoriesRaw = String(item.getAttribute('data-package-categories') || '').trim();
                    const packageCategories = packageCategoriesRaw === ''
                        ? []
                        : packageCategoriesRaw.split(',').map(v => v.trim()).filter(Boolean);
                    matchCategory = isAll || isPackages || (!isPackages && packageCategories.includes(activeCategory));
                } else {
                    matchCategory = isAll || (!isPackages && itemCategory === activeCategory);
                }

                const shouldShow = matchCategory && searchableText.includes(filter);

                item.classList.toggle('d-none', !shouldShow);
            });
        }

        function showProductDetails(name, description, imageUrl, price, stock, size) {
            // Rellenar los datos del modal
            document.getElementById('modalProductName').textContent = name;
            document.getElementById('modalProductDescription').textContent = description;
            document.getElementById('modalProductPrice').textContent = price;
            document.getElementById('modalProductStock').textContent = stock;
            document.getElementById('modalProductSize').textContent = size;

            const productImage = document.getElementById('modalProductImage');
            if (imageUrl) {
                productImage.src = imageUrl;
                productImage.style.display = 'block';
            } else {
                productImage.style.display = 'none';
            }

            // Mostrar el modal
            const modal = new bootstrap.Modal(document.getElementById('productDetailModal'));
            modal.show();
        }
        function showQrModal(imageUrl) {
            const qrModalImage = document.getElementById('qrModalImage');
            qrModalImage.src = imageUrl; // Establecer la imagen en el modal
            const qrModal = new bootstrap.Modal(document.getElementById('qrModal'));
            qrModal.show(); // Mostrar el modal
        }

        async function stopQrScanner() {
            if (!qrScannerInstance || !qrScannerRunning) {
                return;
            }

            try {
                await qrScannerInstance.stop();
            } catch (error) {
            }

            qrScannerRunning = false;
        }

        function getPreferredRearCameraId(cameras) {
            if (!Array.isArray(cameras) || cameras.length === 0) {
                return null;
            }

            const rearCamera = cameras.find(camera => {
                const label = String(camera.label || '').toLowerCase();
                return label.includes('back') || label.includes('rear') || label.includes('trasera') || label.includes('posterior');
            });

            return rearCamera?.id || cameras[cameras.length - 1]?.id || null;
        }

        async function ensureCameraPermission() {
            if (qrScannerPermissionGranted) {
                return true;
            }

            if (!navigator.mediaDevices || typeof navigator.mediaDevices.getUserMedia !== 'function') {
                alert('Tu navegador no soporta acceso a cÃ¡mara en este contexto.');
                return false;
            }

            try {
                const stream = await navigator.mediaDevices.getUserMedia({ video: true, audio: false });
                stream.getTracks().forEach(track => track.stop());
                qrScannerPermissionGranted = true;
                return true;
            } catch (error) {
                alert('No se pudo acceder a la cÃ¡mara. Revisa permisos del navegador y vuelve a intentar.');
                return false;
            }
        }

        async function resolveQrCameraId() {
            if (qrScannerCameraId) {
                return qrScannerCameraId;
            }

            try {
                const cameras = await Html5Qrcode.getCameras();
                qrScannerCameraId = getPreferredRearCameraId(cameras);
            } catch (error) {
                qrScannerCameraId = null;
            }

            return qrScannerCameraId;
        }

        function cleanupModalVisualState() {
            const hasVisibleModal = document.querySelectorAll('.modal.show').length > 0;
            if (hasVisibleModal) {
                return;
            }

            document.body.classList.remove('modal-open');
            document.body.style.removeProperty('overflow');
            document.body.style.removeProperty('padding-right');
            document.querySelectorAll('.modal-backdrop').forEach(el => el.remove());
        }

        async function startQrScanner() {
            if (qrScannerStartInFlight) {
                return;
            }

            const readerElement = document.getElementById('qrScannerReader');
            if (!readerElement) {
                return;
            }

            if (typeof Html5Qrcode === 'undefined') {
                alert('No se pudo cargar el escÃ¡ner QR. Intenta recargar la pÃ¡gina.');
                return;
            }

            if (!qrScannerInstance) {
                qrScannerInstance = new Html5Qrcode('qrScannerReader');
            }

            if (qrScannerRunning) {
                return;
            }

            qrScannerStartInFlight = true;
            qrScannerLock = false;

            try {
                const hasPermission = await ensureCameraPermission();
                if (!hasPermission) {
                    return;
                }

                const cameraId = await resolveQrCameraId();
                const cameraConfig = cameraId || { facingMode: { ideal: 'environment' } };

                await qrScannerInstance.start(
                    cameraConfig,
                    { fps: 10, qrbox: { width: 240, height: 240 } },
                    async (decodedText) => {
                        if (qrScannerLock) {
                            return;
                        }

                        qrScannerLock = true;
                        const input = document.getElementById('scanCodeInput');
                        if (input) {
                            input.value = String(decodedText || '').trim();
                        }

                        await addByScanCode();
                        const modalEl = document.getElementById('scanQrModal');
                        const modalInstance = bootstrap.Modal.getInstance(modalEl);
                        modalInstance?.hide();
                    },
                    () => {
                    }
                );

                qrScannerRunning = true;
            } catch (error) {
                alert('No se pudo iniciar la cÃ¡mara para escanear. Verifica permisos del navegador.');
            } finally {
                qrScannerStartInFlight = false;
            }
        }

        async function addByScanCode() {
            if (scanCodeRequestInFlight) {
                return;
            }

            const input = document.getElementById('scanCodeInput');
            const code = String(input?.value || '').trim();
            if (!code) {
                return;
            }

            scanCodeRequestInFlight = true;

            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            try {
                const response = await fetch('/sales/scan-code', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ code }),
                });

                const payload = await response.json();
                if (!response.ok || !payload.success) {
                    alert(payload.message || 'CÃ³digo no encontrado.');
                    return;
                }

                if (payload.type === 'package') {
                    addMaterialPackageToSale(payload.package_id);
                    input.value = '';
                    return;
                }

                if (payload.type === 'variant' && payload.variant) {
                    const variant = payload.variant;
                    const checkbox = document.getElementById(`variant_${variant.id}`);
                    if (checkbox) {
                        if (!checkbox.checked) {
                            checkbox.checked = true;
                            checkbox.dispatchEvent(new Event('change'));
                        } else {
                            const existing = selectedItems.find(item => String(item.id) === String(variant.id));
                            if (existing) {
                                existing.quantity = Number(existing.quantity || 0) + 1;
                                recalcSubtotals();
                                renderCart();
                            }
                        }
                        input.value = '';
                        return;
                    }

                    const taxes = variant.taxes || [];
                    const totalTaxRate = taxes.reduce((sum, tax) => sum + parseFloat(tax.rate || 0), 0);
                    const price = parseFloat(variant.price || 0);
                    const taxAmount = price * (totalTaxRate / 100);
                    const totalPrice = price + taxAmount;

                    const existing = selectedItems.find(item => String(item.id) === String(variant.id));
                    if (existing) {
                        existing.quantity = Number(existing.quantity || 0) + 1;
                    } else {
                        selectedItems.push({
                            id: String(variant.id),
                            productName: variant.product_name,
                            productSize: variant.size,
                            price,
                            stock: parseFloat(variant.stock || 0),
                            quantity: 1,
                            line_discount_percentage: 0,
                            taxes,
                            taxRate: totalTaxRate,
                            taxAmount,
                            totalPrice,
                        });
                    }

                    recalcSubtotals();
                    renderCart();
                    input.value = '';
                }
            } finally {
                scanCodeRequestInFlight = false;
            }
        }
        function filterCategories() {
            const searchValue = document.getElementById('searchCategory').value.toLowerCase();
            const categories = document.querySelectorAll('.category-item');

            categories.forEach(category => {
                const name = category.getAttribute('data-category-name')?.toLowerCase() || '';
                if (name.includes(searchValue) || category.getAttribute('data-category') === 'all') {
                    category.style.display = 'block';
                } else {
                    category.style.display = 'none';
                }
            });
        }

        document.getElementById('scanCodeBtn')?.addEventListener('click', addByScanCode);
        document.getElementById('confirmPackageFlavorBtn')?.addEventListener('click', confirmPackageFlavorSelection);
        document.getElementById('packageFlavorModal')?.addEventListener('hidden.bs.modal', () => {
            pendingPackageSelection = null;
            const rows = document.getElementById('packageFlavorRows');
            const summary = document.getElementById('packageFlavorSummary');
            if (rows) rows.innerHTML = '';
            if (summary) summary.innerHTML = '';
        });
        document.getElementById('openQrScannerBtn')?.addEventListener('click', () => {
            // Warm up permission on a direct user gesture to avoid flaky prompts on mobile.
            ensureCameraPermission();
        });
        document.getElementById('scanQrModal')?.addEventListener('shown.bs.modal', startQrScanner);
        document.getElementById('scanQrModal')?.addEventListener('hidden.bs.modal', async () => {
            await stopQrScanner();

            // On some mobile browsers the backdrop can remain mounted after quick close.
            setTimeout(() => {
                cleanupModalVisualState();
            }, 60);
        });
        document.getElementById('scanCodeInput')?.addEventListener('keydown', function (event) {
            if (event.key === 'Enter') {
                event.preventDefault();
                if (scanCodeDebounceTimer) {
                    clearTimeout(scanCodeDebounceTimer);
                    scanCodeDebounceTimer = null;
                }
                addByScanCode();
            }
        });
        document.getElementById('scanCodeInput')?.addEventListener('input', function () {
            if (scanCodeDebounceTimer) {
                clearTimeout(scanCodeDebounceTimer);
            }

            const currentValue = String(this.value || '').trim();
            if (currentValue.length < 3) {
                return;
            }

            scanCodeDebounceTimer = setTimeout(() => {
                addByScanCode();
            }, 160);
        });
        document.getElementById('scanCodeInput')?.addEventListener('paste', function () {
            if (scanCodeDebounceTimer) {
                clearTimeout(scanCodeDebounceTimer);
            }

            scanCodeDebounceTimer = setTimeout(() => {
                addByScanCode();
            }, 30);
        });

        function syncSaleStatusSelectAll() {
            const checks = Array.from(document.querySelectorAll('.sale-status-check'));
            const selectAll = document.getElementById('saleStatusSelectAll');
            if (!selectAll || checks.length === 0) {
                return;
            }

            selectAll.checked = checks.every(check => check.checked);
        }

        document.getElementById('saleStatusSelectAll')?.addEventListener('change', function () {
            document.querySelectorAll('.sale-status-check').forEach(check => {
                check.checked = this.checked;
            });
            renderSummary();
        });

        document.querySelectorAll('.sale-status-check').forEach(check => {
            check.addEventListener('change', function () {
                syncSaleStatusSelectAll();
                renderSummary();
            });
        });

        syncSaleStatusSelectAll();
        setSaleFlowStep(1);
        document.getElementById('toStep2').addEventListener('click', function() {
            document.getElementById('step1').classList.add('d-none');
            document.getElementById('step2').classList.remove('d-none');
            setSaleFlowStep(2);

            // Deshabilitar inputs y ocultar botones de eliminar
            document.querySelectorAll('.qty-edit').forEach(input => {
                input.disabled = true;
            });

            document.getElementById('toStep2').classList.add('d-none');

            document.querySelectorAll('.delete-button').forEach(btn => {
                btn.classList.add('d-none');
            });

            closeCartOffcanvas(true);
        });

        document.getElementById('backToStep1').addEventListener('click', function() {
            document.getElementById('step2').classList.add('d-none');
            document.getElementById('step1').classList.remove('d-none');
            setSaleFlowStep(1);
            document.getElementById('toStep2').classList.remove('d-none');

            // Habilitar inputs y mostrar botones de eliminar
            document.querySelectorAll('.qty-edit').forEach(input => {
                input.disabled = false;
            });

            document.querySelectorAll('.delete-button').forEach(btn => {
                btn.classList.remove('d-none');
            });
        });


        
        document.addEventListener('DOMContentLoaded', function () {
            const currencyButtons = document.querySelectorAll('.currency-tab');
            const sections = document.querySelectorAll('.currency-section');

            currencyButtons.forEach(btn => {
                btn.addEventListener('click', () => {
                    const selectedCurrency = btn.dataset.currency;
                    console.log("Moneda seleccionada:", selectedCurrency);
                    // Ocultar todas las secciones
                    sections.forEach(section => {
                        section.classList.add('d-none');
                    });

                    // Mostrar solo la secciÃ³n de la moneda seleccionada
                    document.querySelector(`.currency-section[data-currency="${selectedCurrency}"]`)?.classList.remove('d-none');

                    // Resaltar botÃ³n activo
                    currencyButtons.forEach(b => b.classList.remove('btn-dark'));
                    currencyButtons.forEach(b => b.classList.add('btn-outline-dark'));
                    btn.classList.remove('btn-outline-dark');
                    btn.classList.add('btn-dark');
                });
            });
            
        });
        
        function generatePaymentEntryId() {
            return `${Date.now()}_${Math.floor(Math.random() * 1000000)}`;
        }

        function createPaymentRowHtml(methodId, currency, noReference, entryId) {
            const referenceInput = noReference
                ? `<input type="hidden" class="payment-reference-input" data-method-id="${methodId}" data-entry-id="${entryId}" data-requires-reference="0" value="">`
                : `<input type="text" class="form-control payment-reference-input border border-1 p-2" data-method-id="${methodId}" data-entry-id="${entryId}" data-requires-reference="1" placeholder="Referencia" oninput="updatePayment(this)">`;

            const proofInput = noReference
                ? `<div class="small text-muted py-2">No requiere comprobante para este mÃ©todo.</div>`
                : `<input type="file" class="form-control border border-1 p-2 payment-proof-input" accept="image/*" data-method-id="${methodId}" data-entry-id="${entryId}" onchange="updatePaymentProof(this)">`;

            return `
                <div class="border rounded-3 p-2 bg-white" data-payment-entry-row="${entryId}">
                    <div class="d-flex flex-column flex-lg-row gap-2 align-items-lg-center">
                        <div class="flex-grow-1">
                            <label class="form-label mb-1">Monto</label>
                            <input type="text" inputmode="decimal" autocomplete="off" class="form-control payment-input border border-1 p-2"
                                data-method-id="${methodId}"
                                data-entry-id="${entryId}"
                                data-currency="${currency}"
                                oninput="updatePayment(this)">
                        </div>
                        <div class="flex-grow-1">
                            <label class="form-label mb-1">Referencia</label>
                            ${referenceInput}
                        </div>
                        <div class="flex-grow-1">
                            <label class="form-label mb-1">Comprobante</label>
                            ${proofInput}
                        </div>
                        <div class="align-self-end">
                            <button type="button" class="btn btn-outline-danger btn-sm" onclick="removePaymentEntry('${methodId}', '${entryId}')">X</button>
                        </div>
                    </div>
                </div>
            `;
        }

        function addPaymentEntry(methodId, currency, noReference = false, methodName = '', appliesIgtf = false) {
            const rowsContainer = document.getElementById(`paymentRows_${methodId}`);
            if (!rowsContainer) return;

            const entryId = generatePaymentEntryId();
            rowsContainer.insertAdjacentHTML('beforeend', createPaymentRowHtml(methodId, currency, noReference, entryId));

            payments.push({
                entryId,
                methodId: String(methodId),
                methodName: methodName || `MÃ©todo ${methodId}`,
                currency,
                amount: 0,
                amountBase: 0,
                amountOriginal: 0,
                exchangeRateToBase: 0,
                appliesIgtf: Boolean(appliesIgtf),
                reference: '',
                hasProofImage: false,
                requiresReference: !noReference,
            });

            validatePaymentDetails();
        }

        function parseAmountInputValue(value) {
            const normalized = normalizeEditableAmountValue(value).numeric;
            const parsed = Number.parseFloat(normalized);
            return Number.isFinite(parsed) ? parsed : 0;
        }

        function normalizeEditableAmountValue(value) {
            const source = String(value || '')
                .replace(/\s+/g, '')
                .replace(/[^\d.,]/g, '');

            if (!source) {
                return { text: '', numeric: '' };
            }

            const lastDot = source.lastIndexOf('.');
            const lastComma = source.lastIndexOf(',');
            let decimalIndex = -1;
            let decimalSeparator = '';

            if (lastDot !== -1 && lastComma !== -1) {
                decimalIndex = Math.max(lastDot, lastComma);
                decimalSeparator = source[decimalIndex];
            } else if (lastComma !== -1) {
                const fraction = source.slice(lastComma + 1).replace(/[^\d]/g, '');
                if (fraction.length <= 2) {
                    decimalIndex = lastComma;
                    decimalSeparator = ',';
                }
            } else if (lastDot !== -1) {
                const fraction = source.slice(lastDot + 1).replace(/[^\d]/g, '');
                if (fraction.length <= 2 || source.endsWith('.')) {
                    decimalIndex = lastDot;
                    decimalSeparator = '.';
                }
            }

            let integerPart = '';
            let decimalPart = '';
            let hasTrailingDecimal = false;

            if (decimalIndex !== -1) {
                integerPart = source.slice(0, decimalIndex).replace(/[^\d]/g, '');
                decimalPart = source.slice(decimalIndex + 1).replace(/[^\d]/g, '').slice(0, 2);
                hasTrailingDecimal = source.endsWith(decimalSeparator) && decimalPart.length === 0;
            } else {
                integerPart = source.replace(/[^\d]/g, '');
            }

            integerPart = integerPart.replace(/^0+(?=\d)/, '');

            if (!integerPart && (decimalPart || hasTrailingDecimal)) {
                integerPart = '0';
            }

            const text = decimalIndex !== -1
                ? `${integerPart || '0'}${(decimalPart || hasTrailingDecimal) ? '.' : ''}${decimalPart}`
                : integerPart;

            const numeric = decimalIndex !== -1
                ? `${integerPart || '0'}${decimalPart ? `.${decimalPart}` : ''}`
                : integerPart;

            return { text, numeric };
        }

        function formatAmountDisplay(value) {
            const numeric = Number(value || 0);
            return new Intl.NumberFormat('en-US', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2,
            }).format(Number.isFinite(numeric) ? numeric : 0);
        }

        function syncFormattedPaymentInput(input, applyFormatting = false) {
            if (!input || !input.classList.contains('payment-input')) {
                return 0;
            }

            const numericValue = parseAmountInputValue(input.value);
            input.dataset.rawValue = String(numericValue);

            if (applyFormatting) {
                input.value = numericValue > 0 ? formatAmountDisplay(numericValue) : '';
            }

            return numericValue;
        }

        function sanitizeLiveAdminMoneyInput(input) {
            if (!input || !input.classList.contains('payment-input')) {
                return 0;
            }

            const selectionStart = input.selectionStart ?? String(input.value || '').length;
            const beforeCursor = String(input.value || '').slice(0, selectionStart);
            const normalizedValue = normalizeEditableAmountValue(input.value);
            const normalizedBeforeCursor = normalizeEditableAmountValue(beforeCursor);

            if (!normalizedValue.text) {
                input.dataset.rawValue = '0';
                input.value = '';
                return 0;
            }

            const numericValue = parseAmountInputValue(normalizedValue.text);
            input.dataset.rawValue = String(numericValue);

            if (input.value !== normalizedValue.text) {
                input.value = normalizedValue.text;
                const nextCaret = normalizedBeforeCursor.text.length;
                requestAnimationFrame(() => {
                    try {
                        input.setSelectionRange(nextCaret, nextCaret);
                    } catch (error) {
                    }
                });
            }

            return numericValue;
        }

        function removePaymentEntry(methodId, entryId) {
            const row = document.querySelector(`[data-payment-entry-row="${entryId}"]`);
            if (row) row.remove();

            payments = payments.filter(payment => payment.entryId !== entryId);

            const hasEntriesForMethod = payments.some(payment => payment.methodId === String(methodId));
            if (!hasEntriesForMethod) {
                const checkbox = document.getElementById(`method_${methodId}`);
                const paymentFields = document.getElementById(`paymentFields_${methodId}`);
                const rowsContainer = document.getElementById(`paymentRows_${methodId}`);
                if (rowsContainer) rowsContainer.innerHTML = '';
                if (checkbox) checkbox.checked = false;
                if (paymentFields) paymentFields.classList.add('d-none');
            }

            renderCart();
            validatePaymentDetails();
        }

        function togglePaymentFields(checkbox) {
            const methodId = checkbox.dataset.methodId;
            const methodName = checkbox.dataset.methodName || `MÃ©todo ${methodId}`;
            const paymentFields = document.getElementById(`paymentFields_${methodId}`);
            const currency = paymentFields?.dataset.currency || checkbox.dataset.currency;
            const noReference = paymentFields?.dataset.noReference === '1';
            const appliesIgtf = checkbox.dataset.appliesIgtf === '1';

            if (checkbox.checked) {
                paymentFields.classList.remove('d-none');
                const hasEntriesForMethod = payments.some(payment => payment.methodId === String(methodId));
                if (!hasEntriesForMethod) {
                    addPaymentEntry(methodId, currency, noReference, methodName, appliesIgtf);
                }
            } else {
                const rowsContainer = document.getElementById(`paymentRows_${methodId}`);
                if (rowsContainer) rowsContainer.innerHTML = '';
                paymentFields.classList.add('d-none');
                payments = payments.filter(payment => payment.methodId !== String(methodId));
            }

            console.log("payments", payments);
            validatePaymentDetails();
        }

        function updatePayment(input) {
            const methodId = input.dataset.methodId;
            const entryId = input.dataset.entryId;
            const currency = input.dataset.currency;
            const payment = payments.find(payment => payment.methodId === String(methodId) && payment.entryId === entryId);

            if (payment) {
                if (input.classList.contains('payment-input')) {
                    const enteredAmountOriginal = sanitizeLiveAdminMoneyInput(input);
                    const amountBase = convertAmountToBaseCurrency(enteredAmountOriginal, currency);
                    payment.amountOriginal = roundMoney(enteredAmountOriginal);
                    payment.amountBase = roundMoney(amountBase);
                    payment.exchangeRateToBase = roundMoney(resolvePaymentRateToBase(currency));
                    payment.amount = payment.amountBase;
                } else if (input.classList.contains('payment-reference-input')) {
                    payment.reference = input.value;
                }
            }
            console.log("payments 2", payments);
            renderCart();
            validatePaymentDetails();
        }

        function updatePaymentProof(input) {
            const methodId = input.dataset.methodId;
            const entryId = input.dataset.entryId;
            const payment = payments.find(payment => payment.methodId === String(methodId) && payment.entryId === entryId);

            if (payment) {
                payment.hasProofImage = Boolean(input.files?.[0]);
            }
        }

        function getBaseCurrencyPaymentsTotal() {
            let totalBaseCurrency = 0;

            payments.forEach(p => {
                if (Boolean(p.appliesIgtf)) {
                    totalBaseCurrency += Number(p.amountBase ?? p.amount) || 0;
                }
            });

            return roundMoney(totalBaseCurrency);
        }

        function isIgtfEnabledForSale() {
            return tenantElectronicInvoicingEnabled && !tenantSpecialTaxpayer && Number(igtfTax?.rate || 0) > 0;
        }

        function calculateIgtfTax(totalWithoutIgtf = 0) {
            const totalBaseCurrency = getBaseCurrencyPaymentsTotal();
            if (!isIgtfEnabledForSale()) {
                return {
                    totalBaseCurrency,
                    cappedBase: 0,
                    tax: 0,
                };
            }
            const cappedBase = roundMoney(Math.min(totalBaseCurrency, Math.max(Number(totalWithoutIgtf || 0), 0)));
            const igtfRate = Number(igtfTax?.rate || 0) / 100;
            const tax = roundMoney(cappedBase * igtfRate);
            return {
                totalBaseCurrency,
                cappedBase,
                tax
            };
        }

        let deliveryCountriesLoaded = false;

        function getSelectedOptionText(selectElement) {
            const option = selectElement?.options?.[selectElement.selectedIndex];
            return option ? String(option.text || '').trim() : '';
        }

        function resetLocationSelect(selectElement, placeholder, disabled = true) {
            if (!selectElement) return;
            selectElement.innerHTML = `<option value="">${placeholder}</option>`;
            selectElement.disabled = disabled;
        }

        function fillLocationSelect(selectElement, items, placeholder) {
            if (!selectElement) return;
            selectElement.innerHTML = `<option value="">${placeholder}</option>`;
            (Array.isArray(items) ? items : []).forEach(item => {
                const option = document.createElement('option');
                option.value = String(item.id);
                option.textContent = item.name;
                selectElement.appendChild(option);
            });
            selectElement.disabled = !items || items.length === 0;
        }

        async function fetchLocationJson(url) {
            const response = await fetch(url, {
                headers: { Accept: 'application/json' },
            });

            if (!response.ok) {
                throw new Error('No se pudo cargar informaciÃ³n de ubicaciÃ³n.');
            }

            return response.json();
        }

        async function ensureDeliveryCountriesLoaded() {
            const countrySelect = document.getElementById('deliveryCountry');
            if (!countrySelect || deliveryCountriesLoaded) {
                return;
            }

            const countries = await fetchLocationJson('/get-countries');
            fillLocationSelect(countrySelect, countries, 'PaÃ­s');
            deliveryCountriesLoaded = true;
        }

        function setSaleFlowStep(step) {
            document.querySelectorAll('[data-sale-step]').forEach((item) => {
                const itemStep = Number(item.dataset.saleStep || 0);
                item.classList.toggle('is-active', itemStep === step);
                item.classList.toggle('is-complete', itemStep < step);
            });
        }

        function buildDeliveryAddress() {
            const countrySelect = document.getElementById('deliveryCountry');
            const stateSelect = document.getElementById('deliveryState');
            const citySelect = document.getElementById('deliveryCity');
            const addressDetailInput = document.getElementById('deliveryAddressDetail');

            const countryId = countrySelect?.value || '';
            const stateId = stateSelect?.value || '';
            const cityId = citySelect?.value || '';

            if (!countryId || !stateId || !cityId) {
                return {
                    valid: false,
                    message: 'Debes seleccionar paÃ­s, estado y ciudad para el envÃ­o.',
                    address: '',
                };
            }

            const parts = [
                getSelectedOptionText(countrySelect),
                getSelectedOptionText(stateSelect),
                getSelectedOptionText(citySelect),
                (addressDetailInput?.value || '').trim(),
            ].filter(Boolean);

            return {
                valid: true,
                message: '',
                address: parts.join(', '),
                cityId: Number(cityId),
            };
        }

        function renderSummary() {
            const container = document.getElementById('summaryContainer');
            const deliveryType = document.querySelector('input[name="delivery_type"]:checked')?.value || 'pickup';
            const deliveryAddressData = buildDeliveryAddress();
            const deliveryContext = getAdminDeliveryChargeContext(false);
            const deliveryPreferenceLabel = deliveryType === 'shipping' ? 'EnvÃ­o' : 'Retiro en sede';
            const saleDocumentMode = document.querySelector('input[name="sale_document_mode"]:checked')?.value || 'delivery_note';
            const saleDocumentModeLabel = saleDocumentMode === 'electronic_invoice' ? 'FacturaciÃ³n digital' : 'Orden de entrega';
            const shouldCreateNewCustomer = document.querySelector('input[name="create_new_customer"]:checked')?.value === 'yes';
            const existingCustomerSelect = document.getElementById('existingCustomerSelect');
            const selectedCustomerOption = existingCustomerSelect?.options?.[existingCustomerSelect.selectedIndex] || null;
            const selectedCustomerLabel = (selectedCustomerOption?.textContent || '').trim();
            const newCustomerName = (document.getElementById('newCustomerName')?.value || '').trim();
            const newCustomerEmail = (document.getElementById('newCustomerEmail')?.value || '').trim();
            const newCustomerPhone = (document.getElementById('newCustomerPhone')?.value || '').trim();
            const newCustomerDni = (document.getElementById('newCustomerDni')?.value || '').trim();
            
            container.innerHTML = ''; // Limpiar resumen anterior

            // Resumen de items
            const itemsTitle = document.createElement('h5');
            itemsTitle.innerText = 'Productos seleccionados';
            container.appendChild(itemsTitle);

            const itemList = document.createElement('ul');
            selectedItems.forEach(item => {
                const li = document.createElement('li');
                li.innerText = `${item.productName} - Variante: ${item.productSize} - Cantidad: ${item.quantity} - Subtotal: ${baseCurrencySymbol}${(item.price * item.quantity).toFixed(2)}`;
                itemList.appendChild(li);
            });
            container.appendChild(itemList);
            itemList.className = 'card p-4 gap-2';

            // Total
            const totalDiv = document.createElement('p');
            totalDiv.innerHTML = `<strong>Total a pagar:</strong> ${baseCurrencySymbol}${totalAmount.toFixed(2)}`;
            container.appendChild(totalDiv);

            // Total BS
            console.log("baseRateToBs", baseRateToBs)
            const totalDivBs = document.createElement('p');
            totalDivBs.innerHTML = `<strong>Total a pagar Bs:</strong> Bs${(totalAmount * baseRateToBs).toFixed(2)}`;
            container.appendChild(totalDivBs);

            const deliveryFeeDiv = document.createElement('p');
            deliveryFeeDiv.innerHTML = `<strong>Costo delivery:</strong> ${baseCurrencySymbol}${Number(deliveryContext.fee || 0).toFixed(2)} <span class="text-muted">(${deliveryContext.label || 'Retiro en sede'})</span>`;
            container.appendChild(deliveryFeeDiv);

            const deliveryDiv = document.createElement('p');
            deliveryDiv.innerHTML = `<strong>Entrega:</strong> ${deliveryPreferenceLabel}`;
            container.appendChild(deliveryDiv);

            const addressDiv = document.createElement('p');
            addressDiv.innerHTML = `<strong>DirecciÃ³n:</strong> ${deliveryType === 'shipping' ? (deliveryAddressData.address || 'No indicada') : 'sede'}`;
            container.appendChild(addressDiv);

            const customerDiv = document.createElement('p');
            customerDiv.innerHTML = `<strong>Cliente:</strong> ${shouldCreateNewCustomer ? 'Nuevo cliente' : (selectedCustomerLabel || 'Cliente existente no seleccionado')}`;
            container.appendChild(customerDiv);

            const documentModeDiv = document.createElement('p');
            documentModeDiv.innerHTML = `<strong>Documento:</strong> ${saleDocumentModeLabel}`;
            container.appendChild(documentModeDiv);

            if (shouldCreateNewCustomer) {
                const customerDataDiv = document.createElement('p');
                customerDataDiv.innerHTML = `
                    <strong>Datos cliente nuevo:</strong><br>
                    Nombre: ${newCustomerName || 'No indicado'}<br>
                    Correo: ${newCustomerEmail || 'No indicado'}<br>
                    TelÃ©fono: ${newCustomerPhone || 'No indicado'}<br>
                    DNI: ${newCustomerDni || 'No indicado'}
                `;
                container.appendChild(customerDataDiv);
            }

            const statusDiv = document.createElement('div');
            const markSaleCompleted = document.getElementById('markSaleCompleted')?.checked;
            const markPaymentsPaid = document.getElementById('markPaymentsPaid')?.checked;
            const markDelivered = document.getElementById('markDelivered')?.checked;
            statusDiv.innerHTML = `
                <strong>Estado inicial:</strong>
                <ul class="mb-3 mt-2">
                    <li>Venta completa: ${markSaleCompleted ? 'SÃ­' : 'No'}</li>
                    <li>Pagos pagados: ${markPaymentsPaid ? 'SÃ­' : 'No'}</li>
                    <li>Entregada: ${markDelivered ? 'SÃ­' : 'No'}</li>
                </ul>
            `;
            container.appendChild(statusDiv);

            // Resumen de mÃ©todos de pago
            const paymentsTitle = document.createElement('h5');
            paymentsTitle.innerText = 'MÃ©todos de pago';
            container.appendChild(paymentsTitle);

            if (payments.length === 0) {
                const noPayment = document.createElement('p');
                noPayment.innerText = 'No se ha seleccionado ningÃºn mÃ©todo de pago.';
                container.appendChild(noPayment);
            } else {
                const paymentList = document.createElement('ul');
                payments.forEach(payment => {
                    const amount = Number(payment.amount || 0);
                    const reference = payment.reference || 'N/A';

                    if (amount <= 0) {
                        return;
                    }

                    const li = document.createElement('li');
                    li.innerText = `${payment.methodName} (${payment.currency}) - Monto: ${baseCurrencySymbol}${amount.toFixed(2)} - Referencia: ${reference}`;
                    paymentList.appendChild(li);
                });
                if (paymentList.children.length > 0) {
                    container.appendChild(paymentList);
                    paymentList.className = 'card p-4 gap-2';
                } else {
                    const noPaymentAmount = document.createElement('p');
                    noPaymentAmount.innerText = 'No hay montos cargados en los mÃ©todos seleccionados.';
                    container.appendChild(noPaymentAmount);
                }
            }
        }

        function addPayments() {
            const paymentsContainer = document.getElementById('paymentsContainer');
            paymentsContainer.innerHTML = ''; // Limpiar contenido previo

            payments.forEach(payment => {
                const amount = Number(payment.amount || 0);
                if (amount <= 0) {
                    return;
                }

                const paymentDiv = document.createElement('div');
                paymentDiv.className = 'payment-method-summary';
                const reference = payment.reference || 'N/A';

                paymentDiv.innerHTML = `
                    <strong>MÃ©todo:</strong> ${payment.methodName} (${payment.currency})<br>
                    <strong>Monto:</strong> ${baseCurrencySymbol}${amount.toFixed(2)} <br>
                    <strong>Referencia:</strong> ${reference} <br>
                    <hr>
                `;

                paymentsContainer.appendChild(paymentDiv);
            });
        }

        function validatePaymentDetails() {
            totalPaid = roundMoney(payments.reduce((sum, payment) => sum + (Number(payment.amountBase ?? payment.amount) || 0), 0));
            console.log("Total pagado:", payments);
            const totalPaidSpan = document.getElementById('totalPaid');
            const paymentMessages = document.querySelectorAll('.paymentMessage');
            const toStep3Button = document.getElementById('toStep3');
            const changeControls = document.getElementById('changeSettlementControls');
            const changeBsPreview = document.getElementById('changeAmountBsPreview');

            // Mostrar el total ingresado
            totalPaidSpan.textContent = totalPaid.toFixed(2);

            let messageText = '';
            let messageClass = '';
            let disableStep3 = false;

            // Verificar si hay referencias vacÃ­as (solo si el mÃ©todo requiere referencia y monto > 0)
            const hasEmptyReference = payments.some(payment => {
                const amount = Number(payment.amountBase ?? payment.amount) || 0;
                if (amount <= 0) return false;

                const inputReference = document.querySelector(`.payment-reference-input[data-entry-id="${payment.entryId}"]`);
                const requiresReference = inputReference ? inputReference.dataset.requiresReference === '1' : true;

                if (!requiresReference) return false;
                return !payment.reference || payment.reference.trim() === '';
            });

            if (hasEmptyReference) {
                messageText = `Todos los mÃ©todos de pago deben tener una referencia vÃ¡lida.`;
                messageClass = 'text-danger';
                disableStep3 = true;
                if (changeControls) {
                    changeControls.classList.add('d-none');
                }
            } else if (totalPaid + 0.0001 < roundMoney(totalAmount)) {
                const remaining = roundMoney(totalAmount - totalPaid).toFixed(2);
                messageText = `Falta por pagar: ${baseCurrencySymbol}${remaining} / BS${(remaining * baseRateToBs).toFixed(2)}`;
                messageClass = 'text-danger';
                disableStep3 = true;
                if (changeControls) {
                    changeControls.classList.add('d-none');
                }
            } else if (totalPaid - 0.0001 > roundMoney(totalAmount)) {
                const change = roundMoney(totalPaid - totalAmount).toFixed(2);
                messageText = `Debe entregar vuelto: ${baseCurrencySymbol}${change} / BS${(change * baseRateToBs).toFixed(2)}`;
                messageClass = 'text-warning';
                disableStep3 = false;

                if (changeControls) {
                    changeControls.classList.remove('d-none');
                }
                if (changeBsPreview) {
                    changeBsPreview.textContent = (roundMoney(totalPaid - totalAmount) * baseRateToBs).toFixed(2);
                }
            } else {
                messageText = `Pago exacto.`;
                messageClass = 'text-success';
                disableStep3 = false;

                if (changeControls) {
                    changeControls.classList.add('d-none');
                }
                if (changeBsPreview) {
                    changeBsPreview.textContent = '0.00';
                }
            }

            // Actualizar todos los mensajes en pantalla
            paymentMessages.forEach(el => {
                el.textContent = messageText;
                el.className = `paymentMessage ${messageClass}`; // Mantener la clase base mÃ¡s el color
            });

            toStep3Button.disabled = disableStep3;
        }

        document.addEventListener('focusin', function (event) {
            const input = event.target.closest('.payment-input');
            if (!input) {
                return;
            }

            const normalizedValue = normalizeEditableAmountValue(input.value).text;
            if (normalizedValue && input.value !== normalizedValue) {
                input.value = normalizedValue;
            }
        });

        document.addEventListener('focusout', function (event) {
            const input = event.target.closest('.payment-input');
            if (!input) {
                return;
            }

            syncFormattedPaymentInput(input, true);
        });


        //Funciones para paso 3
        document.getElementById('toStep3').addEventListener('click', function() {
            closeCartOffcanvas(true);
            document.getElementById('step2').classList.add('d-none');
            renderSummary(); // Mostrar el resumen
            document.getElementById('cart').classList.add('d-none');
            document.getElementById('step3').classList.remove('d-none');
            setSaleFlowStep(3);
            document.getElementById('openAdminCartBtn')?.classList.add('d-none');
            console.log('Resumen:', selectedItems);
            console.log('Pagos:', payments);
        });
        document.getElementById('backToStep2').addEventListener('click', function() {
            document.getElementById('step3').classList.add('d-none');
            document.getElementById('step2').classList.remove('d-none');
            setSaleFlowStep(2);
            document.getElementById('cart').classList.remove('d-none');
            document.getElementById('openAdminCartBtn')?.classList.remove('d-none');

        });

        function updateDeliveryAddressVisibility() {
            const selectedType = document.querySelector('input[name="delivery_type"]:checked')?.value || 'pickup';
            const addressContainer = document.getElementById('deliveryAddressContainer');
            const distanceContainer = document.getElementById('deliveryDistanceContainer');
            const distanceInput = document.getElementById('deliveryDistanceKm');
            const countrySelect = document.getElementById('deliveryCountry');
            const stateSelect = document.getElementById('deliveryState');
            const citySelect = document.getElementById('deliveryCity');
            const addressDetailInput = document.getElementById('deliveryAddressDetail');

            if (selectedType === 'shipping') {
                addressContainer.classList.remove('d-none');
                const shouldShowDistance = tenantDeliveryConfig?.enabled && (tenantDeliveryConfig.mode === 'distance');
                distanceContainer?.classList.toggle('d-none', !shouldShowDistance);
                ensureDeliveryCountriesLoaded().catch(() => {
                    alert('No se pudieron cargar los paÃ­ses para el envÃ­o.');
                });
            } else {
                addressContainer.classList.add('d-none');
                distanceContainer?.classList.add('d-none');
                if (countrySelect) countrySelect.value = '';
                if (stateSelect) resetLocationSelect(stateSelect, 'Estado (parte del paÃ­s)', true);
                if (citySelect) resetLocationSelect(citySelect, 'Ciudad', true);
                if (addressDetailInput) addressDetailInput.value = '';
                if (distanceInput) distanceInput.value = '';
            }

            renderCart();

            if (!document.getElementById('step3').classList.contains('d-none')) {
                renderSummary();
            }
        }

        function updateCreateCustomerVisibility() {
            const shouldCreateNewCustomer = document.querySelector('input[name="create_new_customer"]:checked')?.value === 'yes';
            const newCustomerForm = document.getElementById('newCustomerForm');
            const existingCustomerForm = document.getElementById('existingCustomerForm');
            if (!newCustomerForm || !existingCustomerForm) return;

            newCustomerForm.classList.toggle('d-none', !shouldCreateNewCustomer);
            existingCustomerForm.classList.toggle('d-none', shouldCreateNewCustomer);

            if (!shouldCreateNewCustomer) {
                const existingCustomerSelect = document.getElementById('existingCustomerSelect');
                selectedExistingCustomerId = Number(existingCustomerSelect?.value || 0);
            }

            if (!document.getElementById('step3').classList.contains('d-none')) {
                renderSummary();
            }
        }

        document.querySelectorAll('input[name="delivery_type"]').forEach(input => {
            input.addEventListener('change', updateDeliveryAddressVisibility);
        });

        document.querySelectorAll('input[name="sale_document_mode"]').forEach(input => {
            input.addEventListener('change', function () {
                if (!document.getElementById('step3').classList.contains('d-none')) {
                    renderSummary();
                }
            });
        });

        document.getElementById('deliveryAddressDetail').addEventListener('input', function () {
            if (!document.getElementById('step3').classList.contains('d-none')) {
                renderSummary();
            }
        });

        document.getElementById('deliveryDistanceKm')?.addEventListener('input', function () {
            renderCart();
            if (!document.getElementById('step3').classList.contains('d-none')) {
                renderSummary();
            }
        });

        document.getElementById('deliveryCountry')?.addEventListener('change', async function () {
            const stateSelect = document.getElementById('deliveryState');
            const citySelect = document.getElementById('deliveryCity');
            const countryId = this.value;

            resetLocationSelect(stateSelect, 'Estado (parte del paÃ­s)', true);
            resetLocationSelect(citySelect, 'Ciudad', true);

            if (!countryId) {
                renderSummary();
                return;
            }

            try {
                const states = await fetchLocationJson(`/get-states/${countryId}`);
                fillLocationSelect(stateSelect, states, 'Estado (parte del paÃ­s)');
            } catch (error) {
                alert('No se pudieron cargar los estados del paÃ­s seleccionado.');
            }

            renderSummary();
        });

        document.getElementById('deliveryState')?.addEventListener('change', async function () {
            const citySelect = document.getElementById('deliveryCity');
            const stateId = this.value;

            resetLocationSelect(citySelect, 'Ciudad', true);

            if (!stateId) {
                renderSummary();
                return;
            }

            try {
                const cities = await fetchLocationJson(`/get-cities/${stateId}`);
                fillLocationSelect(citySelect, cities, 'Ciudad');
            } catch (error) {
                alert('No se pudieron cargar las ciudades del estado seleccionado.');
            }

            renderSummary();
        });

        document.getElementById('deliveryCity')?.addEventListener('change', function () {
            renderSummary();
        });

        document.querySelectorAll('input[name="create_new_customer"]').forEach(input => {
            input.addEventListener('change', updateCreateCustomerVisibility);
        });

        ['newCustomerName', 'newCustomerEmail', 'newCustomerPhone', 'newCustomerDni'].forEach(fieldId => {
            document.getElementById(fieldId)?.addEventListener('input', function () {
                if (!document.getElementById('step3').classList.contains('d-none')) {
                    renderSummary();
                }
            });
        });

        if (document.getElementById('existingCustomerSelect') && existingCustomersForSale.length > 0) {
            const existingCustomerSelect = document.getElementById('existingCustomerSelect');
            if (existingCustomerSelect && !existingCustomerSelect.value) {
                existingCustomerSelect.value = String(existingCustomersForSale[0].id);
                selectedExistingCustomerId = Number(existingCustomersForSale[0].id || 0);
            }
        }

        updateCreateCustomerVisibility();
        
        document.getElementById('confirmPurchase').addEventListener('click', function () {
    const button = this;

    // Activar loading
    button.disabled = true;
    const originalText = button.innerHTML;
    button.innerHTML = `
        <span class="spinner-border spinner-border-sm me-2"></span>
        Procesando...
    `;

    const tenantId = Number(authUser.tenant_id);
    const deliveryType = document.querySelector('input[name="delivery_type"]:checked')?.value || 'pickup';
    const saleDocumentMode = document.querySelector('input[name="sale_document_mode"]:checked')?.value || 'delivery_note';
    const deliveryAddressData = buildDeliveryAddress();
    const deliveryContext = getAdminDeliveryChargeContext(true);
    const shouldCreateNewCustomer = document.querySelector('input[name="create_new_customer"]:checked')?.value === 'yes';
    const newCustomerName = (document.getElementById('newCustomerName')?.value || '').trim();
    const newCustomerEmail = (document.getElementById('newCustomerEmail')?.value || '').trim();
    const newCustomerPhone = (document.getElementById('newCustomerPhone')?.value || '').trim();
    const newCustomerDni = (document.getElementById('newCustomerDni')?.value || '').trim();

    if (deliveryType === 'shipping' && !deliveryAddressData.valid) {
        alert(deliveryAddressData.message || 'Debes indicar la direcciÃ³n cuando la entrega es por envÃ­o.');
        button.disabled = false;
        button.innerHTML = originalText;
        return;
    }

    if (!deliveryContext.valid) {
        alert(deliveryContext.message || 'Debes completar la informaciÃ³n del delivery.');
        button.disabled = false;
        button.innerHTML = originalText;
        return;
    }

    if (saleDocumentMode === 'electronic_invoice' && !tenantElectronicInvoicingEnabled) {
        alert('La facturaciÃ³n digital estÃ¡ desactivada para esta sede.');
        button.disabled = false;
        button.innerHTML = originalText;
        return;
    }

    if (shouldCreateNewCustomer) {
        const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!newCustomerName || !newCustomerPhone || !newCustomerDni) {
            alert('Para crear un cliente nuevo debes completar nombre, telÃ©fono y DNI.');
            button.disabled = false;
            button.innerHTML = originalText;
            return;
        }

        if (newCustomerEmail && !emailPattern.test(newCustomerEmail)) {
            alert('El correo del nuevo cliente no es vÃ¡lido.');
            button.disabled = false;
            button.innerHTML = originalText;
            return;
        }
    } else if (!selectedExistingCustomerId) {
        alert('Debes seleccionar un cliente existente para registrar la venta.');
        button.disabled = false;
        button.innerHTML = originalText;
        return;
    }

    const validPayments = payments.filter(payment => Number((payment.amountBase ?? payment.amount) || 0) > 0);

    const summary = {
        customerId: shouldCreateNewCustomer ? null : { id: selectedExistingCustomerId },
        customer_existing_id: shouldCreateNewCustomer ? null : selectedExistingCustomerId,
        items: selectedItems,
        tenant_id: tenantId,
        dollarRate: baseRateToBs,
        delivery_type: deliveryType,
        delivery_address: deliveryType === 'shipping' ? deliveryAddressData.address : 'sede',
        delivery_city_id: deliveryType === 'shipping' ? Number(deliveryAddressData.cityId || 0) : null,
        delivery_distance_km: deliveryType === 'shipping' ? deliveryContext.distanceKm : null,
        create_new_customer: shouldCreateNewCustomer,
        customer_new: shouldCreateNewCustomer
            ? {
                name: newCustomerName,
                email: newCustomerEmail || null,
                phone_code: document.getElementById('newCustomerPhoneCode')?.value || '+58',
                phone_number: newCustomerPhone,
                dni: newCustomerDni,
            }
            : null,
        mark_delivered: document.getElementById('markDelivered')?.checked || false,
        mark_payments_paid: document.getElementById('markPaymentsPaid')?.checked || false,
        mark_sale_completed: document.getElementById('markSaleCompleted')?.checked || false,
        sale_document_mode: saleDocumentMode,
    };

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    const formData = new FormData();
    appendFormDataValue(formData, 'customer_existing_id', summary.customer_existing_id);
    appendFormDataValue(formData, 'items', summary.items);
    appendFormDataValue(formData, 'tenant_id', summary.tenant_id);
    appendFormDataValue(formData, 'dollarRate', summary.dollarRate);
    appendFormDataValue(formData, 'delivery_type', summary.delivery_type);
    appendFormDataValue(formData, 'delivery_address', summary.delivery_address);
    appendFormDataValue(formData, 'delivery_city_id', summary.delivery_city_id);
    appendFormDataValue(formData, 'delivery_distance_km', summary.delivery_distance_km);
    appendFormDataValue(formData, 'create_new_customer', summary.create_new_customer);
    appendFormDataValue(formData, 'customer_new', summary.customer_new);
    appendFormDataValue(formData, 'mark_delivered', summary.mark_delivered);
    appendFormDataValue(formData, 'mark_payments_paid', summary.mark_payments_paid);
    appendFormDataValue(formData, 'mark_sale_completed', summary.mark_sale_completed);
    appendFormDataValue(formData, 'sale_document_mode', summary.sale_document_mode);
    appendFormDataValue(formData, 'change_paid_in_bs', (totalPaid - roundMoney(totalAmount) > 0.0001)
        ? (document.getElementById('changePaidInBs')?.checked || false)
        : false);
    appendFormDataValue(formData, 'change_rate_to_bs', baseRateToBs);

    validPayments.forEach((payment, index) => {
        appendFormDataValue(formData, `payments[${index}][methodId]`, payment.methodId);
        appendFormDataValue(formData, `payments[${index}][amount]`, payment.amountBase ?? payment.amount);
        appendFormDataValue(formData, `payments[${index}][amount_base]`, payment.amountBase ?? payment.amount);
        appendFormDataValue(formData, `payments[${index}][amount_original]`, payment.amountOriginal ?? payment.amountBase ?? payment.amount);
        appendFormDataValue(formData, `payments[${index}][exchange_rate_to_base]`, payment.exchangeRateToBase ?? 1);
        appendFormDataValue(formData, `payments[${index}][applies_igtf]`, Boolean(payment.appliesIgtf));
        appendFormDataValue(formData, `payments[${index}][currency]`, payment.currency);
        appendFormDataValue(formData, `payments[${index}][reference]`, payment.reference);

        const proofInput = document.querySelector(`.payment-proof-input[data-entry-id="${payment.entryId}"]`);
        if (proofInput?.files?.[0]) {
            formData.append(`payments[${index}][proof_image]`, proofInput.files[0]);
        }
    });

    fetch('/create-sale', {
        method: 'POST',
        headers: {
            'Accept': 'application/json',
            'X-CSRF-TOKEN': csrfToken
        },
        body: formData
    })
        .then(async response => {
            const payload = await response.json().catch(() => ({}));

            if (response.ok) {
                return payload;
            }

            throw new Error(payload.message || payload.error || 'Error al confirmar la compra.');
        })
        .then(data => {
            let successMessage = data.message || 'Compra confirmada con Ã©xito.';
            if (data.created_customer_temporary_password) {
                successMessage += `\n\nCliente creado con contraseÃ±a temporal: ${data.created_customer_temporary_password}.`;
                successMessage += '\nDebe iniciar sesiÃ³n en la landing y cambiarla en Mi perfil.';
            }
            alert(successMessage);

            if (data.pdf_url) {
                const link = document.createElement('a');
                link.href = data.pdf_url;
                link.download = '';
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
            }

            if (data.nota_entrega_pdf_url) {
                const linkNota = document.createElement('a');
                linkNota.href = data.nota_entrega_pdf_url;
                linkNota.download = '';
                document.body.appendChild(linkNota);
                linkNota.click();
                document.body.removeChild(linkNota);
            }

            window.location.href = '/sales-orders';
        })
        .catch(error => {
            console.error('Error:', error);
            alert(error.message || 'Error al confirmar la compra.');
        })
        .finally(() => {
            // Restaurar botÃ³n en cualquier caso
            button.disabled = false;
            button.innerHTML = originalText;
        });
});


    
