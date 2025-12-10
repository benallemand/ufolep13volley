export const matchFilterMixin = {
    data() {
        return {
            searchQuery: "",
            filter: {
                showPlayedOnly: false,
                showNotPlayedOnly: false,
            },
        };
    },
    methods: {
        matchesSearchQuery(match) {
            const query = this.searchQuery.toLowerCase();
            if (!query) return true;
            return match.equipe_dom.toLowerCase().includes(query) ||
                match.equipe_ext.toLowerCase().includes(query) ||
                match.code_match.toLowerCase().includes(query);
        },
        matchesPlayedFilter(match) {
            const isPlayed = match.is_match_score_filled === 1 || 
                (match.score_equipe_dom + match.score_equipe_ext) > 0;
            if (this.filter.showPlayedOnly && !isPlayed) return false;
            if (this.filter.showNotPlayedOnly && isPlayed) return false;
            return true;
        },
        applyBaseFilters(match) {
            return this.matchesSearchQuery(match) && this.matchesPlayedFilter(match);
        },
        resetBaseFilters() {
            this.searchQuery = "";
            this.filter.showPlayedOnly = false;
            this.filter.showNotPlayedOnly = false;
        },
    },
};

export const filterBarTemplate = `
<div class="flex flex-wrap gap-4 mb-4">
    <input
        type="text"
        v-model="searchQuery"
        placeholder="Rechercher (équipe, code)..."
        class="input input-bordered input-sm flex-grow"
    />
    <label class="flex items-center gap-2">
        <input
            type="checkbox"
            v-model="filter.showPlayedOnly"
            class="checkbox checkbox-sm checkbox-primary"
        />
        <span class="text-sm">Joués</span>
    </label>
    <label class="flex items-center gap-2">
        <input
            type="checkbox"
            v-model="filter.showNotPlayedOnly"
            class="checkbox checkbox-sm checkbox-primary"
        />
        <span class="text-sm">Non joués</span>
    </label>
    <button @click="resetFilters" class="btn btn-outline btn-sm">Réinitialiser</button>
</div>
`;
