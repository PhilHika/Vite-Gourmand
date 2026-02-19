import { Controller } from '@hotwired/stimulus';

/*
 * Controller for managing the "Plats" selection in Menu.
 * - Hides the original select[multiple].
 * - Displays selected items as list of dishes.
 * - Provides a modal to select and add new items.
 * - Shows a save reminder after changes.
 */
export default class extends Controller {
    static targets = ["originalSelect", "list", "modalSelect", "reminder"];

    connect() {
        // Find the actual select element inside the wrapper
        this.selectElement = this.originalSelectTarget.querySelector('select');

        if (!this.selectElement) {
            if (this.originalSelectTarget.tagName === 'SELECT') {
                this.selectElement = this.originalSelectTarget;
            }
        }

        if (!this.selectElement) {
            console.error('menu-plats: Select element not found.');
            this.listTarget.innerHTML = '<li class="list-group-item text-danger">Erreur: Selecteur introuvable</li>';
            return;
        }

        // Hide the wrapper containing the original select
        this.originalSelectTarget.style.display = 'none';

        // Initialize the visible list from the current selection
        this.renderList();
    }

    // Render the list of selected dishes
    renderList() {
        this.listTarget.innerHTML = '';
        const selectedOptions = Array.from(this.selectElement.selectedOptions);

        if (selectedOptions.length === 0) {
            this.listTarget.innerHTML = '<li class="list-group-item text-muted fst-italic">Aucun plat associé</li>';
            return;
        }

        selectedOptions.forEach(option => {
            const li = document.createElement('li');
            li.className = 'list-group-item d-flex justify-content-between align-items-center';
            li.innerHTML = `
                <span>${option.text}</span>
                <button type="button" class="btn btn-sm btn-outline-danger" data-value="${option.value}" data-action="click->menu-plats#remove">
                    <i class="bi bi-trash"></i> Retirer
                </button>
            `;
            this.listTarget.appendChild(li);
        });
    }

    // Open the modal (reset the modal select and filter options)
    openModal() {
        this.modalSelectTarget.value = "";

        // Get currently selected values from the REAL SELECT
        const selectedValues = Array.from(this.selectElement.selectedOptions).map(opt => opt.value);

        // Filter modal options: hide those already selected
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

    // Action: "Ajouter" button in the modal
    add(event) {
        event.preventDefault();
        const selectedValue = this.modalSelectTarget.value;

        if (!selectedValue) return;

        const options = Array.from(this.selectElement.options);
        const optionToSelect = options.find(opt => opt.value === selectedValue);

        if (optionToSelect) {
            optionToSelect.selected = true;
            this.renderList();

            // Close modal
            const modalElement = document.getElementById('platsModal');
            const closeBtn = modalElement.querySelector('[data-bs-dismiss="modal"]');
            if (closeBtn) {
                closeBtn.click();
            }

            this.modalSelectTarget.value = "";
            this.showReminder();
        }
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
