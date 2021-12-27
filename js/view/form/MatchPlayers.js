Ext.define('Ufolep13Volley.view.form.MatchPlayers', {
    extend: 'Ext.form.Panel',
    alias: 'widget.form_match_players',
    title: "Cocher les joueurs du match",
    layout: 'form',
    url: 'rest/action.php/manage_match_players',
    items: [
        {
            xtype: 'hidden',
            fieldLabel: 'id_match',
            name: 'id_match'
        },
        {
            xtype: 'tagfield',
            fieldLabel: 'Joueurs',
            name: 'player_ids[]',
            store: {
                type: 'Players'
            },
            queryMode: 'local',
            displayField: 'full_name',
            valueField: 'id',
            forceSelection: true
        }
    ],
    buttons: [
        {
            text: 'Annuler',
            handler: function () {
                this.up('window').close();
            }
        },
        {
            text: 'Sauver',
            formBind: true,
            disabled: true,
            action: 'save'
        }
    ]
});