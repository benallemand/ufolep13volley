import { defineAsyncComponent } from 'vue';
import { onError } from '../../../toaster.js';
import { adaptCalendarEvents, currentSeason } from '../calendar/calendarData.js';

/**
 * Écran « calendrier » du responsable (issue #290).
 *
 * Répond à la demande d'origine : voir au même endroit l'agenda de la
 * commission — celui de la page d'accueil — et les matchs de son équipe et de
 * son club.
 *
 * Deux composants plutôt qu'un, parce que les deux jeux de données n'ont pas la
 * même forme et ne se lisent pas de la même façon :
 *
 *  - l'agenda est fait d'une quinzaine de périodes longues : une timeline en
 *    donne le rythme d'un coup d'œil ;
 *  - les matchs sont nombreux et ponctuels : un calendrier les situe, une
 *    timeline les empilerait en un amas illisible.
 *
 * Chacun ne montre QUE ce qui le concerne : l'agenda n'est pas superposé au
 * calendrier des matchs, puisqu'il est déjà là, juste en dessous.
 */
export default {
    components: {
        'season-timeline': defineAsyncComponent(() => import('../calendar/SeasonTimeline.js')),
        'match-calendar': defineAsyncComponent(() => import('../calendar/MatchCalendar.js')),
    },
    template: `
      <div class="flex flex-col gap-8">
        <div class="bg-base-200 border border-2 border-base-300 p-4 rounded">
          <match-calendar/>
        </div>

        <div class="bg-base-200 border border-2 border-base-300 p-4 rounded">
          <season-timeline :events="agendaEvents" :season="season"/>
        </div>
      </div>
    `,
    data() {
        return {
            agendaEvents: [],
        };
    },
    computed: {
        season() {
            return currentSeason();
        },
    },
    created() {
        axios.get('/rest/action.php/calendarevents/getCalendarEvents',
            { params: { season: this.season } })
            .then(({ data }) => {
                this.agendaEvents = adaptCalendarEvents(Array.isArray(data) ? data : []);
            })
            .catch((error) => {
                this.agendaEvents = [];
                onError(this, error);
            });
    },
};
