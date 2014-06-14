Ext.define('Ufolep13Volley.view.team.PlayerAddToMyTeam', {
    extend: 'Ext.window.Window',
    alias: 'widget.playeraddtomyteam',
    autoShow: true,
    title: "Ajout d'un joueur",
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
        url: 'ajax/addPlayerToMyTeam.php',
        items: [
            {
                xtype: 'combo',
                forceSelection: true,
                fieldLabel: 'Joueur',
                name: 'id_joueur',
                queryMode: 'local',
                allowBlank: false,
                store: 'Players',
                displayField: 'full_name',
                valueField: 'id',
                listeners: {
                    select: function(combo, records) {
                        combo.up('form').down('image').setSrc(records[0].get('path_photo'));
                    }
                }
            },
            {
                xtype: 'image',
                anchor: '50%',
                margins: 10,
                src: null
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
        ],
        dockedItems: [
            {
                xtype: 'toolbar',
                docked: 'top',
                items: [
                    'Joueur Introuvable ?',
                    {
                        xtype: 'button',
                        text: 'Créer un joueur',
                        action: 'createPlayer'
                    }
                ]
            }
        ]
    }
});