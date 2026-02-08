export default {
    template: `
      <div>
        <div class="text-center mb-6">
          <h2 class="text-2xl font-bold mb-2">Matchs en direct</h2>
          <p class="text-gray-500">Sélectionnez un match pour suivre le score</p>
        </div>
        
        <div v-if="activeLiveScores.length === 0" class="alert alert-info">
          <i class="fas fa-info-circle"></i>
          <span>Aucun match en direct actuellement.</span>
        </div>
        
        <div v-for="live in activeLiveScores" :key="live.id_match" class="card bg-base-100 shadow-xl mb-4">
          <div class="card-body p-4">
            <div class="flex justify-between items-center">
              <div>
                <p class="font-bold">{{ live.equipe_dom }} vs {{ live.equipe_ext }}</p>
                <p class="text-sm text-gray-500">{{ live.code_competition }} - Div {{ live.division }}</p>
              </div>
              <div class="text-center">
                <p class="text-2xl font-bold">{{ live.score_dom }} - {{ live.score_ext }}</p>
                <p class="text-xs">Set {{ live.set_en_cours }}</p>
              </div>
              <a :href="'/live.php?id_match=' + live.id_match" class="btn btn-primary btn-sm">
                <i class="fas fa-eye"></i> Voir
              </a>
            </div>
          </div>
        </div>
      </div>
    `,
    props: {
        activeLiveScores: { type: Array, required: true }
    }
};
