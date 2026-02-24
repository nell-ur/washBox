// assets/js/laundry-create.js

/**
 * Laundry Creation Manager
 * Handles all functionality for laundry creation (both admin and staff)
 */
class LaundryCreateManager {
    constructor(options = {}) {
        this.isAdmin = options.isAdmin || false;
        this.hasPickup = options.hasPickup || false;
        this.pickupData = options.pickupData || null;
        this.csrfToken = options.csrfToken || document.querySelector('meta[name="csrf-token"]')?.content || '';

        // Store add-ons data
        this.addons = [];
        this.selectedAddons = new Map(); // Map of addonId -> { id, quantity, price, name }

        this.initialize();
    }

    _injectMissingFields() {
        // Find the service row to inject after — look for serviceSelect parent row
        const serviceSelect = document.getElementById('serviceSelect');
        if (!serviceSelect) return;

        const serviceRow = serviceSelect.closest('.row, .col-md-6, .col-lg-6')?.closest('.row');
        if (!serviceRow) return;

        // Inject weight container if missing
        if (!document.getElementById('weightContainer')) {
            const weightHtml = `
                <div class="col-md-6" id="weightContainer">
                    <label class="form-label fw-semibold" for="weightInput">
                        Weight (kg) <span class="text-danger">*</span>
                    </label>
                    <div class="input-group">
                        <input type="number" name="weight" step="0.1" min="0"
                            class="form-control"
                            placeholder="0.0"
                            id="weightInput">
                        <span class="input-group-text">kg</span>
                    </div>
                    <small class="text-muted" id="weightHelp">Enter laundry weight</small>
                </div>`;
            serviceRow.insertAdjacentHTML('beforeend', weightHtml);
        }

        // Inject loads container if missing
        if (!document.getElementById('loadsContainer')) {
            const loadsHtml = `
                <div class="col-md-6 d-none" id="loadsContainer">
                    <label class="form-label fw-semibold" for="loadsInput">Number of Loads <span class="text-danger">*</span></label>
                    <input type="number" name="number_of_loads" min="1"
                        class="form-control"
                        value="1"
                        placeholder="1"
                        id="loadsInput">
                    <small class="text-muted" id="loadsHelp">Number of loads/pieces</small>
                </div>`;
            serviceRow.insertAdjacentHTML('beforeend', loadsHtml);
        }

        // Re-cache containers
        this.weightContainer = document.getElementById('weightContainer');
        this.loadsContainer  = document.getElementById('loadsContainer');
        this.weightHelp      = document.getElementById('weightHelp');
        this.loadsHelp       = document.getElementById('loadsHelp');
    }

    initialize() {
        this.cacheElements();
        this.attachEventListeners();
        this.initializeCustomerInfo();
        this.updatePricingFields();
        this.updatePrice();
        this.initializeAddons();
        this.initAddonQuantityControls();
        this.initializeFormValidation();

        // Debug promotion data
        console.log('Promotions available:');
        if (this.promotionSelect) {
            Array.from(this.promotionSelect.options).forEach(option => {
                if (option.value) {
                    console.log({
                        value: option.value,
                        text: option.text,
                        applicationType: option.dataset.applicationType,
                        displayPrice: option.dataset.displayPrice,
                        discountType: option.dataset.discountType,
                        discountValue: option.dataset.discountValue
                    });
                }
            });
        }

        console.log('✅ Laundry Create Manager initialized');
    }

    cacheElements() {
        // Form elements
        this.form = document.getElementById('laundryForm') || document.getElementById('orderForm');
        this.serviceSelect = document.getElementById('serviceSelect');
        this.promotionSelect = document.getElementById('promotionSelect');
        this.weightInput = document.getElementById('weightInput');
        this.loadsInput = document.getElementById('loadsInput');

        // If fields are missing (pickup create blade), inject them after Service select
        if (!this.weightInput || !this.loadsInput) {
            this._injectMissingFields();
            this.weightInput = document.getElementById('weightInput');
            this.loadsInput = document.getElementById('loadsInput');
            this.weightContainer = document.getElementById('weightContainer');
            this.loadsContainer = document.getElementById('loadsContainer');
        }
        this.pickupFeeInput = document.getElementById('pickupFeeInput');
        this.deliveryFeeInput = document.getElementById('deliveryFeeInput');
        this.customerSelect = document.getElementById('customerSelect');

        // Containers
        this.weightContainer = document.getElementById('weightContainer');
        this.loadsContainer = document.getElementById('loadsContainer');
        this.customerInfo = document.getElementById('customerInfo');
        this.extraWeightWarning = document.getElementById('extraWeightWarning');
        this.addonsSection = document.getElementById('addonsSection');
        this.promotionSection = document.getElementById('promotionSection');
        this.extraLoadsSection = document.getElementById('extraLoadsSection');

        // Display elements
        this.servicePriceDisplay = document.getElementById('servicePriceDisplay');
        this.quantityDisplay = document.getElementById('quantityDisplay');
        this.serviceSubtotalDisplay = document.getElementById('serviceSubtotalDisplay');
        this.pickupFeeDisplay = document.getElementById('pickupFeeDisplay');
        this.deliveryFeeDisplay = document.getElementById('deliveryFeeDisplay');
        this.totalFeesDisplay = document.getElementById('totalFeesDisplay');
        this.totalDisplay = document.getElementById('totalDisplay');
        this.addonsTotalDisplay = document.getElementById('addonsTotalDisplay');
        this.promotionDiscountDisplay = document.getElementById('promotionDiscountDisplay');
        this.extraLoadsCount = document.getElementById('extraLoadsCount');
        this.extraLoadsCharge = document.getElementById('extraLoadsCharge');
        this.addonsList = document.getElementById('addonsList');
        this.loadsBreakdownList = document.getElementById('loadsBreakdownList');
        this.serviceBaseInfo = document.getElementById('serviceBaseInfo');
        this.loadsBreakdown = document.getElementById('loadsBreakdown');
        this.serviceChargesTitle = document.getElementById('serviceChargesTitle');
        this.serviceDescription = document.getElementById('serviceDescription');
        this.promotionDescription = document.getElementById('promotionDescription');
        this.extraWeightMessage = document.getElementById('extraWeightMessage');
        this.autoExtraLoad = document.getElementById('autoExtraLoad');
        this.weightHelp = document.getElementById('weightHelp');
        this.loadsHelp = document.getElementById('loadsHelp');

        // Inject weight row into Order Summary dynamically (blade is not modified)
        this.weightSummaryRow = document.getElementById('weightSummaryRow');
        if (!this.weightSummaryRow && this.serviceBaseInfo) {
            // Insert after "Base Service" row, before "Quantity" row
            const weightRowHtml = `
                <div class="d-flex justify-content-between mb-2" id="weightSummaryRow" style="display:none;">
                    <span class="text-muted"><i class="bi bi-speedometer2 me-1"></i>Weight:</span>
                    <strong id="weightSummaryDisplay" class="text-primary">—</strong>
                </div>`;
            const quantityRow = this.serviceBaseInfo.querySelector('#quantityDisplay')?.closest('.d-flex');
            if (quantityRow) {
                quantityRow.insertAdjacentHTML('beforebegin', weightRowHtml);
            } else {
                this.serviceBaseInfo.insertAdjacentHTML('beforeend', weightRowHtml);
            }
            this.weightSummaryRow = document.getElementById('weightSummaryRow');
        }
        this.weightSummaryDisplay = document.getElementById('weightSummaryDisplay');

        // Customer info elements
        this.customerPhone = document.getElementById('customerPhone');
        this.customerAddress = document.getElementById('customerAddress');

        // New customer form elements
        this.newCustomerName = document.getElementById('newCustomerName');
        this.newCustomerPhone = document.getElementById('newCustomerPhone');
        this.newCustomerAddress = document.getElementById('newCustomerAddress');
    }

