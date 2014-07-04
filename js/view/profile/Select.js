Ext.define('Ufolep13Volley.view.profile.Select', {
    extend: 'Ext.window.Window',
    alias: 'widget.profileselect',
    title: 'Associer à un profil',
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
        url: 'ajax/addProfileToUsers.php',
        items: [
            {
                xtype: 'hidden',
                name: 'id_users',
                allowBlank: false
            },
            {
                xtype: 'combo',
                allowBlank: false,
                forceSelection: true,
                fieldLabel: 'Profil',
                name: 'id_profile',
                queryMode: 'local',
                store: 'Profiles',
                displayField: 'name',
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
    },
    autoShow: true
});