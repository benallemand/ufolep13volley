export default {
    template: `
      <div class="bg-base-100">
        <div class="text-center mt-4">
          <div class="text-2xl font-bold text-primary">commission départementale</div>
        </div>
        <div class="text-center mt-4">
          <div class="text-lg text-secondary">les personnes membres de la Commission Technique Départementale
            Volley-Ball des Bouches du Rhône ainsi que les personnes qui aident le font à titre de bénévolat
          </div>
        </div>
        <table class="table table-pin-rows">
          <thead>
          <tr>
            <th>nom</th>
            <th>rôle</th>
            <th>divisions</th>
            <th>téléphone</th>
            <th>email</th>
          </tr>
          </thead>
          <tbody>
          <tr v-for="item in items" :key="item.id_commission">
            <td>{{ item.prenom }} {{ item.nom }}
              <p class="flex justify-center mt-1 mb-1"><img alt="photo manquante" :src="'/'+item.photo" class="max-h-[100px]" /></p>
            </td>
            <td>{{ item.type }} / {{ item.fonction }}</td>
            <td>
              <div class="flex flex-wrap gap-1">
                <span v-for="attr in formatAttribution(item.attribution)" :key="attr.raw" :class="['badge', attr.badgeClass]">
                  {{ attr.label }}
                </span>
              </div>
            </td>
            <td>{{ item.telephone1 }}</td>
            <td>{{ item.email }}</td>
          </tr>
          </tbody>
        </table>
      </div>
    `,
    data() {
        return {
            items: [],
            fetchUrl: "/rest/action.php/commission/get"
        };
    },
    methods: {
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
        formatAttribution(attribution) {
            if (!attribution) return [];
            const competitionMap = {
                'm': { name: 'Champ. Masculin', badge: 'badge-primary' },
                'f': { name: 'Champ. Féminin', badge: 'badge-secondary' },
                'mo': { name: 'Champ. Mixte', badge: 'badge-accent' },
                'c': { name: 'Coupe Isoardi', badge: 'badge-success' },
                'kh': { name: 'Coupe Khoury Hanna', badge: 'badge-warning' },
            };
            return attribution.split(',').map(attr => {
                const [code, div] = attr.trim().split('/');
                const comp = competitionMap[code] || { name: code.toUpperCase(), badge: 'badge-ghost' };
                return {
                    raw: attr,
                    label: `${comp.name} D${div}`,
                    badgeClass: comp.badge
                };
            });
        },
    },
    created() {
        this.fetch();
    },
};