    attachEventListeners() {
        // Service selection
        if (this.serviceSelect) {
            this.serviceSelect.addEventListener('change', () => {
                this.updatePricingFields();
                this.updatePrice();
            });
        }

        // Promotion selection
        if (this.promotionSelect) {
            this.promotionSelect.addEventListener('change', () => {
                this.updatePricingFields();
                this.updatePrice();
            });
        }

        // Weight input
        if (this.weightInput) {
            this.weightInput.addEventListener('input', () => {
                this.updatePrice();
                this.updateWeightSummary();
            });
        }

        // Loads input
        if (this.loadsInput) {
            this.loadsInput.addEventListener('input', () => this.updatePrice());
            this.loadsInput.addEventListener('change', () => this.autoCalculateWeight());
        }

        // Fee inputs
        if (this.pickupFeeInput) {
            this.pickupFeeInput.addEventListener('input', () => this.updatePrice());
        }

        if (this.deliveryFeeInput) {
            this.deliveryFeeInput.addEventListener('input', () => this.updatePrice());
        }

        // Customer selection
        if (this.customerSelect) {
            this.customerSelect.addEventListener('change', () => this.updateCustomerInfo());
        }

        // Form submission — patch weight before submit
        if (this.form) {
            this.form.addEventListener('submit', (e) => {
                // Always ensure weight has a value — required by DB NOT NULL constraint
                if (this.weightInput) {
                    const isHidden = this.weightContainer?.classList.contains('d-none');
                    if (isHidden || this.weightInput.value === '' || this.weightInput.value === null) {
                        this.weightInput.value = '0';
                    }
                    // Remove required so the 0 value passes HTML5 validation
                    this.weightInput.removeAttribute('required');
                }
                this.handleSubmit(e);
            });
        }
    }

    initializeCustomerInfo() {
        if (this.customerSelect && this.customerSelect.value) {
            this.updateCustomerInfo();
        }
    }

    updateCustomerInfo() {
        if (!this.customerSelect || !this.customerInfo) return;

        const selected = this.customerSelect.options[this.customerSelect.selectedIndex];

        if (this.customerSelect.value) {
            this.customerPhone.textContent = selected.dataset.phone || '-';
            this.customerAddress.textContent = selected.dataset.address || '-';
            this.customerInfo.classList.remove('d-none');
        } else {
            this.customerInfo.classList.add('d-none');
        }
    }

    /**
     * Debug method to check selected addons
     */
    debugSelectedAddons() {
        console.log('=== SELECTED ADDONS DEBUG ===');
        console.log('SelectedAddons Map size:', this.selectedAddons.size);
        this.selectedAddons.forEach((addon, id) => {
            console.log(`Addon ${id}: ${addon.name}, Quantity: ${addon.quantity}, Price: ${addon.price}`);
        });
        console.log('=============================');
    }

