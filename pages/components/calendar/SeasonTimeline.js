import { seasonStart, toIso, eventDetail } from './calendarData.js';

/**
 * Agenda de la commission, en timeline de saison (issue #290).
 *
 * Remplace `AnnualCalendar.js`, 385 lignes qui redessinaient dix grilles
 * mensuelles pour n'y placer qu'une quinzaine d'événements.
 *
 * Le choix de la timeline vient de la forme des données : l'agenda est fait de
 * PÉRIODES longues — « Championnats » court sur sept semaines, « Inscriptions
 * coupe 4x4 » sur trois mois. Sur une grille mensuelle, ces périodes remplissent
 * chaque case et noient les rendez-vous ponctuels. Sur une timeline, elles
 * deviennent des barres, et surtout les libellés qui reviennent partagent une
 * ligne : « Championnats » ×3 et « Vacances » ×3 se lisent alors comme le
 * rythme de la saison, ce qu'aucune grille ne montre.
 *
 * Pas de bibliothèque, et c'est un choix documenté dans #290 : une saison est
 * un intervalle FIXE et connu (1er septembre -> 30 juin), donc y positionner
 * une date n'est qu'une règle de trois. Les deux bibliothèques candidates
 * coûtaient soit une licence non-MIT (FullCalendar resource-timeline), soit
 * moment.js et sept dépendances (vis-timeline).
 */
