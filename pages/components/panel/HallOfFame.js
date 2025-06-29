export default {
    components: {
        'hall-of-fame-table': () => import('../table/HallOfFame.js'),
    },
    template: `
      <hall-of-fame-table :key="hall-of-fame"></hall-of-fame-table>
    `,
};
