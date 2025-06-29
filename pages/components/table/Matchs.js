export default {
    template: `
      <table class="table mt-2 table-pin-rows bg-base-100">
        <thead>
        <tr>
          <th class="hidden md:table-cell">code</th>
          <th class="hidden md:table-cell">journée</th>
          <th class="hidden md:table-cell">date</th>
          <th></th>
          <th>résultat</th>
          <th></th>
          <th>score</th>
          <th class="hidden md:table-cell">commentaires</th>
        </tr>
        </thead>
        <tbody>
        <tr v-for="match in matchs" :key="match.id_match">
          <td class="hidden md:table-cell">{{ match.code_match }}</td>
          <td class="hidden md:table-cell">{{ match.nommage ? match.nommage : match.numero_journee }}</td>
          <td class="hidden md:table-cell">{{ match.date_reception }}</td>
          <td :class="match.score_equipe_dom === 3 ? 'bg-success/5':''">{{ match.equipe_dom }}</td>
          <td>
            <span :class="match.score_equipe_dom === 3 ? 'text-success':''">{{ match.score_equipe_dom }}</span>
            /
            <span :class="match.score_equipe_ext === 3 ? 'text-success':''">{{ match.score_equipe_ext }}</span>
          </td>
          <td :class="match.score_equipe_ext === 3 ? 'bg-success/5':''">{{ match.equipe_ext }}</td>
          <td>
                <span v-if="match.score_equipe_dom+match.score_equipe_ext >=1">{{ match.set_1_dom }}/
                  {{ match.set_1_ext }}</span>
            <span v-if="match.score_equipe_dom+match.score_equipe_ext >=2">{{ match.set_2_dom }}/
              {{ match.set_2_ext }}</span>
            <span v-if="match.score_equipe_dom+match.score_equipe_ext >=3">{{ match.set_3_dom }}/
              {{ match.set_3_ext }}</span>
            <span v-if="match.score_equipe_dom+match.score_equipe_ext >=4">{{ match.set_4_dom }}/
              {{ match.set_4_ext }}</span>
            <span v-if="match.score_equipe_dom+match.score_equipe_ext >=5">{{ match.set_5_dom }}/
              {{ match.set_5_ext }}</span>
          </td>
          <td class="hidden md:table-cell">{{ match.note }}</td>
        </tr>
        </tbody>
      </table>
    `, props: {
        fetchUrl: {
            type: String, required: true,
        }
    }, data() {
        return {
            matchs: [],
        };
    }, methods: {
        fetch() {
            axios
                .get(this.fetchUrl)
                .then((response) => {
                    this.matchs = response.data;
                })
                .catch((error) => {
                    console.error("Erreur lors du chargement des matchs :", error);
                });
        },
    }, created() {
        this.fetch();
    },
};