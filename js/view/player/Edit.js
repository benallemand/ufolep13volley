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
                fieldLabel: 'Pr�nom',
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
                fieldLabel: 'Num�ro de licence',
                minLength: 8,
                maxLength: 8,
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
                        {"abbr": "F", "name": "F�minin"}
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
                fieldLabel: "D�partement d'affiliation",
                xtype: 'combo',
                allowBlank: false,
                store: Ext.create('Ext.data.Store', {
                    fields: [{name: 'abbr', type: 'int'}, 'name'],
                    data: [
                        {"abbr": 13, "name": "Bouches du Rh�ne"},
                        {"abbr": 84, "name": "Vaucluse"},
                        {"abbr": 83, "name": "Var"},
                        {"abbr": 0, "name": "Autres"}
                    ]
                }),
                queryMode: 'local',
                displayField: 'name',
                valueField: 'abbr',
                forceSelection: true,
                msgTarget: 'under'
            },
            {
                name: 'est_actif',
                xtype: 'checkboxfield',
                fieldLabel: 'Actif ?',
                boxLabel: 'Oui',
                msgTarget: 'under'
            },
            {
                xtype: 'fieldset',
                title: 'Club',
                layout: 'anchor',
                items: [
                    {
                        xtype: 'label',
                        text: "A modifier uniquement si le joueur vient d'un autre club (pr�t, changement de club)",
                        anchor: '100%'
                    },
                    {
                        name: 'id_club',
                        fieldLabel: 'Club',
                        xtype: 'combo',
                        queryMode: 'local',
                        store: 'Clubs',
                        displayField: 'nom',
                        valueField: 'id',
                        msgTarget: 'under',
                        anchor: '100%'
                    }
                ]
            },
            {
                name: 'show_photo',
                xtype: 'checkboxfield',
                fieldLabel: 'Diffusion photo autoris�e ?',
                boxLabel: 'Oui',
                msgTarget: 'under'
            },
            {
                name: 'photo',
                xtype: 'filefield',
                fieldLabel: 'Photo',
                buttonText: 'S�lection Photo...',
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
                title: 'D�tails',
                layout: 'anchor',
                defaults: {
                    xtype: 'textfield',
                    anchor: '90%'
                },
                items: [
                    {
                        name: 'telephone',
                        fieldLabel: 'Num�ro de t�l�phone',
                        msgTarget: 'under'
                    },
                    {
                        name: 'email',
                        fieldLabel: 'Email',
                        msgTarget: 'under'
                    },
                    {
                        name: 'telephone2',
                        fieldLabel: 'Num�ro de t�l�phone secondaire',
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
                handler: function () {
                    this.up('window').close();
                }
            }
        ]
    }
});