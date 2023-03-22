Ext.define('Ufolep13Volley.controller.rank_for_cup', {
    extend: 'Ext.app.Controller',
    stores: [
        'rank_for_cup',
    ],
    models: [
        'rank_for_cup',
    ],
    views: [
        'grid.rank_for_cup',
    ],
    refs: [],
    init: function () {
        this.control({
            'rank_for_cup_grid': {
                added: function (grid) {
                    grid.getStore().load({
                        params: {
                            'code_competition': code_competition
                        }
                    });
                }
            },
        });
    },
});