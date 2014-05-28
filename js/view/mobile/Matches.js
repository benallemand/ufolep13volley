Ext.define('Ufolep13Volley.view.mobile.Matches', {
    extend: 'Ext.dataview.List',
    requires: [
        'Ext.dataview.List'
    ],
    xtype: 'listmatches',
    config: {
        itemTpl: '{competition} - {division_journee} | {equipe_domicile} vs {equipe_exterieur} | {score_equipe_dom}-{score_equipe_ext}',
        store: 'Matches',
        flex: 1
    }
});
