Ext.define('Ufolep13Volley.view.team.SetMyTeamCaptain', {
    extend: 'Ext.window.Window',
    alias: 'widget.setmyteamcaptain',
    autoShow: true,
    title: "Modifier le responsable d'équipe",
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
        url: 'ajax/updateMyTeamCaptain.php',
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
                formBind: true,
                disabled: true
            }
        ]
    }
});