import { Controller } from '@hotwired/stimulus';

/*
 * Controller for managing the "Allergenes" selection in Plat.
 * - Hides the original select[multiple].
 * - Displays selected items as a visual list with "Retirer" buttons.
 * - Provides a modal to:
 *   1. Create a new allergene on the fly (POST to backend).
 *   2. Select an existing allergene from a filtered dropdown.
 */
export default class extends Controller {
    static targets = ["originalSelect", "list", "modalSelect", "newInput", "createButton", "reminder"];
    static values = {
        createUrl: String
    }

    connect() {
        console.log('Plat-Allergenes controller connected');

        // Find the actual select element inside the wrapper
        this.selectElement = this.originalSelectTarget.querySelector('select');

        if (!this.selectElement) {
            console.error('Select element not found inside originalSelectTarget, looking directly');
            if (this.originalSelectTarget.tagName === 'SELECT') {
                this.selectElement = this.originalSelectTarget;
            }
        }

        if (!this.selectElement) {
            console.error('Select element not found. Stopping.');
            return;
        }

        // Hide the wrapper containing the original select
        this.originalSelectTarget.style.display = 'none';

        // Initialize the visible list from the current selection
        this.renderList();
    }

    // Render the list of selected allergenes
    renderList() {
        this.listTarget.innerHTML = '';
        const selectedOptions = Array.from(this.selectElement.selectedOptions);

        if (selectedOptions.length === 0) {
            this.listTarget.innerHTML = '<li class="list-group-item text-muted fst-italic">Aucun allergène associé</li>';
            return;
        }

        selectedOptions.forEach(option => {
            const li = document.createElement('li');
            li.className = 'list-group-item d-flex justify-content-between align-items-center';
            li.innerHTML = `
                <span>${option.text}</span>
                <button type="button" class="btn btn-sm btn-outline-danger" data-value="${option.value}" data-action="click->plat-allergenes#remove">
                    <i class="bi bi-trash"></i> Retirer
                </button>
            `;
            this.listTarget.appendChild(li);
        });
    }

    // Open the modal (reset inputs and filter options)
    openModal() {
        this.modalSelectTarget.value = "";

        // Clear the "create new" input field
        if (this.hasNewInputTarget) {
            this.newInputTarget.value = "";
        }

        // Get currently selected values from the REAL SELECT
        const selectedValues = Array.from(this.selectElement.selectedOptions).map(opt => opt.value);

        // Filter modal options: hide those already selected
        Array.from(this.modalSelectTarget.options).forEach(opt => {
            if (opt.value === "") {
                opt.style.display = ''; // Always show placeholder
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

    // Action: "Ajouter la sélection" button in the modal (existing allergene)
    add(event) {
        event.preventDefault();
        const selectedValue = this.modalSelectTarget.value;

        if (!selectedValue) return;

        this.selectAndClose(selectedValue);
    }

    // Action: "Créer & Ajouter" button in the modal (new allergene)
    create(event) {
        event.preventDefault();
        const newName = this.newInputTarget.value.trim();

        if (!newName) return;

        if (!this.hasCreateUrlValue) {
            console.error("Create URL not defined");
            return;
        }

        const button = this.createButtonTarget;
        const originalText = button.innerText;
        button.disabled = true;
        button.innerText = '...';

        const formData = new FormData();
        formData.append('allergene_form[libelle]', newName);

        fetch(this.createUrlValue, {
            method: 'POST',
            body: formData
        })
            .then(response => response.json())
            .then(data => {
                if (data.success && data.item) {
                    const item = data.item;

                    // Add new option to the Real (hidden) Select
                    const newOption = new Option(item.libelle, item.id);
                    this.selectElement.add(newOption);

                    // Add new option to the Modal Select (so it exists for future filtering)
                    const newModalOption = new Option(item.libelle, item.id);
                    this.modalSelectTarget.add(newModalOption);

                    // Select and Close
                    this.selectAndClose(item.id.toString());
                } else {
                    alert('Erreur: ' + (data.message || 'Inconnue'));
                }
            })
            .catch(err => {
                console.error(err);
                alert("Erreur réseau lors de la création de l'allergène.");
            })
            .finally(() => {
                button.disabled = false;
                button.innerText = originalText;
            });
    }

    // Helper: Select an option by value, refresh list, close modal
    selectAndClose(value) {
        const options = Array.from(this.selectElement.options);
        const optionToSelect = options.find(opt => opt.value === value);

        if (optionToSelect) {
            optionToSelect.selected = true;
            this.renderList();

            // Close modal
            const modalElement = document.getElementById('allergeneModal');
            const closeBtn = modalElement.querySelector('[data-bs-dismiss="modal"]');
            if (closeBtn) {
                closeBtn.click();
            }

            // Reset modal inputs
            this.modalSelectTarget.value = "";
            if (this.hasNewInputTarget) {
                this.newInputTarget.value = "";
            }
        }

        this.showReminder();
    }

    // Action: "Retirer" button on a list item
    remove(event) {
        event.preventDefault();
        const valueToRemove = event.currentTarget.dataset.value;

        const options = Array.from(this.selectElement.options);
        const optionToUnselect = options.find(opt => opt.value === valueToRemove);

        if (optionToUnselect) {
            optionToUnselect.selected = false;
            this.renderList();
            this.showReminder();
        }
    }

    // Show save reminder
    showReminder() {
        if (this.hasReminderTarget) {
            this.reminderTarget.classList.remove('d-none');
        }
    }
}
