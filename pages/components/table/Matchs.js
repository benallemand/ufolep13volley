export default {
    template: `
      <table class="table mt-2 table-pin-rows bg-base-100">
        <thead>
        <tr>
          <th>code</th>
          <th>date</th>
          <th></th>
          <th>résultat</th>
          <th></th>
          <th>score</th>
          <th>commentaires</th>
        </tr>
        </thead>
        <tbody>
        <tr v-for="match in matchs" :key="match.id_match">
          <td>
            <a class="link link-primary" :href="'/match.php?id_match=' + match.id_match"
               target="_blank">{{ match.code_match }}
            </a>
          </td>
          <td>{{ match.date_reception }} {{ match.heure_reception }}<span v-if="match.match_status == 'NOT_CONFIRMED'"
                                                                          class="ml-1 badge badge-warning">date non confirmée</span>
          </td>
          <td :class="match.score_equipe_dom === 3 ? 'bg-success/5':''">{{ match.equipe_dom }}</td>
          <td>
            <span :class="match.score_equipe_dom === 3 ? 'text-success':''">{{ match.score_equipe_dom }}</span>
            /
            <span :class="match.score_equipe_ext === 3 ? 'text-success':''">{{ match.score_equipe_ext }}</span>
          </td>
          <td :class="match.score_equipe_ext === 3 ? 'bg-success/5':''">{{ match.equipe_ext }}</td>
          <td>
            <span
                v-if="match.score_equipe_dom+match.score_equipe_ext >=1"><span>{{ match.set_1_dom }}</span>/<span>{{ match.set_1_ext }}</span></span>
            <span
                v-if="match.score_equipe_dom+match.score_equipe_ext >=2"><span>{{ match.set_2_dom }}</span>/<span>{{ match.set_2_ext }}</span></span>
            <span
                v-if="match.score_equipe_dom+match.score_equipe_ext >=3"><span>{{ match.set_3_dom }}</span>/<span>{{ match.set_3_ext }}</span></span>
            <span
                v-if="match.score_equipe_dom+match.score_equipe_ext >=4"><span>{{ match.set_4_dom }}</span>/<span>{{ match.set_4_ext }}</span></span>
            <span
                v-if="match.score_equipe_dom+match.score_equipe_ext >=5"><span>{{ match.set_5_dom }}</span>/<span>{{ match.set_5_ext }}</span></span>
          </td>
          <td>{{ match.note }}</td>
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
