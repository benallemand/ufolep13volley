Ext.define('Ufolep13Volley.store.HallOfFame', {
    extend: 'Ext.data.Store',
    alias: 'store.HallOfFame',
    config: {
        model: 'Ufolep13Volley.model.HallOfFame',
        proxy: {
            type: 'ajax',
            url: '/rest/action.php/halloffame/getHallOfFame'
        },
        autoLoad: true
    }
});