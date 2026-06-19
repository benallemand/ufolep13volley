// Cellule "podium" : champion (or) au-dessus du vice-champion (argent), + lien diplômes.
const PodiumCell = {
    props: ['label', 'champion', 'vice', 'ids'],
    computed: {
        // `ids` = "idChampion,idVice" → un id par équipe pour le téléchargement individuel
        championId() {
            return this.ids ? String(this.ids).split(',')[0] : null;
        },
        viceId() {
            return this.ids ? String(this.ids).split(',')[1] : null;
        },
    },
    methods: {
        diplomaUrl(id) {
            return '/rest/action.php/halloffame/download_diploma?ids=' + id;
        },
    },
    template: `
      <div class="flex-1 min-w-0">
        <div v-if="label" class="text-[10px] uppercase tracking-wide text-base-content/50 text-center mb-1">
          {{ label }}
        </div>
        <div v-if="champion"
             class="flex items-center gap-1 rounded bg-amber-100 border-l-4 border-amber-400 px-2 py-1">
          <span>🥇</span>
          <span class="text-xs font-semibold truncate flex-1" :title="champion">{{ champion }}</span>
          <a v-if="championId" :href="diplomaUrl(championId)" target="_blank"
             title="Télécharger le diplôme" class="shrink-0 text-primary/70 hover:text-primary">📄</a>
        </div>
        <div v-if="vice"
             class="flex items-center gap-1 rounded bg-slate-100 border-l-4 border-slate-300 px-2 py-1 mt-1">
          <span>🥈</span>
          <span class="text-xs truncate flex-1" :title="vice">{{ vice }}</span>
          <a v-if="viceId" :href="diplomaUrl(viceId)" target="_blank"
             title="Télécharger le diplôme" class="shrink-0 text-primary/70 hover:text-primary">📄</a>
        </div>
      </div>
    `,
};

// Corps d'un groupe (saison ou année de coupe) : sections par ligue, puis cartes-division.
const SeasonBody = {
    components: { PodiumCell },
    props: ['leagues'],
    template: `
      <div class="space-y-6">
        <div v-for="lg in leagues" :key="lg.league">
          <h4 class="text-sm font-bold uppercase tracking-wide text-base-content/70 border-b border-base-300 pb-1 mb-3">
            {{ lg.league }}
          </h4>

          <!-- Coupe : un seul podium, pas de division -->
          <div v-if="lg.type === 'cup'" class="max-w-xs">
            <div class="card bg-base-100 border border-base-300 shadow-sm">
              <div class="card-body p-3">
                <podium-cell :champion="lg.champion" :vice="lg.vice" :ids="lg.ids"></podium-cell>
              </div>
            </div>
          </div>

          <!-- Championnat : grille de cartes-division, phase 1 / phase 2 -->
          <div v-else class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-3">
            <div v-for="d in lg.divisions" :key="d.division"
                 class="card bg-base-100 border border-base-300 shadow-sm">
              <div class="card-body p-3 gap-2">
                <div class="text-center font-bold text-primary text-sm">Division {{ d.division }}</div>
                <div class="flex gap-2">
                  <podium-cell label="1ère demi-saison" :champion="d.p1 && d.p1.champion" :vice="d.p1 && d.p1.vice"
                               :ids="d.p1 && d.p1.ids"></podium-cell>
                  <div class="w-px bg-base-300"></div>
                  <podium-cell label="2e demi-saison" :champion="d.p2 && d.p2.champion" :vice="d.p2 && d.p2.vice"
                               :ids="d.p2 && d.p2.ids"></podium-cell>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    `,
};

