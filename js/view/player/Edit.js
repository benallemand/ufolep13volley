Ext.define('Ufolep13Volley.view.player.Edit', {
    extend: 'Ext.window.Window',
    alias: 'widget.playeredit',
    title: 'Joueur',
    layout: 'fit',
    modal: true,
    width: 700,
    height: 500,
    autoShow: true,
    items: {
        xtype: 'form',
        defaults: {
            xtype: 'textfield',
            anchor: '90%'
        },
        url: 'ajax/savePlayer.php',
        autoScroll: true,
        layout: 'anchor',
        items: [
            {
                xtype: 'hidden',
                name: 'id',
                fieldLabel: 'Id'
            },
            {
                name: 'prenom',
                fieldLabel: 'Pr�nom',
                allowBlank: false
            },
            {
                name: 'nom',
                fieldLabel: 'Nom',
                allowBlank: false
            },
            {
                name: 'num_licence',
                fieldLabel: 'Num�ro de licence',
                minLength: 8,
                maxLength: 8,
                allowBlank: false
            },
            {
                name: 'sexe',
                fieldLabel: 'Sexe',
                allowBlank: false
            },
            {
                name: 'departement_affiliation',
                fieldLabel: 'D�partement',
                xtype: 'numberfield',
                allowBlank: false
            },
            {
                name: 'est_actif',
                xtype: 'checkboxfield',
                fieldLabel: 'Actif ?'
            },
            {
                name: 'id_club',
                fieldLabel: 'Club',
                xtype: 'combo',
                store: 'Clubs',
                displayField: 'nom',
                valueField: 'id'
            },
            {
                name: 'date_homologation',
                xtype: 'datefield',
                format: 'd/m/Y',
                fieldLabel: "Date d'homologation"
            },
            {
                name: 'photo',
                xtype: 'filefield',
                fieldLabel: 'Photo',
                buttonText: 'S�lection Photo...'
            },
            {
                name: 'est_responsable_club',
                xtype: 'checkboxfield',
                fieldLabel: 'Responsable ?',
                listeners: {
                    change: function(box, newVal, oldVal) {
                        var fieldsetDetails = Ext.ComponentQuery.query('fieldset[title=D�tails du responsable]')[0];
                        if (newVal) {
                            fieldsetDetails.show();
                        }
                        else {
                            fieldsetDetails.hide();
                        }
                    }
                }
            },
            {
                xtype: 'fieldset',
                title: 'D�tails du responsable',
                hidden: true,
                layout: 'anchor',
                defaults: {
                    xtype: 'textfield',
                    anchor: '90%'
                },
                items: [
                    {
                        name: 'telephone',
                        fieldLabel: 'Num�ro de t�l�phone'
                    },
                    {
                        name: 'email',
                        fieldLabel: 'Email'
                    },
                    {
                        name: 'telephone2',
                        fieldLabel: 'Num�ro de t�l�phone secondaire'
                    },
                    {
                        name: 'email2',
                        fieldLabel: 'Email secondaire'
                    }
                ]
            }
        ],
        buttons: [
            {
                text: 'Sauver',
                action: 'save',
                formBind: true,
                disabled: true
            },
            {
                text: 'Annuler',
                handler: function() {
                    this.up('window').close();
                }
            }
        ]
    }
});