    /**
 * Initialize add-on quantity controls - FIXED VERSION
 */
initAddonQuantityControls() {
    // Store all add-on data
    document.querySelectorAll('.addon-checkbox').forEach(checkbox => {
        const addonId = checkbox.value;
        const price = parseFloat(checkbox.dataset.price);
        const name = checkbox.dataset.name;

        // Check if this addon is already in the array to avoid duplicates
        if (!this.addons.some(a => a.id == addonId)) {
            this.addons.push({
                id: addonId,
                price: price,
                name: name,
                checkbox: checkbox
            });
        }

        // Remove existing listeners and add fresh one
        checkbox.removeEventListener('change', this.handleCheckboxChange);
        checkbox.addEventListener('change', (e) => {
            this.toggleAddonSelection(addonId, e.target.checked);
        });
    });

    // Initialize quantity inputs
    document.querySelectorAll('.addon-quantity').forEach(input => {
        const addonId = input.dataset.addonId;

        // Remove existing listeners
        input.removeEventListener('change', this.handleQuantityChange);
        input.addEventListener('change', (e) => {
            const quantity = parseInt(e.target.value) || 1;
            if (quantity < 1) {
                e.target.value = 1;
            }

            // Update selected addon quantity
            const selected = this.selectedAddons.get(addonId);
            if (selected) {
                selected.quantity = parseInt(e.target.value);
            }

            this.updateAddonTotal(addonId);
            this.updatePrice();
        });
    });

    // FIXED: Use event delegation instead of cloning buttons
    // Guard against double-registration: remove existing handler before re-adding
    const addonsContainer = document.getElementById('addonsContainer');
    if (addonsContainer) {
        // Remove previous listener BEFORE redefining the handler reference
        if (this.handleAddonButtonClick) {
            addonsContainer.removeEventListener('click', this.handleAddonButtonClick);
            this.handleAddonButtonClick = null;
        }

        // Define the handler and bind it to this instance
        this.handleAddonButtonClick = (e) => {
            const btn = e.target.closest('.minus-btn, .plus-btn');
            if (!btn) return;

            e.preventDefault();
            e.stopPropagation();

            // Get the addon ID from the parent elements
            const addonItem = btn.closest('.addon-item');
            if (!addonItem) return;

            const addonId = addonItem.dataset.addonId;
            const isPlus = btn.classList.contains('plus-btn');
            const delta = isPlus ? 1 : -1;

            console.log(`Button clicked: ${isPlus ? '+' : '-'}, addonId: ${addonId}`);

            // Check if addon is selected
            const checkbox = document.getElementById(`addon${addonId}`);
            if (!checkbox || !checkbox.checked) {
                // If not selected, auto-select it when clicking +/-
                checkbox.checked = true;
                this.toggleAddonSelection(addonId, true);
            }

            // Update quantity
            const quantityInput = document.getElementById(`quantity${addonId}`);
            if (!quantityInput) return;

            let currentValue = parseInt(quantityInput.value) || 1;
            let newValue = currentValue + delta;

            if (newValue < 1) newValue = 1;
            if (newValue > 99) newValue = 99;

            quantityInput.value = newValue;

            // Update selected addon quantity
            const selected = this.selectedAddons.get(addonId);
            if (selected) {
                selected.quantity = newValue;
            } else {
                // If not in selected addons but checkbox is checked, add it
                if (checkbox && checkbox.checked) {
                    const addon = this.addons.find(a => a.id == addonId);
                    if (addon) {
                        this.selectedAddons.set(addonId, {
                            id: addonId,
                            quantity: newValue,
                            price: addon.price,
                            name: addon.name
                        });
                    }
                }
            }

            // Update displays
            this.updateAddonTotal(addonId);
            this.updatePrice();

            console.log(`Quantity updated from ${currentValue} to ${newValue}`);
        };

        // Add single event listener to the container (event delegation)
        addonsContainer.addEventListener('click', this.handleAddonButtonClick);
    }
}

    /**
     * Toggle add-on selection - FIXED
     */
    toggleAddonSelection(addonId, isSelected) {
        const addonItem = document.querySelector(`.addon-item[data-addon-id="${addonId}"]`);
        const checkbox = document.getElementById(`addon${addonId}`);
        const quantityInput = document.getElementById(`quantity${addonId}`);
        const minusBtn = addonItem?.querySelector('.minus-btn');
        const plusBtn = addonItem?.querySelector('.plus-btn');

        if (isSelected) {
            // Get current quantity from input
            const currentQuantity = parseInt(quantityInput?.value) || 1;

            // Add to selected map
            const addon = this.addons.find(a => a.id == addonId);
            if (addon) {
                this.selectedAddons.set(addonId, {
                    id: addonId,
                    quantity: currentQuantity,
                    price: addon.price,
                    name: addon.name
                });
                console.log(`Added addon ${addonId} with quantity ${currentQuantity}`);
            }

            // Enable quantity controls
            addonItem?.classList.add('selected');
            if (quantityInput) {
                quantityInput.disabled = false;
                quantityInput.value = currentQuantity; // Ensure value is preserved
            }
            if (minusBtn) minusBtn.disabled = false;
            if (plusBtn) plusBtn.disabled = false;

            // Set initial total
            this.updateAddonTotal(addonId);
        } else {
            // Remove from selected map
            this.selectedAddons.delete(addonId);
            console.log(`Removed addon ${addonId}`);

            // Disable quantity controls
            addonItem?.classList.remove('selected');
            if (quantityInput) {
                quantityInput.disabled = true;
                // Don't reset quantity to 1 - keep the value for when it's re-selected
            }
            if (minusBtn) minusBtn.disabled = true;
            if (plusBtn) plusBtn.disabled = true;

            // Update total display
            const totalSpan = document.getElementById(`total${addonId}`);
            if (totalSpan) totalSpan.textContent = 'Total: ₱0.00';
        }

        this.debugSelectedAddons(); // Debug
        this.updatePrice();
    }

    /**
     * Update add-on quantity - FIXED
     */
    updateAddonQuantity(addonId, delta) {
        const quantityInput = document.getElementById(`quantity${addonId}`);
        if (!quantityInput) return;

        let currentValue = parseInt(quantityInput.value) || 1;
        let newValue = currentValue + delta;

        if (newValue < 1) newValue = 1;
        if (newValue > 99) newValue = 99;

        quantityInput.value = newValue;

        // Update selected addon quantity
        const selected = this.selectedAddons.get(addonId);
        if (selected) {
            selected.quantity = newValue;
        } else {
            // If not in selected addons but checkbox is checked, add it
            const checkbox = document.getElementById(`addon${addonId}`);
            if (checkbox && checkbox.checked) {
                const addon = this.addons.find(a => a.id == addonId);
                if (addon) {
                    this.selectedAddons.set(addonId, {
                        id: addonId,
                        quantity: newValue,
                        price: addon.price,
                        name: addon.name
                    });
                }
            }
        }

        this.updateAddonTotal(addonId);
        this.updatePrice();

        console.log(`updateAddonQuantity: ${currentValue} -> ${newValue}`); // Debug
    }

