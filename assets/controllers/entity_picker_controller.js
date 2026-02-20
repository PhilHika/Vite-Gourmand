import { Controller } from '@hotwired/stimulus';

/*
 * Unified controller for managing entity selection in forms.
 * Replaces: menu_plats, plat_menus, plat_allergenes, menu_regime controllers.
 *
 * Supports:
 * - ManyToMany (multiple selection) and ManyToOne (single selection) via `mode` value.
 * - Optional on-the-fly entity creation via `createUrl` value.
 * - Visual card-list display with add/remove buttons.
 * - Bootstrap modal for selection and creation.
 * - Save reminder after changes.
 *
 * Usage in Twig:
 *   <div data-controller="entity-picker"
 *        data-entity-picker-mode-value="multiple"          (or "single", default: "multiple")
 *        data-entity-picker-modal-id-value="platsModal"    (ID of the Bootstrap modal)
 *        data-entity-picker-empty-message-value="Aucun plat associé"
 *        data-entity-picker-create-url-value="/admin/..."  (optional, enables creation)
 *        data-entity-picker-form-field-name-value="regime_form[libelle]"> (required if createUrl is set)
 *   </div>
 */
export default class extends Controller {
    static targets = ["originalSelect", "list", "modalSelect", "newInput", "createButton", "reminder"];
    static values = {
        mode: { type: String, default: 'multiple' },            // 'single' or 'multiple'
        modalId: String,                                        // Bootstrap modal ID
        emptyMessage: { type: String, default: 'Aucun élément sélectionné' },
        createUrl: String,                                      // Optional: URL to POST for on-the-fly creation
        formFieldName: String,                                  // Optional: form field name for creation (e.g. 'regime_form[libelle]')
        editUrlTemplate: String,                                // Optional: URL template with __ID__ placeholder for edit links
    }

    connect() {
        // Find the actual <select> element inside the wrapper div
        this.selectElement = this.originalSelectTarget.querySelector('select');

        if (!this.selectElement) {
            if (this.originalSelectTarget.tagName === 'SELECT') {
                this.selectElement = this.originalSelectTarget;
            }
        }

        if (!this.selectElement) {
            console.error('entity-picker: Select element not found.');
            this.listTarget.innerHTML = '<li class="list-group-item text-danger">Erreur: Sélecteur introuvable</li>';
            return;
        }

        // Hide the original Symfony select
        this.originalSelectTarget.style.display = 'none';

        // Render the initial state
        this.renderList();
    }

    // ── Rendering ──────────────────────────────────────────────

    renderList() {
        this.listTarget.innerHTML = '';

        if (this.isSingleMode()) {
            this._renderSingle();
        } else {
            this._renderMultiple();
        }
    }

    _renderSingle() {
        const selected = this.selectElement.options[this.selectElement.selectedIndex];

        if (selected && selected.value) {
            const item = this._createListItem(selected.text, selected.value);
            this.listTarget.appendChild(item);
        } else {
            this.listTarget.innerHTML = `<div class="list-group-item text-muted fst-italic">${this.emptyMessageValue}</div>`;
        }
    }

    _renderMultiple() {
        const selectedOptions = Array.from(this.selectElement.selectedOptions);

        if (selectedOptions.length === 0) {
            this.listTarget.innerHTML = `<li class="list-group-item text-muted fst-italic">${this.emptyMessageValue}</li>`;
            return;
        }

        selectedOptions.forEach(option => {
            const item = this._createListItem(option.text, option.value);
            this.listTarget.appendChild(item);
        });
    }

    _createListItem(text, value) {
        const el = document.createElement(this.isSingleMode() ? 'div' : 'li');
        el.className = 'list-group-item d-flex justify-content-between align-items-center';

        const span = document.createElement('span');
        span.textContent = text;

        const btnGroup = document.createElement('div');
        btnGroup.className = 'btn-group btn-group-sm';

        // Edit button — only rendered if editUrlTemplate is provided on this instance
        if (this.hasEditUrlTemplateValue) {
            const editUrl = this.editUrlTemplateValue.replace('__ID__', value);
            // Append current page URL as ?back= so the edit page can offer a "Retour" button
            const separator = editUrl.includes('?') ? '&' : '?';
            const fullEditUrl = editUrl + separator + 'back=' + encodeURIComponent(window.location.href);
            const editLink = document.createElement('a');
            editLink.href = fullEditUrl;
            editLink.className = 'btn btn-outline-primary btn-sm';
            editLink.innerHTML = '<i class="bi bi-pencil"></i> Éditer';
            btnGroup.appendChild(editLink);
        }

        // Remove button
        const removeBtn = document.createElement('button');
        removeBtn.type = 'button';
        removeBtn.className = 'btn btn-outline-danger btn-sm';
        removeBtn.dataset.value = value;
        removeBtn.dataset.action = 'click->entity-picker#remove';
        removeBtn.innerHTML = '<i class="bi bi-trash"></i> Retirer';
        btnGroup.appendChild(removeBtn);

        el.appendChild(span);
        el.appendChild(btnGroup);
        return el;
    }

