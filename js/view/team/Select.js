Ext.define('Ufolep13Volley.view.team.Select', {
    extend: 'Ext.window.Window',
    alias: 'widget.teamselect',
    title: 'Associer � une �quipe',
    height: 200,
    width: 750,
    modal: true,
    layout: 'fit',
    items: {
        xtype: 'form',
        layout: 'anchor',
        defaults: {
            anchor: '90%',
            margins: 10
        },
        url: 'ajax/addPlayersToTeam.php',
        items: [
            {
                xtype: 'hidden',
                name: 'id_players',
                allowBlank: false
            },
            {
                xtype: 'combo',
                allowBlank: false,
                forceSelection: true,
                fieldLabel: 'Equipe',
                name: 'id_team',
                queryMode: 'local',
                store: 'Teams',
                displayField: 'team_full_name',
                valueField: 'id_equipe'
            }
        ],
        buttons: [
            {
                text: 'Annuler',
                handler: function() {
                    this.up('window').close();
                }
            },
            {
                text: 'Sauver',
                action: 'save',
                formBind: true,
                disabled: true
            }
        ]
    },
    autoShow: true
});