    /**
     * Update add-on from input change
     */
    updateAddonFromInput(addonId) {
        const quantityInput = document.getElementById(`quantity${addonId}`);
        if (!quantityInput) return;

        let quantity = parseInt(quantityInput.value) || 1;
        if (quantity < 1) {
            quantity = 1;
            quantityInput.value = 1;
        }
        if (quantity > 99) {
            quantity = 99;
            quantityInput.value = 99;
        }

        // Update selected addon quantity
        const selected = this.selectedAddons.get(addonId);
        if (selected) {
            selected.quantity = quantity;
        }

        this.updateAddonTotal(addonId);
        this.updatePrice(); // Update summary when quantity changes
    }

    /**
     * Update add-on total display
     */
    updateAddonTotal(addonId) {
        const quantityInput = document.getElementById(`quantity${addonId}`);
        const totalSpan = document.getElementById(`total${addonId}`);
        const checkbox = document.getElementById(`addon${addonId}`);

        if (!checkbox?.checked) return;

        const price = parseFloat(checkbox.dataset.price) || 0;
        const quantity = parseInt(quantityInput?.value) || 1;
        const total = price * quantity;

        if (totalSpan) {
            totalSpan.textContent = `Total: ₱${total.toFixed(2)}`;
        }
    }

    initializeAddons() {
        // Intentionally empty — checkbox handling is done in initAddonQuantityControls
        // via toggleAddonSelection(), which already calls updatePrice().
        // Adding another listener here would cause calculateAddonsTotal() to run twice,
        // duplicating summary rows.
    }

    toggleAddonHighlight(checkbox) {
        const card = checkbox.closest('.addon-item');
        if (card) {
            if (checkbox.checked) {
                card.classList.add('selected');
            } else {
                card.classList.remove('selected');
            }
        }
    }

    updatePricingFields() {
        if (!this.serviceSelect) return;

        const selected = this.serviceSelect.options[this.serviceSelect.selectedIndex];
        const promotionSelected = this.promotionSelect?.options[this.promotionSelect.selectedIndex];

        const isPerLoadOverride = promotionSelected && promotionSelected.value &&
                                  promotionSelected.dataset.applicationType === 'per_load_override';

        // If per-load override is selected
        if (isPerLoadOverride) {
            // Show weight as optional so staff can still record actual kg
            this.weightContainer?.classList.remove('d-none');
            this.loadsContainer?.classList.remove('d-none');
            if (this.loadsInput) {
                this.loadsInput.required = true;
                if (!this.loadsInput.value) this.loadsInput.value = '1';
            }
            if (this.weightInput) {
                this.weightInput.required = false;
                const weightLabel = this.weightContainer?.querySelector('label');
                if (weightLabel) weightLabel.innerHTML = 'Weight (kg) <span class="text-muted fw-normal small">(optional)</span>';
            }
            if (this.loadsContainer) {
                const label = this.loadsContainer.querySelector('label');
                if (label) label.textContent = 'Number of Loads *';
            }
            if (this.loadsHelp) {
                this.loadsHelp.textContent = 'Number of loads (e.g., 2 loads)';
            }
            const weightHelp = document.getElementById('weightHelp');
            if (weightHelp) weightHelp.textContent = 'Record actual weight for reference';
            if (!selected.value && this.serviceDescription) {
                this.serviceDescription.textContent = 'Per-load promotion selected - No service needed';
            }
            return;
        }

        // Normal service selection
        if (!selected.value) {
            this.weightContainer?.classList.add('d-none');
            this.loadsContainer?.classList.add('d-none');
            if (this.extraWeightWarning) this.extraWeightWarning.style.display = 'none';
            if (this.serviceDescription) this.serviceDescription.textContent = '';
            return;
        }

        const pricingType = selected.dataset.pricingType;
        const serviceType = selected.dataset.serviceType;
        const maxWeight = parseFloat(selected.dataset.maxWeight) || 0;
        const minWeight = parseFloat(selected.dataset.minWeight) || 0;
        const turnaround = selected.dataset.turnaroundTime || 24;

        // Update service description
        const category = selected.dataset.category || '';
        let description = '';
        if (serviceType === 'regular_clothes' || serviceType === 'full_service') {
            description = 'Drop Off — Regular Package';
        } else if (serviceType === 'special_item') {
            description = 'Drop Off — Comforter/Blanket (per piece)';
        } else if (serviceType === 'self_service') {
            description = 'Self Service — Customer operated';
        } else if (serviceType === 'addon') {
            description = 'Add-on service';
        }

        if (this.serviceDescription) {
            this.serviceDescription.textContent = description + ' | Turnaround: ' + turnaround + ' hours';
        }

        // Handle per_piece vs per_load pricing type
        if (pricingType === 'per_piece') {
            // Per piece pricing — hide weight (irrelevant), show pieces count
            this.weightContainer?.classList.add('d-none');
            this.loadsContainer?.classList.remove('d-none');

            if (this.loadsContainer) {
                const label = this.loadsContainer.querySelector('label');
                if (label) label.textContent = 'Number of Pieces *';
            }
            if (this.loadsHelp) {
                this.loadsHelp.textContent = 'Number of pieces (e.g., 2 comforters)';
            }
            if (this.loadsInput) {
                if (!this.loadsInput.value) this.loadsInput.value = '1';
                this.loadsInput.required = true;
            }
            if (this.weightInput) {
                // Fully neutralise the hidden weight input so browser HTML5
                // validation never fires on it (hidden inputs can't be focused)
                this.weightInput.required = false;
                this.weightInput.removeAttribute('required');
                this.weightInput.removeAttribute('min');   // 0 would fail min="0.1"
                this.weightInput.value = '0';              // satisfies DB NOT NULL
            }
        } else {
            // Per load pricing — show both loads AND weight (weight is optional, for records)
            this.weightContainer?.classList.remove('d-none');
            this.loadsContainer?.classList.remove('d-none');

            if (this.loadsContainer) {
                const label = this.loadsContainer.querySelector('label');
                if (label) {
                    label.textContent = serviceType === 'special_item' ? 'Number of Pieces *' : 'Number of Loads *';
                }
            }

            if (this.loadsHelp) {
                this.loadsHelp.textContent = serviceType === 'special_item'
                    ? 'Number of pieces (e.g., 2 comforters)'
                    : 'Number of loads (e.g., 2 loads)';
            }

            if (this.loadsInput) {
                if (!this.loadsInput.value) this.loadsInput.value = '1';
                this.loadsInput.required = true;
            }

            // Weight is optional for per-load — restore attributes that were stripped for per-piece
            if (this.weightInput) {
                this.weightInput.setAttribute('min', '0');  // restore min (0 = optional recording)
                this.weightInput.required = false;
                this.weightInput.removeAttribute('required');
            }
            const weightHelp = document.getElementById('weightHelp');
            if (weightHelp) weightHelp.textContent = 'Record actual weight for reference';
        }

        this.updatePrice();
    }

