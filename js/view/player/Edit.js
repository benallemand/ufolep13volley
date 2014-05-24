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
        trackResetOnLoad: true,
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
                fieldLabel: 'Id',
                msgTarget: 'under'
            },
            {
                name: 'prenom',
                fieldLabel: 'Prénom',
                allowBlank: false,
                msgTarget: 'under'
            },
            {
                name: 'nom',
                fieldLabel: 'Nom',
                allowBlank: false,
                msgTarget: 'under'
            },
            {
                name: 'num_licence',
                fieldLabel: 'Numéro de licence',
                minLength: 8,
                maxLength: 8,
                allowBlank: false,
                msgTarget: 'under'
            },
            {
                name: 'sexe',
                fieldLabel: 'Sexe',
                allowBlank: false,
                maskRe : /[MF]{1}/,
                minLength: 1,
                maxLength: 1,
                msgTarget: 'under'                
            },
            {
                name: 'departement_affiliation',
                fieldLabel: "Département d'affiliation",
                xtype: 'numberfield',
                allowBlank: false,
                msgTarget: 'under'
            },
            {
                name: 'est_actif',
                xtype: 'checkboxfield',
                fieldLabel: 'Actif ?',
                msgTarget: 'under'
            },
            {
                name: 'id_club',
                fieldLabel: 'Club',
                xtype: 'combo',
                store: 'Clubs',
                displayField: 'nom',
                valueField: 'id',
                msgTarget: 'under'
            },
            {
                name: 'date_homologation',
                xtype: 'datefield',
                format: 'd/m/Y',
                fieldLabel: "Date d'homologation",
                msgTarget: 'under'
            },
            {
                name: 'show_photo',
                xtype: 'checkboxfield',
                fieldLabel: 'Diffusion photo autorisée ?',
                msgTarget: 'under'
            },
            {
                name: 'photo',
                xtype: 'filefield',
                fieldLabel: 'Photo',
                buttonText: 'Sélection Photo...',
                msgTarget: 'under'
            },
            {
                name: 'team_leader_list',
                xtype: 'displayfield',
                fieldLabel: 'Capitaine de',
                msgTarget: 'under'
            },
            {
                name: 'teams_list',
                xtype: 'displayfield',
                fieldLabel: 'Membre de',
                msgTarget: 'under'
            },
            {
                name: 'est_responsable_club',
                xtype: 'checkboxfield',
                fieldLabel: 'Responsable ?',
                msgTarget: 'under'
            },
            {
                xtype: 'fieldset',
                title: 'Détails',
                layout: 'anchor',
                defaults: {
                    xtype: 'textfield',
                    anchor: '90%'
                },
                items: [
                    {
                        name: 'telephone',
                        fieldLabel: 'Numéro de téléphone',
                        msgTarget: 'under'
                    },
                    {
                        name: 'email',
                        fieldLabel: 'Email',
                        msgTarget: 'under'
                    },
                    {
                        name: 'telephone2',
                        fieldLabel: 'Numéro de téléphone secondaire',
                        msgTarget: 'under'
                    },
                    {
                        name: 'email2',
                        fieldLabel: 'Email secondaire',
                        msgTarget: 'under'
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