Ext.define('Ufolep13Volley.view.form.register', {
    extend: 'Ext.form.Panel',
    alias: 'widget.form_register',
    title: title,
    layout: 'form',
    url: 'rest/action.php/register/register',
    trackResetOnLoad: true,
    defaults: {
        xtype: 'textfield',
        margin: 10,
        anchor: '100%'
    },
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
            name: 'new_team_name',
            fieldLabel: "Nom de l'équipe à engager",
            labelWidth: 400,
            allowBlank: false,
            msgTarget: 'under'
        },
        {
            xtype: 'label',
            width: 400,
            html: "<small style='font-style: italic'>Vous devez saisir un nom pour votre équipe,<br/>" +
                "que ce soit le même que l'année dernière ou pas</small>",
        },
        {
            xtype: 'combo_club',
            name: 'id_club',
            fieldLabel: "Club de l'équipe à engager",
            labelWidth: 400,
            listeners: {
                change: function (combo, new_val, old_val) {
                    if (new_val == old_val) {
                        return;
                    }
                    if (Ext.isEmpty(new_val)) {
                        combo.up('form').down('combo[name=old_team_id]').getStore().clearFilter();
                        return;
                    }
                    combo.up('form').down('combo[name=old_team_id]').getStore().filter(
                        {
                            property: 'id_club',
                            value: new_val,
                            exactMatch: true,
                        });
                }
            }
        },
        {
            xtype: 'combo_competition',
            store: {
                type: 'Competitions',
                // sélection des compétitions non démarrées uniquement
                filters: [
                    function(item) {
                        return item.get('limit_register_date') >= Ext.Date.now();
                    }
                ]
            },
            fieldLabel: "Compétition",
            name: 'id_competition',
            allowBlank: false,
            listeners: {
                select: function (combo, record) {
                    combo.up('form').down('combo[name=old_team_id]').getStore().filter(
                        {
                            property: 'code_competition',
                            value: record.get('code_competition'),
                            exactMatch: true,
                        });
                }
            }
        },
        user_details.profile_name === 'ADMINISTRATEUR' ? {
            xtype: 'fieldset',
            title: "Attribution de division (Admin)",
            defaults: {
                xtype: 'textfield',
                margin: 10,
                anchor: '100%'
            },
            layout: 'anchor',
            items: [
                {
                    name: 'division',
                    fieldLabel: "Division",
                    allowBlank: true,
                    msgTarget: 'under'
                },
                {
                    xtype: 'numberfield',
                    name: 'rank_start',
                    fieldLabel: "Rang de départ",
                    allowBlank: true,
                    msgTarget: 'under',
                    minValue: 1,
                },
            ]
        } : null,
        {
            xtype: 'combo_team',
            fieldLabel: "Nom de l'équipe lors de la saison précédente",
            labelWidth: 400,
            name: 'old_team_id',
        },
        {
            xtype: 'fieldset',
            title: "Responsable de l'équipe",
            defaults: {
                xtype: 'textfield',
                margin: 10,
                anchor: '100%'
            },
            layout: 'anchor',
            items: [
                {
                    name: 'leader_name',
                    fieldLabel: "Nom",
                    allowBlank: false,
                    msgTarget: 'under'
                },
                {
                    name: 'leader_first_name',
                    fieldLabel: "Prénom",
                    allowBlank: false,
                    msgTarget: 'under'
                },
                {
                    name: 'leader_email',
                    fieldLabel: "Email",
                    allowBlank: false,
                    msgTarget: 'under',
                    vtype: 'email',
                },
                {
                    name: 'leader_phone',
                    fieldLabel: "Numéro de téléphone",
                    allowBlank: false,
                    msgTarget: 'under',
                },
            ]
        },
        {
            xtype: 'fieldset',
            title: "Réception principale",
            defaults: {
                xtype: 'textfield',
                margin: 10,
                anchor: '100%'
            },
            layout: 'anchor',
            items: [
                {
                    xtype: 'combo_court',
                    fieldLabel: 'Gymnase Principal',
                    name: 'id_court_1',
                },
                {
                    xtype: 'label',
                    width: 400,
                    html: "<small style='font-style: italic'>Si votre gymnase n'apparait pas dans la liste<br/>" +
                        "Envoyez un email à <a href='mailto:contact@ufolep13volley.org'>contact@ufolep13volley.org</a></small>",
                },
                {
                    xtype: 'combo_day',
                    name: 'day_court_1',
                },
                {
                    xtype: 'combo_hour',
                    name: 'hour_court_1',
                },
            ]
        },
        {
            xtype: 'fieldset',
            title: "Réception secondaire",
            defaults: {
                xtype: 'textfield',
                margin: 10,
                anchor: '100%'
            },
            layout: 'anchor',
            items: [
                {
                    xtype: 'combo_court',
                    fieldLabel: 'Gymnase Secondaire',
                    name: 'id_court_2',
                },
                {
                    xtype: 'label',
                    width: 400,
                    html: "<small style='font-style: italic'>Si votre gymnase n'apparait pas dans la liste<br/>" +
                        "Envoyez un email à <a href='mailto:contact@ufolep13volley.org'>contact@ufolep13volley.org</a></small>",
                },
                {
                    xtype: 'combo_day',
                    name: 'day_court_2',
                },
                {
                    xtype: 'combo_hour',
                    name: 'hour_court_2',
                },
            ]
        },
        {
            xtype: 'textarea',
            name: 'remarks',
            fieldLabel: "Autres infos",
            allowBlank: true,
        },
        {
            xtype: 'label',
            width: 400,
            html: "<small style='font-style: italic'>Vous pouvez indiquer ici les dates d'indisponibilité du gymnase<br/>" +
                "Ou toute autre info importante pour générer les matchs.</small>",
        },
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