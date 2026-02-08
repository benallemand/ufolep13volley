export default {
    template: `
      <div class="card bg-base-100 shadow-xl">
        <div class="card-body p-4">
          <div class="grid grid-cols-2 gap-2 text-sm">
            <div><i class="fas fa-calendar mr-1"></i> {{ match.date_reception || 'Non définie' }}</div>
            <div><i class="fas fa-clock mr-1"></i> {{ match.heure_reception || '' }}</div>
            <div class="col-span-2"><i class="fas fa-map-marker-alt mr-1"></i> {{ match.gymnasium || 'Non défini' }}</div>
          </div>
        </div>
      </div>
    `,
    props: {
        match: { type: Object, required: true }
    }
};
