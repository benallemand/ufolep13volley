import { MENU } from './AdminLayout.js';

/**
 * Barre latérale de navigation de l'administration (issue #265).
 *
 * Depuis le lot 6, elle liste TOUS les écrans : l'admin ExtJS a été supprimée,
 * et le lien « ancienne administration » avec elle.
 */
export default {
    props: {
        user: { type: Object, required: true },
    },
    template: `
      <aside class="bg-base-200 w-64 min-h-full flex flex-col">
        <div class="p-4 border-b border-base-300">
          <div class="font-bold text-lg">Administration</div>
          <div class="text-xs text-base-content/60 truncate" :title="user.login">{{ user.login }}</div>
        </div>

        <ul class="menu p-2 flex-1 overflow-y-auto flex-nowrap">
          <template v-for="group in menu" :key="group.label">
            <li class="menu-title">{{ group.label }}</li>
            <li v-for="item in group.items" :key="item.path || item.href">
              <router-link v-if="item.path" :to="item.path" active-class="active">
                <i :class="item.icon"></i> {{ item.label }}
              </router-link>
              <!-- Entrée Vite à part (admin/matches.html) : lien classique. -->
              <a v-else :href="item.href">
                <i :class="item.icon"></i> {{ item.label }}
              </a>
            </li>
          </template>
        </ul>

        <div class="p-2 border-t border-base-300 space-y-1">
          <a href="/pages/home.html" class="btn btn-ghost btn-sm w-full justify-start">
            <i class="fas fa-home"></i> Retour au site
          </a>
        </div>
      </aside>
    `,
    data() {
        return { menu: MENU };
    },
};
