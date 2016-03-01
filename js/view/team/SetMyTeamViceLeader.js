Ext.define('Ufolep13Volley.view.team.SetMyTeamViceLeader', {
    extend: 'Ext.window.Window',
    alias: 'widget.setmyteamviceleader',
    autoShow: true,
    title: "Modifier le Suppléant",
    height: 500,
    width: 500,
    modal: true,
    layout: 'fit',
    items: {
        xtype: 'form',
        layout: 'anchor',
        defaults: {
            anchor: '90%',
            margins: 10
        },
        url: 'ajax/updateMyTeamViceLeader.php',
        items: [
            {
                xtype: 'combo',
                forceSelection: true,
                fieldLabel: 'Joueur',
                name: 'id_joueur',
                queryMode: 'local',
                allowBlank: false,
                store: 'MyPlayers',
                displayField: 'full_name',
                valueField: 'id'
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
    }
});