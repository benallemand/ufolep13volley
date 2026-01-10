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
        url: '/rest/action.php/player/savePlayer',
        autoScroll: true,
        layout: 'anchor',
        items: [
            {
                xtype: 'image',
                src: '',
                anchor: null,
                width: 80,
                height: 100
            },
            {
                xtype: 'hidden',
                name: 'id',
                fieldLabel: 'Id',
                msgTarget: 'under'
            },
            {
                xtype: 'hidden',
                name: 'id_team',
                fieldLabel: 'IdTeam',
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
                msgTarget: 'under'
            },
            {
                xtype: 'datefield',
                name: 'date_homologation',
                fieldLabel: "Date d'homologation",
                format: 'd/m/Y',
                startDay: 1,
                msgTarget: 'under'
            },
            {
                xtype: 'combo',
                name: 'sexe',
                fieldLabel: 'Sexe',
                allowBlank: false,
                store: Ext.create('Ext.data.Store', {
                    fields: ['abbr', 'name'],
                    data: [
                        {"abbr": "M", "name": "Masculin"},
                        {"abbr": "F", "name": "Féminin"}
                    ]
                }),
                queryMode: 'local',
                displayField: 'name',
                valueField: 'abbr',
                forceSelection: true,
                msgTarget: 'under'
            },
            {
                name: 'departement_affiliation',
                fieldLabel: "Département d'affiliation",
                xtype: 'combo',
                allowBlank: false,
                store: {type: 'Departements'},
                queryMode: 'local',
                displayField: 'name',
                valueField: 'abbr',
                forceSelection: true,
                msgTarget: 'under'
            },
            {
                xtype: 'fieldset',
                title: 'Club',
                layout: 'anchor',
                items: [
                    {
                        xtype: 'label',
                        text: "A modifier uniquement si le joueur vient d'un autre club (prêt, changement de club)",
                        anchor: '100%'
                    },
                    {
                        name: 'id_club',
                        fieldLabel: 'Club',
                        xtype: 'combo',
                        queryMode: 'local',
                        store: {type: 'Clubs'},
                        displayField: 'nom',
                        valueField: 'id',
                        msgTarget: 'under',
                        anchor: '100%',
                        forceSelection: true
                    }
                ]
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
                msgTarget: 'under',
                uncheckedValue: 'off'
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
                action: 'cancel',
            }
        ]
    }
});