Ext.define('Ufolep13Volley.view.form.player', {
    extend: 'Ext.form.Panel',
    alias: 'widget.form_player',
    title: "Fiche joueur/joueuse",
    layout: 'form',
    url: 'rest/action.php/player/update_player',
    trackResetOnLoad: true,
    defaults: {
        xtype: 'textfield',
        anchor: '95%',
        margin: '5 0 5 0',
    },
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
            msgTarget: 'under',
        },
        {
            xtype: 'label',
            html: "<small style='font-style: italic'>Le numéro de licence peut être laissé vide dans un premier temps, si vous ne le connaissez pas encore. Le numéro de licence doit comporter exactement 8 chiffres si renseigné.</small> ",
        },
        {
            xtype: 'datefield',
            readOnly: true,
            name: 'date_homologation',
            fieldLabel: "Date d'homologation",
            format: 'd/m/Y',
            startDay: 1,
            msgTarget: 'under',
            readOnly: true,
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
        },
        {
            xtype: 'label',
            html: "<small style='font-style: italic'>A modifier uniquement si le joueur vient d'un autre club (prêt, changement de club)</small>",
        },
        {
            name: 'photo',
            xtype: 'filefield',
            fieldLabel: 'Photo',
            buttonText: 'Sélection Photo...',
            msgTarget: 'under'
        },
        {
            xtype: 'label',
            html: "<small style='font-style: italic'>La photo du joueur doit être au format JPG. Essayer d'utiliser une photo peu volumineuse (moins de 1Mo)</small>",
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
});