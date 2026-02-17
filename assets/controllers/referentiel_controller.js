import { Controller } from '@hotwired/stimulus';

/*
 * Controls the "Reference" management modal (e.g. Allergenes, Regimes)
 * 
 * Usage in Twig:
 * <div class="modal" 
 *      data-controller="referentiel"
 *      data-referentiel-list-url-value="..."
 *      data-referentiel-add-url-value="..."
 *      data-referentiel-delete-url-template-value="..."
 *      data-referentiel-select-id-value="id_of_symfony_select"
 *      data-referentiel-form-name-value="allergene_form">
 * ...
 * </div>
 */
export default class extends Controller {
    static values = {
        listUrl: String,
        addUrl: String,
        deleteUrlTemplate: String,
        selectId: String,
        formName: String
    }

    static targets = ["list", "input", "addButton"]

    connect() {
        // Listen for Bootstrap modal show event
        this.element.addEventListener('show.bs.modal', this.load.bind(this));

        // Find the external select element on the page
        this.selectTarget = document.getElementById(this.selectIdValue);
    }

    load() {
        this.listTarget.innerHTML = '<li class="list-group-item text-center"><span class="spinner-border spinner-border-sm"></span> Chargement...</li>';

        fetch(this.listUrlValue)
            .then(response => {
                if (!response.ok) throw new Error('Network error');
                return response.json();
            })
            .then(data => {
                this.renderList(data);
                this.updateSelect(data);
            })
            .catch(error => {
                this.listTarget.innerHTML = '<li class="list-group-item text-danger text-center">Erreur de chargement</li>';
                console.error('Referentiel Load Error:', error);
            });
    }

    renderList(data) {
        this.listTarget.innerHTML = '';

        if (data.length === 0) {
            this.listTarget.innerHTML = '<li class="list-group-item text-muted text-center">Aucun élément trouvé. Ajoutez-en un !</li>';
            return;
        }

        data.forEach(item => {
            const li = document.createElement('li');
            li.className = 'list-group-item d-flex justify-content-between align-items-center';
            li.innerHTML = `
                ${item.libelle}
                <button class="btn btn-sm btn-danger" data-action="click->referentiel#delete" data-referentiel-id-param="${item.id}">&times;</button>
            `;
            this.listTarget.appendChild(li);
        });
    }

    updateSelect(data) {
        if (!this.selectTarget) return;

        // Keep currently selected values
        const selectedIds = Array.from(this.selectTarget.selectedOptions).map(opt => opt.value);
        this.selectTarget.innerHTML = '';

        // Add default option if not multiple
        if (!this.selectTarget.multiple) {
            const defaultOption = document.createElement('option');
            defaultOption.value = '';
            defaultOption.textContent = 'Choisir...';
            this.selectTarget.appendChild(defaultOption);
        }

        data.forEach(item => {
            const option = document.createElement('option');
            option.value = item.id;
            option.textContent = item.libelle;
            if (selectedIds.includes(String(item.id))) {
                option.selected = true;
            }
            this.selectTarget.appendChild(option);
        });
    }

    add(event) {
        event.preventDefault();
        const libelle = this.inputTarget.value.trim();
        if (!libelle) return;

        const formData = new FormData();
        formData.append(`${this.formNameValue}[libelle]`, libelle);

        const button = this.addButtonTarget;
        const originalText = button.innerText;
        button.disabled = true;
        button.innerText = 'Ajout...';

        fetch(this.addUrlValue, {
            method: 'POST',
            body: formData
        })
            .then(response => response.ok ? response.json() : Promise.reject(response)) // Check ok status first
            .then(data => {
                // Some controllers return success inside json
                if (data.success || data.id) {
                    this.inputTarget.value = '';
                    this.load();
                } else {
                    throw new Error(data.message || 'Erreur inconnue');
                }
            })
            .catch((error) => {
                // If we failed in the first then(), response might be the error object
                // Ideally we standardise JSON response {success: bool}
                // For now assuming success if we got here or reloading anyway
                // Let's rely on load() to refresh.
                // If actual error:
                console.error(error);
                alert('Erreur lors de l\'ajout.');
            })
            .finally(() => {
                button.disabled = false;
                button.innerText = originalText;
                // Reload list to be safe and consistent
                this.load();
            });
    }

    delete(event) {
        if (!confirm('Supprimer cet élément ?')) return;

        const id = event.params.id;
        // Replace placeholder in template using simple string replacement
        // Note: The template should contain a placeholder like 'PLACEHOLDER'
        const url = this.deleteUrlTemplateValue.replace('PLACEHOLDER', id); // We used 'list' hack before, let's use a clear placeholder now

        fetch(url, { method: 'DELETE' })
            .then(response => {
                if (response.ok) this.load();
                else alert('Impossible de supprimer cet élément (peut-être utilisé ?)');
            })
            .catch(() => alert('Erreur réseau'));
    }
}