    updateWeightSummary() {
        const weight = parseFloat(this.weightInput?.value) || 0;
        if (weight > 0 && this.weightSummaryRow && this.weightSummaryDisplay) {
            this.weightSummaryRow.style.display = 'flex';
            this.weightSummaryDisplay.textContent = weight.toFixed(2) + ' kg';
        } else if (this.weightSummaryRow) {
            this.weightSummaryRow.style.display = 'none';
        }
    }

    /**
     * Calculate add-ons total with quantities - FIXED with debug logs
     */
    calculateAddonsTotal() {
        let total = 0;
        const addonsList = this.addonsList;
        if (addonsList) addonsList.innerHTML = '';

        console.log('Selected addons:', Array.from(this.selectedAddons.entries())); // Debug log

        // Use selectedAddons Map for accurate quantities
        this.selectedAddons.forEach((addon, addonId) => {
            const price = addon.price;
            const quantity = addon.quantity;
            const name = addon.name;
            const itemTotal = price * quantity;
            total += itemTotal;

            console.log(`Addon ${name}: ${quantity} x ₱${price} = ₱${itemTotal}`); // Debug log

            if (addonsList) {
                const item = document.createElement('div');
                item.className = 'addons-summary-item d-flex justify-content-between align-items-center mb-1';
                item.innerHTML = `
                    <span>${name} x${quantity}</span>
                    <span>₱${itemTotal.toFixed(2)}</span>
                `;
                addonsList.appendChild(item);
            }
        });

        if (this.addonsSection) {
            this.addonsSection.style.display = total > 0 ? 'block' : 'none';
        }
        if (this.addonsTotalDisplay) {
            this.addonsTotalDisplay.textContent = '₱' + total.toFixed(2);
        }

        console.log('Addons total:', total); // Debug log
        return total;
    }

