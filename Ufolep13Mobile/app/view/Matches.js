Ext.define('Ufolep13.view.Matches', {
    extend: 'Ext.dataview.List',
    requires: [
        'Ext.dataview.List'
    ],
    xtype: 'listmatches',
    config: {
        itemTpl: '{equipe_domicile} {score_equipe_dom}-{score_equipe_ext} {equipe_exterieur}',
        grouped: true,
        store: 'Matches',
        flex: 1
    }
});
