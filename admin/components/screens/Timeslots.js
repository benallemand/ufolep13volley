import { defineAsyncComponent } from 'vue';

/**
 * Gestion des créneaux (issue #288).
 * Remplace `js/view/grid/Timeslots.js` + `js/view/window/Timeslot.js`.
 *
 * L'admin ExtJS avait **deux** entrées de menu distinctes : *Gestion des
 * créneaux*, ce CRUD, et *Planning de la semaine*, une consultation
 * (`#/week-schedule`). L'inventaire du lot 6 de #265 les avait mappées toutes
 * les deux sur la seconde : le CRUD est resté sur le carreau, relevé en
 * recette par Benjamin.
 *
 * Un créneau, c'est le rendez-vous de réception d'une équipe : gymnase, jour,
 * heure. Les scripts Python de génération lisent la table `creneau` pour
 * placer les matchs, et `matchs_view` s'en sert pour l'heure de réception —
 * une erreur ici se propage donc au calendrier.
 */
export default {
    components: {
        'admin-grid': defineAsyncComponent(() => import('../grid/AdminGrid.js')),
    },
    template: `
      <admin-grid
        title="Gestion des créneaux"
        entity-label="créneau"
        :columns="columns"
        :fields="fields"
        fetch-url="/rest/action.php/timeslot/getTimeSlots"
        save-url="/rest/action.php/timeslot/saveTimeSlot"
        delete-url="/rest/action.php/timeslot/delete"/>
    `,
    data() {
        return {
            teams: [],
            gymnasiums: [],
            columns: [
                { key: 'team_full_name', label: 'Équipe' },
                { key: 'gymnasium_full_name', label: 'Gymnase' },
                { key: 'jour', label: 'Jour' },
                { key: 'heure', label: 'Heure' },
                {
                    key: 'has_time_constraint', label: 'Contrainte horaire',
                    format: (v) => (Number(v) ? 'oui' : 'non'),
                    badge: (r) => 'badge badge-sm ' + (Number(r.has_time_constraint) ? 'badge-warning' : 'badge-ghost'),
                },
                { key: 'usage_priority', label: 'Priorité', align: 'right' },
            ],
        };
    },
    computed: {
        fields() {
            return [
                {
                    name: 'id_equipe', label: 'Équipe', type: 'select', required: true,
                    options: this.teams.map((t) => ({
                        value: t.id_equipe,
                        label: t.team_full_name || t.nom_equipe,
                    })),
                },
                {
                    name: 'id_gymnase', label: 'Gymnase', type: 'select', required: true,
                    options: this.gymnasiums.map((g) => ({
                        value: g.id,
                        label: g.full_name || g.nom,
                    })),
                },
                {
                    name: 'jour', label: 'Jour de réception', type: 'select', required: true,
                    // Pas de samedi ni de dimanche : les rencontres se jouent
                    // en semaine, comme dans le formulaire ExtJS.
                    options: ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi']
                        .map((j) => ({ value: j, label: j })),
                },
                {
                    name: 'heure', label: 'Heure de réception', type: 'select', required: true,
                    options: this.hours.map((h) => ({ value: h, label: h })),
                },
                { name: 'has_time_constraint', label: 'Contrainte horaire forte ?', type: 'checkbox' },
                {
                    name: 'usage_priority', label: "Priorité d'utilisation", type: 'number',
                    min: 1, required: true,
                    help: 'Ordre de préférence quand une équipe a plusieurs créneaux (1 = principal)',
                },
            ];
        },
        /** De 18:00 à 21:45 par quart d'heure, comme le combo ExtJS. */
        hours() {
            const list = [];
            for (let h = 18; h <= 21; h += 1) {
                for (const m of ['00', '15', '30', '45']) {
                    list.push(`${h}:${m}`);
                }
            }
            return list;
        },
    },
    created() {
        axios.get('/rest/action.php/team/getTeams')
            .then(({ data }) => { this.teams = data; })
            .catch(() => { this.teams = []; });
        axios.get('/rest/action.php/court/getGymnasiums')
            .then(({ data }) => { this.gymnasiums = data; })
            .catch(() => { this.gymnasiums = []; });
    },
};
