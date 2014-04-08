Ext.define('Ufolep13Mobile.view.Teams', {
    extend: 'Ext.dataview.List',
    requires: [
        'Ext.dataview.List'
    ],
    xtype: 'listteams',
    config: {
        itemTpl: '{nom_equipe}',
        grouped: true,
        store: 'Teams',
        flex: 1
    }
});