    updatePrice() {
        if (!this.serviceSelect) return;

        const selected = this.serviceSelect.options[this.serviceSelect.selectedIndex];
        const promotionSelected = this.promotionSelect?.options[this.promotionSelect.selectedIndex];

        const isPerLoadOverride = promotionSelected && promotionSelected.value &&
                                  promotionSelected.dataset.applicationType === 'per_load_override';

        if (!selected.value && !isPerLoadOverride) {
            this.resetPriceDisplay();
            return;
        }

        const pricingType = selected.dataset.pricingType;
        const serviceType = selected.dataset.serviceType;
        const pricePerLoad = parseFloat(selected.dataset.pricePerLoad) || 0;
        const pricePerPiece = parseFloat(selected.dataset.pricePerPiece) || 0;
        const maxWeight = parseFloat(selected.dataset.maxWeight) || 0;

        let serviceSubtotal = 0;
        let extraLoads = 0;
        let extraCharge = 0;
        let loads = parseInt(this.loadsInput?.value) || 1;
        let weight = 0;

        // Calculate service subtotal
        if (isPerLoadOverride && !selected.value) {
            // This is the fixed price promotion case (no service selected)
            const displayPrice = parseFloat(promotionSelected.dataset.displayPrice) || 0;
            serviceSubtotal = loads * displayPrice;

            if (this.servicePriceDisplay) this.servicePriceDisplay.textContent = '₱' + displayPrice.toFixed(2) + '/load (promo)';
            if (this.quantityDisplay) {
                this.quantityDisplay.textContent = loads + (loads === 1 ? ' load' : ' loads');
            }
            if (this.extraLoadsSection) this.extraLoadsSection.style.display = 'none';

            // Show loads breakdown with promotion price
            this.showLoadsBreakdown(loads, displayPrice, 'Promotion');
            if (this.serviceBaseInfo) this.serviceBaseInfo.style.display = 'none';
            if (this.loadsBreakdown) this.loadsBreakdown.style.display = 'block';

        } else if (selected.value) {
            // Normal service calculation (with or without promotion)
            const unitPrice = pricingType === 'per_piece' ? pricePerPiece : pricePerLoad;

            // Check if weight exceeds max for full_service
            if (serviceType === 'full_service' && maxWeight > 0) {
                weight = parseFloat(this.weightInput?.value) || maxWeight;
                const requiredLoads = Math.ceil(weight / maxWeight);

                if (requiredLoads > loads) {
                    loads = requiredLoads;
                    if (this.loadsInput) this.loadsInput.value = loads;

                    // Show extra load warning
                    if (this.extraWeightWarning) {
                        this.extraWeightWarning.style.display = 'block';
                        if (this.extraWeightMessage) {
                            this.extraWeightMessage.textContent =
                                `Weight (${weight.toFixed(1)}kg) exceeds ${maxWeight}kg per load.`;
                        }
                        if (this.autoExtraLoad) {
                            this.autoExtraLoad.textContent = `Auto-adjusted to ${loads} load(s).`;
                        }
                    }

                    extraLoads = loads - 1;
                    extraCharge = extraLoads * unitPrice;

                    if (this.extraLoadsSection) {
                        this.extraLoadsSection.style.display = 'block';
                        if (this.extraLoadsCount) {
                            this.extraLoadsCount.textContent = extraLoads + ' extra load(s)';
                        }
                        if (this.extraLoadsCharge) {
                            this.extraLoadsCharge.textContent = '₱' + extraCharge.toFixed(2);
                        }
                    }
                } else {
                    if (this.extraWeightWarning) this.extraWeightWarning.style.display = 'none';
                    if (this.extraLoadsSection) this.extraLoadsSection.style.display = 'none';
                }
            }

            serviceSubtotal = loads * unitPrice;

            // Update displays based on pricing type
            if (pricingType === 'per_piece' || serviceType === 'special_item') {
                if (this.servicePriceDisplay) {
                    this.servicePriceDisplay.textContent = '₱' + unitPrice.toFixed(2) + '/piece';
                }
                if (this.quantityDisplay) {
                    this.quantityDisplay.textContent = loads + (loads === 1 ? ' piece' : ' pieces');
                }
            } else {
                if (this.servicePriceDisplay) {
                    this.servicePriceDisplay.textContent = '₱' + unitPrice.toFixed(2) + '/load';
                }
                if (this.quantityDisplay) {
                    this.quantityDisplay.textContent = loads + (loads === 1 ? ' load' : ' loads');
                }
            }
        }

        // Debug before calculating addons
        this.debugSelectedAddons();

        // Calculate add-ons total with quantities
        const addonsTotal = this.calculateAddonsTotal();

        // Calculate promotion discount (for percentage promotions only)
        const promotionResult = this.calculatePromotion(serviceSubtotal, loads, isPerLoadOverride, selected.value);

        // Calculate fees
        const pickupFee = parseFloat(this.pickupFeeInput?.value) || 0;
        const deliveryFee = parseFloat(this.deliveryFeeInput?.value) || 0;
        const totalFees = pickupFee + deliveryFee;

        // Calculate grand total
        let grandTotal = 0;
        let displaySubtotal = 0;

        if (isPerLoadOverride && !selected.value) {
            // For fixed price promotions without service, use the promotion price directly
            displaySubtotal = serviceSubtotal;
            grandTotal = serviceSubtotal + extraCharge + addonsTotal + totalFees;
        } else if (promotionResult.isOverride) {
            // For fixed price promotions WITH service
            displaySubtotal = promotionResult.overrideTotal;
            grandTotal = promotionResult.overrideTotal + extraCharge + addonsTotal + totalFees;
        } else {
            // Regular service (with or without percentage discount)
            displaySubtotal = serviceSubtotal;
            grandTotal = serviceSubtotal - promotionResult.discount + extraCharge + addonsTotal + totalFees;
        }

        // Update displays
        if (this.serviceSubtotalDisplay) {
            this.serviceSubtotalDisplay.textContent = '₱' + displaySubtotal.toFixed(2);
        }
        if (this.pickupFeeDisplay) {
            this.pickupFeeDisplay.textContent = '₱' + pickupFee.toFixed(2);
        }
        if (this.deliveryFeeDisplay) {
            this.deliveryFeeDisplay.textContent = '₱' + deliveryFee.toFixed(2);
        }
        if (this.totalFeesDisplay) {
            this.totalFeesDisplay.textContent = '₱' + totalFees.toFixed(2);
        }
        if (this.totalDisplay) {
            this.totalDisplay.textContent = '₱' + grandTotal.toFixed(2);
        }

        this.updateWeightSummary();
    }

    calculatePromotion(serviceSubtotal, loads, isPerLoadOverride, hasService = true) {
        const promotionSelected = this.promotionSelect?.options[this.promotionSelect.selectedIndex];

        let discount = 0;
        let overrideTotal = serviceSubtotal;
        let displayText = '';
        let isOverride = false;

        if (promotionSelected && promotionSelected.value) {
            const applicationType = promotionSelected.dataset.applicationType;
            const discountType = promotionSelected.dataset.discountType;
            const discountValue = parseFloat(promotionSelected.dataset.discountValue) || 0;
            const displayPrice = parseFloat(promotionSelected.dataset.displayPrice) || 0;

            if (applicationType === 'per_load_override') {
                isOverride = true;
                overrideTotal = loads * displayPrice;

                // Only calculate discount if we have a service (for display purposes)
                if (hasService) {
                    discount = Math.max(0, serviceSubtotal - overrideTotal);
                } else {
                    discount = 0;
                }

                displayText = '₱' + displayPrice.toFixed(2) + '/load';

                // Only show breakdown if we have a service selected
                if (hasService) {
                    this.showLoadsBreakdown(loads, displayPrice, 'Promotion');
                }

                if (this.serviceChargesTitle) {
                    this.serviceChargesTitle.textContent = 'Promotion Applied';
                }
                if (this.promotionDescription) {
                    this.promotionDescription.textContent =
                        `Fixed price: ₱${displayPrice.toFixed(2)} per load`;
                }
                if (this.serviceBaseInfo) this.serviceBaseInfo.style.display = 'none';
                if (this.loadsBreakdown) this.loadsBreakdown.style.display = 'block';

            } else {
                // Regular discount promotions - ONLY percentage discounts apply
                if (discountType === 'percentage') {
                    discount = (serviceSubtotal * discountValue) / 100;
                    displayText = discountValue + '% OFF';
                    if (this.promotionDescription) {
                        this.promotionDescription.textContent =
                            `${discountValue}% discount applied`;
                    }

                    // Fixed discount promotions are NOT applied (just for display)
                } else {
                    // Fixed discount promotions - show as text but don't apply discount
                    displayText = '₱' + discountValue.toFixed(2) + ' OFF (Manual)';
                    discount = 0; // Don't apply fixed discount
                    if (this.promotionDescription) {
                        this.promotionDescription.textContent =
                            `Fixed discount of ₱${discountValue.toFixed(2)} (not applied automatically)`;
                    }
                }

                discount = Math.min(discount, serviceSubtotal);
                overrideTotal = serviceSubtotal - discount;

                if (this.serviceChargesTitle) {
                    this.serviceChargesTitle.textContent = 'Service Charges';
                }
            }

            if (this.promotionSection) {
                this.promotionSection.style.display = 'block';
            }
            if (this.promotionDiscountDisplay) {
                this.promotionDiscountDisplay.textContent = displayText;
            }

        } else {
            if (this.promotionSection) {
                this.promotionSection.style.display = 'none';
            }
            if (this.promotionDescription) {
                this.promotionDescription.textContent = '';
            }
            if (this.serviceChargesTitle) {
                this.serviceChargesTitle.textContent = 'Service Charges';
            }
        }

        return { discount, overrideTotal, isOverride };
    }

