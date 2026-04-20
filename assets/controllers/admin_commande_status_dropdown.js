import { Controller } from '@hotwired/stimulus';

/*
 * Gère l'ergonomie du dropdown de statut commande dans le formulaire admin.
 *
 * - Grise "En attente de retour matériel" si pretMateriel non coché ou statut ≠ livree
 * - Grise "Terminée" si les conditions métier ne sont pas remplies
 * - Met à jour dynamiquement le label des options pour expliquer la raison
 *
 * Usage dans templates/admin/commande/edit.html.twig :
 *   <section data-controller="admin-commande-status-dropdown"
 *            data-admin-commande-status-dropdown-statut-actuel-value="{{ commande.statut }}">
 */
export default class AdminCommandeStatusDropdownController extends Controller {
    static values = {
        statutActuel: String,
    };

    connect() {
        this.pretCheckbox = document.getElementById('admin_commande_form_type_pretMateriel');
        this.restitutionCheckbox = document.getElementById('admin_commande_form_type_restitutionMateriel');
        this.statutSelect = document.getElementById('commande-statut-select');

        if (!this.pretCheckbox || !this.restitutionCheckbox || !this.statutSelect) return;

        this._updateOptions = this.updateOptions.bind(this);
        this.pretCheckbox.addEventListener('change', this._updateOptions);
        this.restitutionCheckbox.addEventListener('change', this._updateOptions);
    }

    disconnect() {
        if (this.pretCheckbox) this.pretCheckbox.removeEventListener('change', this._updateOptions);
        if (this.restitutionCheckbox) this.restitutionCheckbox.removeEventListener('change', this._updateOptions);
    }

    updateOptions() {
        const pret = this.pretCheckbox.checked;
        const restitution = this.restitutionCheckbox.checked;
        const statutActuel = this.statutActuelValue;

        this.statutSelect.querySelectorAll('option').forEach((option) => {
            if (option.value === 'en_attente_retour_materiel') {
                this.#updateAttenteRetour(option, statutActuel, pret, restitution);
            }
            if (option.value === 'terminee') {
                this.#updateTerminee(option, statutActuel, pret, restitution);
            }
        });
    }

    #updateAttenteRetour(option, statutActuel, pret, restitution) {
        option.disabled = !(statutActuel === 'livree' && pret && !restitution);

        if (!pret) {
            option.textContent = 'En attente de retour matériel (prêt : non)';
        } else if (restitution) {
            option.textContent = 'En attente de retour matériel (déjà restitué)';
        } else {
            option.textContent = 'En attente de retour matériel';
        }
    }

    #updateTerminee(option, statutActuel, pret, restitution) {
        const canTerminate =
            (statutActuel === 'livree' && !pret)
            || (statutActuel === 'livree' && pret && restitution)
            || (statutActuel === 'en_attente_retour_materiel' && restitution);
        option.disabled = !canTerminate;

        if (statutActuel !== 'livree' && statutActuel !== 'en_attente_retour_materiel') {
            option.textContent = 'Terminée (requiert : livrée)';
        } else if (pret && !restitution) {
            option.textContent = 'Terminée (restitution : non)';
        } else {
            option.textContent = 'Terminée';
        }
    }
}
