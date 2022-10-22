Ext.define('Ufolep13Volley.view.club.Select', {
    extend: 'Ext.window.Window',
    alias: 'widget.clubselect',
    title: 'Associer à un club',
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
        url: '/rest/action.php/player/addPlayersToClub',
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
                fieldLabel: 'Club',
                name: 'id_club',
                queryMode: 'local',
                store: 'Clubs',
                displayField: 'nom',
                valueField: 'id'
            }
        ],
        buttons: [
            {
                text: 'Annuler',
                action: 'cancel',
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