/**
 * Tiroir de detail d'une ligne de grille (issue #308).
 *
 * Les vues SQL renvoient beaucoup plus de colonnes que les grilles n'en
 * affichent. Avant ce tiroir, le seul moyen de lire un champ absent de la
 * grille etait d'ouvrir la fenetre d'edition — un formulaire d'ecriture
 * utilise comme liseuse, avec le risque d'enregistrer sans le vouloir.
 *
 * Il **flotte par-dessus** le tableau au lieu de le pousser : retrecir une
 * grille de onze colonnes de 420 px la ferait defiler horizontalement. Et il
 * commence **sous la barre d'outils**, sinon il recouvre `Export`,
 * `Rafraichir` et les actions propres a l'ecran, qui sont alignees a droite.
 *
 * Sur mobile il devient une feuille ancree en bas : la grille y deborde deja
 * lateralement, la ligne est le seul point d'entree praticable.
 *
 * **Aucune valeur n'est rendue en `v-html`.** Les noms d'equipe et de joueur
 * sont saisis par des responsables sans filtrage a l'entree (cf. issue #292
 * pour les gabarits d'emails) : l'interpolation Vue les echappe, on ne
 * contourne pas.
 */
export default {
    props: {
        /**
         * Configuration normalisee par AdminGrid :
         * { title(row), subtitle?(row), badge?(row), image?(row), sections }
         * ou `sections` vaut [{ title, fields: [{ key, label, format? }] }].
         */
        detail: { type: Object, required: true },
        row: { type: Object, required: true },
        /** Navigation d'une ligne a l'autre sans refermer */
        hasPrev: { type: Boolean, default: false },
        hasNext: { type: Boolean, default: false },
        /** L'ecran sait-il editer ? (une grille de consultation n'a pas de save-url) */
        canEdit: { type: Boolean, default: false },
    },
    emits: ['close', 'prev', 'next', 'edit'],
    template: `
      <!--
        Deux boites : celle-ci se contente d'occuper la colonne de droite sur
        toute la hauteur du tableau, et le panneau qu'elle contient COLLE au
        defilement.

        Sans ca, le panneau s'etirait jusqu'en bas du tableau : avec « tout »
        par page et 3 650 lignes, ses boutons d'action se retrouvaient a des
        milliers de pixels du regard. Un panneau simplement fixe dans la fenetre
        reglerait le probleme mais recouvrirait la barre d'outils en haut de
        page ; colle dans une boite qui commence sous elle, il ne peut pas
        remonter plus haut qu'elle.
      -->
      <aside class="fixed inset-x-0 bottom-0 top-auto z-30
                    lg:absolute lg:inset-x-auto lg:inset-y-0 lg:right-0 lg:w-[420px]"
             role="dialog"
             aria-modal="false"
             :aria-label="title">
      <div class="max-h-[85vh] rounded-t-2xl border-t border-base-300
                  lg:sticky lg:top-2 lg:max-h-[calc(100vh-1rem)] lg:rounded-none lg:rounded-l-box
                  lg:border-t-0 lg:border-l
                  bg-base-100 shadow-2xl flex flex-col overflow-hidden">

        <!-- Poignee : repere de feuille sur mobile, inutile sur desktop -->
        <div class="lg:hidden flex justify-center pt-2.5 pb-1.5">
          <span class="w-11 h-1 rounded-full bg-base-300"></span>
        </div>

        <div class="p-4 border-b border-base-300 flex items-start gap-3">
          <img v-if="image"
               :src="'/' + image"
               :alt="title"
               class="w-14 h-14 flex-none rounded-full object-cover bg-base-200"/>
          <div class="flex-1 min-w-0">
            <div class="text-lg font-bold leading-6 break-words">{{ title }}</div>
            <div class="mt-1.5 flex items-center gap-2 flex-wrap">
              <span v-if="badge" :class="['badge badge-sm', badge.class]">{{ badge.label }}</span>
              <span v-if="subtitle" class="text-xs text-base-content/60">{{ subtitle }}</span>
            </div>
          </div>
          <div class="flex items-center gap-1 flex-none">
            <button class="btn btn-ghost btn-xs btn-square"
                    :disabled="!hasPrev"
                    title="Ligne précédente"
                    @click="$emit('prev')">
              <i class="fas fa-chevron-left"></i>
            </button>
            <button class="btn btn-ghost btn-xs btn-square"
                    :disabled="!hasNext"
                    title="Ligne suivante"
                    @click="$emit('next')">
              <i class="fas fa-chevron-right"></i>
            </button>
            <button class="btn btn-ghost btn-xs btn-square" title="Fermer" @click="$emit('close')">
              <i class="fas fa-xmark"></i>
            </button>
          </div>
        </div>

        <div class="flex-1 overflow-y-auto p-4 flex flex-col gap-5">
          <section v-for="(section, si) in sections" :key="si">
            <h2 v-if="section.title"
                class="mb-2 text-xs font-bold uppercase tracking-wide text-base-content/50">
              {{ section.title }}
            </h2>
            <dl class="grid grid-cols-[minmax(7rem,9.5rem)_minmax(0,1fr)] gap-x-3 gap-y-2 text-sm">
              <template v-for="field in section.fields" :key="field.key">
                <dt class="text-base-content/60">{{ field.label }}</dt>
                <dd class="m-0 break-words">{{ value(field) }}</dd>
              </template>
            </dl>
          </section>
        </div>

        <div class="p-3 border-t border-base-300 flex flex-wrap gap-2">
          <button v-if="canEdit" class="btn btn-primary btn-sm" @click="$emit('edit')">
            <i class="fas fa-pen"></i> Éditer
          </button>
          <!-- Actions propres a l'ecran : la grille ne les connait pas. -->
          <slot name="detail-actions" :row="row"></slot>
        </div>
      </div>
      </aside>
    `,
    computed: {
        title() {
            return this.call(this.detail.title, '');
        },
        subtitle() {
            return this.call(this.detail.subtitle, '');
        },
        image() {
            return this.call(this.detail.image, '');
        },
        /**
         * `badge(row)` rend `{ label, tone }`, `tone` valant 'success',
         * 'error', 'warning' ou 'neutral'. On traduit ici en classe DaisyUI
         * pour que les ecrans n'aient pas a connaitre le vocabulaire CSS.
         */
        badge() {
            const badge = this.call(this.detail.badge, null);
            if (!badge || !badge.label) {
                return null;
            }
            const tones = {
                success: 'badge-success',
                error: 'badge-error',
                warning: 'badge-warning',
                neutral: 'badge-ghost',
            };
            return { label: badge.label, class: tones[badge.tone] || tones.neutral };
        },
        sections() {
            return this.detail.sections || [];
        },
    },
    methods: {
        call(fn, fallback) {
            return typeof fn === 'function' ? (fn(this.row) ?? fallback) : fallback;
        },
        /**
         * Une valeur vide s'affiche « — » et non pas rien : dans une liste de
         * champs, une cellule blanche se lit comme un defaut d'affichage. La
         * grille, elle, garde ses cellules vides — c'est son habitude.
         */
        value(field) {
            const raw = this.row[field.key];
            const rendered = field.format ? field.format(raw, this.row) : raw;
            return (rendered === null || rendered === undefined || rendered === '')
                ? '—'
                : rendered;
        },
    },
};
