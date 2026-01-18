export default {
    template: `
      <div v-if="commissionMember" class="alert alert-info">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 shrink-0 stroke-current" fill="none" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
        </svg>
        <span><strong>{{ commissionMember.prenom }} {{ commissionMember.nom }}</strong> - {{ commissionMember.fonction }}</span>
      </div>
    `,
    props: {
        code_competition: {
            type: String,
            required: true,
        },
        division: {
            type: String,
            required: true,
        },
    },
    data() {
        return {
            commissionMember: null,
        };
    },
    watch: {
        code_competition: {
            handler() {
                this.fetch();
            },
            immediate: true
        },
        division: {
            handler() {
                this.fetch();
            },
        },
    },
    methods: {
        fetch() {
            axios
                .get(`/rest/action.php/commission/getByDivision?code_competition=${this.code_competition}&division=${this.division}`)
                .then((response) => {
                    this.commissionMember = response.data.length > 0 ? response.data[0] : null;
                })
                .catch((error) => {
                    console.error("Erreur lors du chargement du membre de commission :", error);
                    this.commissionMember = null;
                });
        },
    },
};
