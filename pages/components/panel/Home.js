export default {
    components: {
        'news': () => import('../table/News.js')
    },
    template: `
      <div class="container mx-auto p-4">
        <news/>
      </div>
    `,
    data() {
        return {};
    },
    computed: {}
};
