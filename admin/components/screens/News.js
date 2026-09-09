import { defineAsyncComponent } from 'vue';

/**
 * Gestion des news (issue #265, lot 4).
 * Remplace `js/view/news/AdminGrid.js` + `js/view/news/Edit.js`.
 *
 * Deux particularités par rapport aux autres écrans :
 * - `news.news_date` est stockée et rendue en **ISO** (`getAllNews` fait
 *   `DATE_FORMAT(..., '%Y-%m-%d')`), pas en `jj/mm/aaaa` comme partout
 *   ailleurs, d'où `dateFormat: 'iso'` ;
 * - `News::deleteNews($id)` ne prend **qu'un** identifiant, d'où
 *   `delete-mode="id"` : la grille enchaîne un appel par ligne sélectionnée.
 */
export default {
    components: {
        'admin-grid': defineAsyncComponent(() => import('../grid/AdminGrid.js')),
    },
    template: `
      <admin-grid
        title="Gestion des news"
        entity-label="news"
        :columns="columns"
        :fields="fields"
        delete-mode="id"
        fetch-url="/rest/action.php/news/getAllNews"
        save-url="/rest/action.php/news/saveNews"
        delete-url="/rest/action.php/news/deleteNews"/>
    `,
    data() {
        return {
            columns: [
                { key: 'news_date', label: 'Date' },
                { key: 'title', label: 'Titre' },
                {
                    key: 'text', label: 'Texte',
                    // Certaines news font plusieurs paragraphes : la grille en
                    // montre le début, l'édition affiche le tout.
                    format: (v) => {
                        const s = String(v ?? '');
                        return s.length > 160 ? s.slice(0, 160) + '…' : s;
                    },
                },
                { key: 'file_path', label: 'Fichier' },
                {
                    key: 'is_disabled', label: 'Publiée',
                    format: (v) => (Number(v) ? 'non' : 'oui'),
                    badge: (r) => 'badge badge-sm ' + (Number(r.is_disabled) ? 'badge-ghost' : 'badge-success'),
                },
            ],
            fields: [
                { name: 'title', label: 'Titre', required: true },
                { name: 'text', label: 'Texte', type: 'textarea' },
                { name: 'file_path', label: 'Chemin du fichier', help: 'Relatif à news_files/, laisser vide si aucun' },
                { name: 'news_date', label: 'Date', type: 'date', dateFormat: 'iso', required: true },
                { name: 'is_disabled', label: 'Désactivée (masquée de la home) ?', type: 'checkbox' },
            ],
        };
    },
};