export default {
    components: { SeasonBody },
    template: `
      <div class="w-full p-4 space-y-6">
        <h2 class="text-2xl font-bold text-center text-primary">Palmarès</h2>

        <p v-if="!items.length" class="text-center text-base-content/50">Chargement…</p>

        <!-- Dernière saison : mise en avant -->
        <div v-if="latestGroup" class="rounded-2xl border-2 border-primary/40 bg-primary/5 p-4 shadow-md">
          <div class="flex items-center justify-center gap-2 mb-3">
            <span class="text-2xl">🏆</span>
            <h3 class="text-xl font-bold text-primary">{{ latestGroup.label }}</h3>
            <span class="badge badge-primary badge-sm">dernière saison</span>
          </div>
          <div v-if="latestGroup.allIds" class="flex justify-center mb-4">
            <a :href="'/rest/action.php/halloffame/download_diploma?ids=' + latestGroup.allIds"
               target="_blank" class="btn btn-sm btn-primary gap-1">
              📄 Télécharger tous les diplômes de la saison
            </a>
          </div>
          <season-body :leagues="latestGroup.leagues"></season-body>
        </div>

        <!-- Saisons / coupes précédentes : accordéons repliés -->
        <div v-if="olderGroups.length" class="space-y-2">
          <h3 class="text-sm font-semibold text-base-content/60 px-1">Saisons précédentes</h3>
          <div v-for="grp in olderGroups" :key="grp.period"
               class="collapse collapse-arrow border border-base-300 bg-base-100 rounded-box">
            <input type="checkbox"/>
            <div class="collapse-title font-semibold">{{ grp.label }}</div>
            <div class="collapse-content">
              <div v-if="grp.allIds" class="flex justify-end pt-2">
                <a :href="'/rest/action.php/halloffame/download_diploma?ids=' + grp.allIds"
                   target="_blank" class="btn btn-xs btn-outline btn-primary gap-1">
                  📄 Tous les diplômes
                </a>
              </div>
              <season-body :leagues="grp.leagues" class="pt-2"></season-body>
            </div>
          </div>
        </div>
      </div>
    `,
    data() {
        return {
            items: [],
            fetchUrl: "/rest/action.php/halloffame/getHallOfFameDisplay",
        };
    },
    computed: {
        groups() {
            const leagueOrder = {
                'Championnat Féminin': 1,
                'Championnat Masculin': 2,
                'Championnat Mixte': 3,
            };
            // Les coupes (période = année N) sont rattachées à la saison (N-1)-N,
            // car elles se jouent en fin de saison.
            const seasonKey = (period) => {
                if (/^\d{4}$/.test(period)) {
                    const y = parseInt(period, 10);
                    return `${y - 1}-${y}`;
                }
                return period;
            };
            // 1) Regrouper par saison
            const bySeason = {};
            for (const it of this.items) {
                const key = seasonKey(it.period);
                (bySeason[key] = bySeason[key] || []).push(it);
            }
            // 2) Construire chaque groupe-saison
            const seasons = Object.keys(bySeason).sort((a, b) => (a < b ? 1 : a > b ? -1 : 0));
            return seasons.map((period) => {
                const rows = bySeason[period];
                // Regrouper par ligue
                const leaguesMap = {};
                for (const r of rows) {
                    (leaguesMap[r.league] = leaguesMap[r.league] || []).push(r);
                }
                const leagues = Object.keys(leaguesMap)
                    .sort((a, b) => (leagueOrder[a] || 99) - (leagueOrder[b] || 99) || a.localeCompare(b))
                    .map((league) => {
                        const lrows = leaguesMap[league];
                        const isCup = lrows.every((r) => !r.division);
                        if (isCup) {
                            const r = lrows[0];
                            return { league, type: 'cup', champion: r.champion, vice: r.vice_champion, ids: r.ids };
                        }
                        // Championnat : regrouper par division avec phase 1 / phase 2
                        const divMap = {};
                        for (const r of lrows) {
                            const d = (divMap[r.division] = divMap[r.division] || { division: r.division });
                            const phase = Number(r.demi_saison) === 1 ? 'p1' : 'p2';
                            d[phase] = { champion: r.champion, vice: r.vice_champion, ids: r.ids };
                        }
                        const divisions = Object.values(divMap).sort((a, b) => this.compareDivisions(a.division, b.division));
                        return { league, type: 'champ', divisions };
                    });
                // Tous les ids (champion + vice) de la saison, pour le téléchargement groupé
                const allIds = rows.map((r) => r.ids).filter(Boolean).join(',');
                return { period, label: `Saison ${period}`, leagues, allIds };
            });
        },
        latestGroup() {
            // La saison la plus récente est mise en avant
            return this.groups[0] || null;
        },
        olderGroups() {
            return this.groups.filter((g) => g !== this.latestGroup);
        },
    },
    methods: {
        compareDivisions(a, b) {
            const na = parseInt(a, 10);
            const nb = parseInt(b, 10);
            if (na !== nb) return na - nb;
            return String(a).localeCompare(String(b)); // ex. 7a avant 7b
        },
        fetch() {
            axios
                .get(this.fetchUrl)
                .then((response) => {
                    this.items = response.data;
                })
                .catch((error) => {
                    console.error("Erreur lors du chargement:", error);
                });
        },
    },
    created() {
        this.fetch();
    },
};