    showLoadsBreakdown(loads, pricePerLoad, label) {
        if (!this.loadsBreakdownList) return;

        this.loadsBreakdownList.innerHTML = '';

        for (let i = 1; i <= loads; i++) {
            const item = document.createElement('div');
            item.className = 'service-breakdown-item';
            item.innerHTML = `
                <span>${label} Load ${i}:</span>
                <span>₱${pricePerLoad.toFixed(2)}</span>
            `;
            this.loadsBreakdownList.appendChild(item);
        }

        const totalItem = document.createElement('div');
        totalItem.className = 'service-breakdown-total';
        totalItem.innerHTML = `
            <span>Total (${loads} ${loads === 1 ? 'load' : 'loads'}):</span>
            <span class="text-success">₱${(loads * pricePerLoad).toFixed(2)}</span>
        `;
        this.loadsBreakdownList.appendChild(totalItem);
    }

    autoCalculateWeight() {
        if (!this.serviceSelect || !this.loadsInput) return;

        const selected = this.serviceSelect.options[this.serviceSelect.selectedIndex];
        const serviceType = selected.dataset.serviceType;
        const pricingType = selected.dataset.pricingType;
        const maxWeight = parseFloat(selected.dataset.maxWeight) || 0;

        // Only auto-calculate weight for per-load full_service (not per-piece)
        if (serviceType === 'full_service' && pricingType !== 'per_piece' && maxWeight > 0) {
            const loads = parseInt(this.loadsInput.value) || 1;
            const estimatedWeight = loads * maxWeight;
            if (this.weightInput) {
                this.weightInput.value = estimatedWeight.toFixed(1);
                this.updatePrice();
            }
        }
    }

    resetPriceDisplay() {
        if (this.servicePriceDisplay) this.servicePriceDisplay.textContent = '₱0.00';
        if (this.quantityDisplay) this.quantityDisplay.textContent = '0';
        if (this.weightSummaryRow) this.weightSummaryRow.style.display = 'none';
        if (this.serviceSubtotalDisplay) this.serviceSubtotalDisplay.textContent = '₱0.00';
        if (this.pickupFeeDisplay) this.pickupFeeDisplay.textContent = '₱0.00';
        if (this.deliveryFeeDisplay) this.deliveryFeeDisplay.textContent = '₱0.00';
        if (this.totalFeesDisplay) this.totalFeesDisplay.textContent = '₱0.00';
        if (this.totalDisplay) this.totalDisplay.textContent = '₱0.00';
        if (this.extraLoadsSection) this.extraLoadsSection.style.display = 'none';
        if (this.addonsSection) this.addonsSection.style.display = 'none';
        if (this.promotionSection) this.promotionSection.style.display = 'none';
        if (this.serviceBaseInfo) this.serviceBaseInfo.style.display = 'block';
        if (this.loadsBreakdown) this.loadsBreakdown.style.display = 'none';
    }

    initializeFormValidation() {
        if (!this.form) return;

        const submitBtn = this.form.querySelector('button[type="submit"]');

        // Loading state is added in handleSubmit via the submit event in attachEventListeners
        // Store reference so handleSubmit can use it
        this.submitBtn = submitBtn;
    }

    handleSubmit(e) {
        if (!this.validateForm()) {
            e.preventDefault();
            return;
        }
        // Add loading state
        if (this.submitBtn) {
            this.submitBtn.classList.add('loading');
            this.submitBtn.disabled = true;
        }
    }

