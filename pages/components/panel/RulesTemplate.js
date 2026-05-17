export function createRulesComponent(config) {
    const { title, subtitle, lastUpdate, articles } = config;
    return {
        data() {
            return {
                title,
                subtitle: subtitle || '',
                lastUpdate,
                articles,
            };
        },
        template: `
      <div class="container mx-auto p-4">
        <div class="bg-base-100 shadow-xl rounded-lg p-4 mb-6">
          <h1 class="text-3xl font-bold text-center">{{ title }}</h1>
          <h2 v-if="subtitle" class="text-xl text-center mt-2">{{ subtitle }}</h2>
          <h3 class="text-lg text-center mt-2">
            Dernière mise à jour le : <span class="font-bold">{{ lastUpdate }}</span>
          </h3>
        </div>
        <table
            class="mb-6 table w-full bg-base-100 border border-base-300 divide-y divide-base-300 [&_td:first-child::first-letter]:font-bold [&_td:first-child::first-letter]:uppercase">
          <tr>
            <th>Récapitulatif</th>
            <th>Article</th>
          </tr>
          <tr v-for="article in articles" :key="'recap-' + article.num">
            <td>{{ article.title }}</td>
            <td>
              <a :href="'#article-' + article.num" class="link link-primary">{{ article.num }}</a>
            </td>
          </tr>
        </table>
        <div class="grid grid-cols-[repeat(auto-fit,minmax(320px,1fr))] gap-4">
          <div
              v-for="article in articles"
              :key="article.num"
              :id="'article-' + article.num"
              class="bg-base-200 rounded-xl p-4">
            <h4 class="font-bold text-lg mb-2">Article {{ article.num }} : {{ article.title }}</h4>
            <p v-for="(paragraph, idx) in article.content" :key="idx" class="mb-2 last:mb-0">
              {{ paragraph }}
            </p>
          </div>
        </div>
      </div>
    `,
    };
}
