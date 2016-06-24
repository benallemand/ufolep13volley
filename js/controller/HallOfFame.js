Ext.define('Ufolep13Volley.controller.HallOfFame', {
    extend: 'Ext.app.Controller',
    stores: ['HallOfFame'],
    models: ['HallOfFame'],
    views: [
        'grid.HallOfFame'
    ],
    refs: [],
    init: function () {
        this.control(
            {
                'menuitem[action=showHallOfFame]': {
                    click: this.showHallOfFame
                }
            });
    },
    showHallOfFame: function () {
        Ext.create('Ext.window.Window', {
            title: 'Palmarès',
            maximizable: true,
            modal: true,
            width: 800,
            height: 500,
            layout: 'fit',
            items: {
                xtype: 'hall_of_fame_grid'
            }

        }).show();
    }
});