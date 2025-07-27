export default {
    components: {
        'news': () => import('../table/News.js'),
        'photos': () => import('../carousel/Photos.js'),
    },
    template: `
      <div class="flex flex-col items-center">
        <news/>
        <photos/>
      </div>
    `,
    data() {
        return {};
    },
    computed: {}
};