    validateForm() {
        const pickup = parseFloat(this.pickupFeeInput?.value) || 0;
        const delivery = parseFloat(this.deliveryFeeInput?.value) || 0;

        if (!this.serviceSelect && !this.hasPickup) return true;

        const promotionSelected = this.promotionSelect?.options[this.promotionSelect.selectedIndex];
        const isPerLoadOverride = promotionSelected && promotionSelected.value &&
                                  promotionSelected.dataset.applicationType === 'per_load_override';

        // ── Ensure weight always has a numeric value before submit ──────────────
        // If weightContainer is hidden (per-piece service), force weight to 0
        // so the NOT NULL constraint is satisfied in the DB
        if (this.weightInput) {
            const weightIsHidden = this.weightContainer?.classList.contains('d-none');
            if (weightIsHidden || !this.weightInput.value || this.weightInput.value === '') {
                this.weightInput.value = '0';
                this.weightInput.removeAttribute('required');
            }
        }

        // Check for pickup fees if this is a pickup laundry
        if (this.hasPickup && pickup === 0 && delivery === 0) {
            if (!confirm('⚠️ No pickup/delivery fees entered!\n\nAre you sure this laundry should have NO fees?')) {
                if (this.pickupFeeInput) {
                    this.pickupFeeInput.classList.add('is-invalid');
                    this.pickupFeeInput.focus();
                }
                return false;
            }
        }

        // Validate service selection - Allow per_load_override without service
        if (!this.serviceSelect?.value && !isPerLoadOverride) {
            this.showToast('Please select a service or choose a per-load promotion', 'warning');
            this.serviceSelect?.focus();
            return false;
        }

        // Validate loads for per-load promotions
        if (isPerLoadOverride) {
            const loads = parseInt(this.loadsInput?.value) || 0;
            if (loads <= 0) {
                this.showToast('Please enter a valid number of loads for the per-load promotion', 'warning');
                this.loadsInput?.focus();
                return false;
            }
            // If it's a per_load_override without service, we don't need to validate service
            return true;
        }

        // Validate weight/loads for normal services
        if (this.serviceSelect?.value) {
            const selected = this.serviceSelect.options[this.serviceSelect.selectedIndex];
            const pricingType = selected.dataset.pricingType;
            const serviceType = selected.dataset.serviceType;

            const loads = parseInt(this.loadsInput?.value) || 0;
            if (loads <= 0) {
                const label = (pricingType === 'per_piece' || serviceType === 'special_item')
                    ? 'number of pieces'
                    : 'number of loads';
                this.showToast(`Please enter a valid ${label}`, 'warning');
                this.loadsInput?.focus();
                return false;
            }
        }

        return true;
    }

    setFees(pickup, delivery) {
        if (this.pickupFeeInput) {
            this.pickupFeeInput.value = pickup.toFixed(2);
            this.pickupFeeInput.classList.remove('is-invalid');
        }
        if (this.deliveryFeeInput) {
            this.deliveryFeeInput.value = delivery.toFixed(2);
            this.deliveryFeeInput.classList.remove('is-invalid');
        }
        this.updatePrice();
        this.showToast(`Fees updated: Pickup ₱${pickup}, Delivery ₱${delivery}`, 'success');
    }

    addNewCustomer() {
        if (!this.newCustomerName || !this.newCustomerName.value.trim()) {
            this.showToast('Please enter customer name', 'warning');
            this.newCustomerName?.focus();
            return;
        }

        const name = this.newCustomerName.value.trim();
        const phone = this.newCustomerPhone?.value.trim() || '';
        const address = this.newCustomerAddress?.value.trim() || '';

        // Show loading state
        const addButton = document.querySelector('[onclick="addNewCustomer()"]');
        if (addButton) {
            addButton.classList.add('loading');
            addButton.disabled = true;
        }

        const customerUrl = this.isAdmin ? '/admin/customers' : '/staff/customers';
        fetch(customerUrl, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': this.csrfToken,
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify({ name, phone, address })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success && this.customerSelect) {
                const newOption = new Option(
                    data.customer.name,
                    data.customer.id,
                    false,
                    true
                );
                newOption.dataset.phone = data.customer.phone || 'N/A';
                newOption.dataset.address = data.customer.address || 'N/A';
                this.customerSelect.add(newOption);
                this.customerSelect.value = data.customer.id;
                this.updateCustomerInfo();

                // Clear form
                if (this.newCustomerName) this.newCustomerName.value = '';
                if (this.newCustomerPhone) this.newCustomerPhone.value = '';
                if (this.newCustomerAddress) this.newCustomerAddress.value = '';

                // Close accordion
                const accordion = document.getElementById('newCustomerForm');
                if (accordion && bootstrap && bootstrap.Collapse) {
                    bootstrap.Collapse.getInstance(accordion)?.hide();
                }

                this.showToast('Customer added successfully!', 'success');
            } else {
                this.showToast(data.message || 'Error adding customer', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            this.showToast('Error adding customer', 'error');
        })
        .finally(() => {
            if (addButton) {
                addButton.classList.remove('loading');
                addButton.disabled = false;
            }
        });
    }

    showToast(message, type = 'info') {
        // Create toast container if it doesn't exist
        let container = document.querySelector('.toast-container');
        if (!container) {
            container = document.createElement('div');
            container.className = 'toast-container position-fixed bottom-0 end-0 p-3';
            document.body.appendChild(container);
        }

        // Create toast element
        const toast = document.createElement('div');
        toast.className = `toast align-items-center text-white bg-${type} border-0`;
        toast.setAttribute('role', 'alert');
        toast.setAttribute('aria-live', 'assertive');
        toast.setAttribute('aria-atomic', 'true');

        toast.innerHTML = `
            <div class="d-flex">
                <div class="toast-body">
                    <i class="bi bi-${type === 'success' ? 'check-circle' : type === 'warning' ? 'exclamation-triangle' : 'info-circle'} me-2"></i>
                    ${message}
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        `;

        container.appendChild(toast);

        // Initialize and show toast
        if (window.bootstrap && bootstrap.Toast) {
            const bsToast = new bootstrap.Toast(toast, { delay: 3000 });
            bsToast.show();
            toast.addEventListener('hidden.bs.toast', () => toast.remove());
        } else {
            // Fallback
            setTimeout(() => toast.remove(), 3000);
        }
    }
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    // Check if we're on admin or staff page
    const isAdmin = document.body.classList.contains('admin-bg') ||
                   window.location.pathname.includes('/admin/');

    const hasPickup = document.querySelector('[name="pickup_request_id"]') !== null;

    const pickupData = hasPickup ? {
        id: document.querySelector('[name="pickup_request_id"]')?.value,
        // Add other pickup data if needed
    } : null;

    window.laundryManager = new LaundryCreateManager({
        isAdmin,
        hasPickup,
        pickupData,
        csrfToken: document.querySelector('meta[name="csrf-token"]')?.content
    });
});

// Export for module use if needed
if (typeof module !== 'undefined' && module.exports) {
    module.exports = LaundryCreateManager;
}