export default {
    props: {
        /** Événements normalisés par `calendarData.adaptCalendarEvents()`. */
        events: { type: Array, default: () => [] },
        /** Saison affichée, '2025-2026'. */
        season: { type: String, required: true },
    },
    template: `
      <div class="w-full">
        <h2 class="text-2xl font-bold text-center text-primary mb-1">
          calendrier {{ season }}
        </h2>
        <p class="text-center text-sm opacity-60 mb-4">
          agenda de la commission
        </p>

        <div v-if="rows.length === 0" class="text-center opacity-60 py-6">
          Aucun événement au calendrier pour cette saison.
        </div>

        <!-- Défilement horizontal sur petit écran : dix mois ne tiennent pas
             sur un téléphone, mieux vaut faire glisser que compresser. -->
        <div v-else class="overflow-x-auto">
          <div class="min-w-[700px] pb-2">

            <div class="flex border-b border-base-300 mb-1">
              <div class="w-40 md:w-52 shrink-0"></div>
              <div class="relative flex-1 h-6">
                <div v-for="m in months" :key="m.key"
                     class="absolute top-0 h-6 text-[11px] text-base-content/60 border-l border-base-300 pl-1 capitalize"
                     :style="{ left: m.left + '%', width: m.width + '%' }">{{ m.name }}</div>
              </div>
            </div>

            <div v-for="row in rows" :key="row.id"
                 class="flex items-center rounded hover:bg-base-200/60">
              <div class="w-40 md:w-52 shrink-0 pr-2 py-1 text-xs md:text-sm truncate"
                   :title="row.label">
                <span class="inline-block w-2 h-2 rounded-sm mr-1 align-middle"
                      :style="{ background: row.color }"></span>{{ row.label }}
              </div>

              <div class="relative flex-1 h-7">
                <div v-for="m in months" :key="row.id + m.key"
                     class="absolute inset-y-0 border-l border-base-200"
                     :style="{ left: m.left + '%' }"></div>

                <div v-if="todayLeft !== null"
                     class="absolute inset-y-0 border-l-2 border-error/70 z-20"
                     :style="{ left: todayLeft + '%' }"
                     title="aujourd'hui"></div>

                <div v-for="item in row.periods" :key="item.id"
                     class="absolute top-1.5 h-4 rounded text-[10px] text-white leading-4 px-1 overflow-hidden whitespace-nowrap z-10"
                     :style="{ left: item.left + '%', width: item.width + '%', background: row.color, minWidth: '4px' }"
                     :title="item.tooltip">{{ item.width > 7 ? item.title : '' }}</div>

                <div v-for="item in row.points" :key="item.id"
                     class="absolute top-2.5 w-2.5 h-2.5 rotate-45 border border-base-100 z-10"
                     :style="{ left: 'calc(' + item.left + '% - 5px)', background: row.color }"
                     :title="item.tooltip"></div>
              </div>
            </div>
          </div>
        </div>

        <p v-if="rows.length > 0" class="text-xs opacity-60 mt-3 text-center">
          Barre = période · losange = rendez-vous ponctuel. Survolez pour le détail.
        </p>
      </div>
    `,
    computed: {
        /** Bornes de la saison : du 1er septembre au 30 juin inclus. */
        bounds() {
            const start = seasonStart(this.season);
            const endYear = Number(this.season.slice(0, 4)) + 1;
            return { start, end: endYear + '-07-01' };
        },
        totalDays() {
            return this.daysBetween(this.bounds.start, this.bounds.end);
        },
        /** Les dix mois de la saison, positionnés en pourcentage. */
        months() {
            const out = [];
            const first = new Date(this.bounds.start + 'T00:00:00');
            for (let i = 0; i < 10; i += 1) {
                const from = new Date(first.getFullYear(), first.getMonth() + i, 1);
                const to = new Date(first.getFullYear(), first.getMonth() + i + 1, 1);
                out.push({
                    key: 'm' + i,
                    name: from.toLocaleDateString('fr-FR', { month: 'short' }),
                    left: this.position(toIso(from)),
                    width: (this.daysBetween(toIso(from), toIso(to)) / this.totalDays) * 100,
                });
            }
            return out;
        },
        /** Repère « aujourd'hui », seulement si la date du jour est dans la saison. */
        todayLeft() {
            const today = toIso(new Date());
            if (today < this.bounds.start || today >= this.bounds.end) {
                return null;
            }
            return this.position(today);
        },
        /**
         * Une ligne par libellé de période, plus une ligne unique pour tous les
         * rendez-vous d'un seul jour.
         *
         * Sans ce regroupement, les six réunions de la saison occupaient six
         * lignes portant un point chacune, pour un écran deux fois plus haut.
         */
        rows() {
            const byId = new Map();
            const order = [];

            for (const event of this.events) {
                const isPoint = event.endDate === event.startDate;
                const id = isPoint ? '_rdv' : 'p-' + event.title;
                if (!byId.has(id)) {
                    byId.set(id, {
                        id,
                        label: isPoint ? 'Réunions et rendez-vous' : event.title,
                        isPoint,
                        periods: [],
                        points: [],
                        firstDate: event.startDate,
                    });
                    order.push(id);
                }
                const row = byId.get(id);
                const item = this.decorate(event);
                if (isPoint) {
                    row.points.push(item);
                } else {
                    row.periods.push(item);
                }
                if (event.startDate < row.firstDate) {
                    row.firstDate = event.startDate;
                }
            }

            // Ordre de lecture : les périodes dans l'ordre de leur première
            // occurrence — c'est le fil de la saison — et les rendez-vous en
            // dernier, puisqu'ils s'égrènent d'un bout à l'autre.
            const rows = order.map((id) => byId.get(id));
            rows.sort((a, b) => {
                if (a.isPoint !== b.isPoint) {
                    return a.isPoint ? 1 : -1;
                }
                return a.firstDate.localeCompare(b.firstDate);
            });

            return rows.map((row, i) => ({ ...row, color: this.colorAt(i) }));
        },
    },
    methods: {
        /**
         * Palette de lignes. Reprend l'esprit du composant remplacé, qui donnait
         * une couleur par type d'événement : la couleur reste un repère de
         * lecture quand plusieurs barres s'alignent verticalement.
         */
        colorAt(index) {
            const palette = [
                '#4f46e5', '#0891b2', '#7c3aed', '#ea580c', '#dc2626',
                '#059669', '#db2777', '#0d9488', '#ca8a04', '#2563eb',
            ];
            return palette[index % palette.length];
        },
        daysBetween(fromIso, toIsoDate) {
            const ms = new Date(toIsoDate + 'T00:00:00') - new Date(fromIso + 'T00:00:00');
            return Math.round(ms / 86400000);
        },
        /** Position d'une date dans la saison, en pourcentage de largeur. */
        position(iso) {
            const pct = (this.daysBetween(this.bounds.start, iso) / this.totalDays) * 100;
            return Math.min(100, Math.max(0, pct));
        },
        decorate(event) {
            const detail = eventDetail(event);
            // Fin INCLUSIVE : une période du 3 au 10 occupe huit jours, pas sept.
            const span = this.daysBetween(event.startDate, event.endDate) + 1;
            const left = this.position(event.startDate);
            // La largeur est bornée par la place restante : un événement mal
            // daté — saisi en juillet, ou avant le 1er septembre — verrait
            // sinon sa barre déborder du conteneur et casser la mise en page.
            const width = Math.max(
                0,
                Math.min((span / this.totalDays) * 100, 100 - left),
            );
            return {
                id: event.id,
                title: event.title,
                left,
                width,
                tooltip: event.title
                    + (detail ? '\n' + detail : '')
                    + '\n' + this.frenchDate(event.startDate)
                    + (event.endDate !== event.startDate
                        ? ' au ' + this.frenchDate(event.endDate)
                        : ''),
            };
        },
        frenchDate(iso) {
            const [y, m, d] = iso.split('-');
            return d + '/' + m + '/' + y;
        },
    },
};