    // ── Modal ──────────────────────────────────────────────────

    openModal() {
        this.modalSelectTarget.value = "";

        // Clear the creation input if present
        if (this.hasNewInputTarget) {
            this.newInputTarget.value = "";
        }

        // For multiple mode: hide already-selected options in the modal dropdown
        if (!this.isSingleMode()) {
            const selectedValues = Array.from(this.selectElement.selectedOptions).map(opt => opt.value);

            Array.from(this.modalSelectTarget.options).forEach(opt => {
                if (opt.value === "") {
                    opt.style.display = '';
                    return;
                }

                if (selectedValues.includes(opt.value)) {
                    opt.style.display = 'none';
                    opt.disabled = true;
                } else {
                    opt.style.display = '';
                    opt.disabled = false;
                }
            });
        }
    }

    // ── Actions ────────────────────────────────────────────────

    // Select an existing entity from the modal dropdown
    add(event) {
        event.preventDefault();
        const selectedValue = this.modalSelectTarget.value;
        if (!selectedValue) return;

        this._selectAndClose(selectedValue);
    }

    // Create a new entity on the fly, then select it
    create(event) {
        event.preventDefault();
        const newName = this.newInputTarget.value.trim();
        if (!newName) return;

        if (!this.hasCreateUrlValue) {
            console.error("entity-picker: createUrl not defined");
            return;
        }

        const button = this.createButtonTarget;
        const originalText = button.innerText;
        button.disabled = true;
        button.innerText = '...';

        const formData = new FormData();
        formData.append(this.formFieldNameValue, newName);

        fetch(this.createUrlValue, {
            method: 'POST',
            body: formData
        })
            .then(response => response.json())
            .then(data => {
                if (data.success && data.item) {
                    const item = data.item;

                    // Add to the real (hidden) select
                    const newOption = new Option(item.libelle, item.id);
                    this.selectElement.add(newOption);

                    // Add to the modal select
                    const newModalOption = new Option(item.libelle, item.id);
                    this.modalSelectTarget.add(newModalOption);

                    // Select and close
                    this._selectAndClose(item.id.toString());
                } else {
                    alert('Erreur: ' + (data.message || 'Inconnue'));
                }
            })
            .catch(err => {
                console.error(err);
                alert("Erreur réseau lors de la création.");
            })
            .finally(() => {
                button.disabled = false;
                button.innerText = originalText;
            });
    }

    // Remove an entity from the selection
    remove(event) {
        event.preventDefault();

        if (this.isSingleMode()) {
            // Reset to empty/default option
            this.selectElement.value = "";
        } else {
            const valueToRemove = event.currentTarget.dataset.value;
            const option = Array.from(this.selectElement.options).find(opt => opt.value === valueToRemove);
            if (option) {
                option.selected = false;
            }
        }

        this.renderList();
        this._showReminder();
    }

    // ── Helpers ────────────────────────────────────────────────

    _selectAndClose(value) {
        if (this.isSingleMode()) {
            // ManyToOne: replace the current value
            this.selectElement.value = value;
        } else {
            // ManyToMany: add to multi-selection
            const option = Array.from(this.selectElement.options).find(opt => opt.value === value);
            if (option) {
                option.selected = true;
            }
        }

        this.renderList();

        // Close the Bootstrap modal
        if (this.hasModalIdValue) {
            const modalElement = document.getElementById(this.modalIdValue);
            if (modalElement) {
                const closeBtn = modalElement.querySelector('[data-bs-dismiss="modal"]');
                if (closeBtn) closeBtn.click();
            }
        }

        // Reset modal inputs
        this.modalSelectTarget.value = "";
        if (this.hasNewInputTarget) {
            this.newInputTarget.value = "";
        }

        this._showReminder();
    }

    _showReminder() {
        if (this.hasReminderTarget) {
            this.reminderTarget.classList.remove('d-none');
        }
    }

    isSingleMode() {
        return this.modeValue === 'single';
    }
}
