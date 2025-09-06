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
            xtype: 'fieldset',
            title: "Equipe",
            defaults: {
                xtype: 'textfield',
                margin: 10,
                anchor: '100%',
                labelWidth: 400,
            },
            layout: 'anchor',
            items: [
                {
                    name: 'new_team_name',
                    fieldLabel: "Nom de l'équipe à engager",
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
                    xtype: 'label',
                    width: 400,
                    html: "<small style='font-style: italic'>Le nombre d'équipes que vous pouvez inscrire <br/>" +
                        "est limité au nombre de terrains que votre club fournit pour les matchs: 1 terrain par semaine => 2 équipes maximum<br/>" +
                        "par exemple, si vous avez un terrain le mardi et un terrain le vendredi, ça fait 2 terrains par semaine, donc 4 équipes maximum.<br/>" +
                        "Une vérification sera faite avant d'établir les divisions.</small>",
                },
                {
                    xtype: 'combo_competition',
                    store: {
                        type: 'Competitions',
                        filters: [
                            function (item) {
                                return user_details.profile_name === 'ADMINISTRATEUR' ? true : item.get('limit_register_date') >= Ext.Date.now() && item.get('start_register_date') < Ext.Date.now();
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
                            if(record.get('code_competition') === 'm') {
                                combo.up('form').down('checkboxfield[name=is_cup_registered]').show();
                                combo.up('form').down('checkboxfield[name=is_cup_registered]').setValue('on');
                            }
                            else {
                                combo.up('form').down('checkboxfield[name=is_cup_registered]').setValue('off');
                                combo.up('form').down('checkboxfield[name=is_cup_registered]').hide();
                            }
                        }
                    },
                },
                {
                    xtype: 'combo_team',
                    fieldLabel: "Nom de l'équipe lors de la saison précédente",
                    name: 'old_team_id',
                    listeners: {
                        select: function (combo, record) {
                            Ext.Msg.prompt("Identification",
                                "Pour pré-remplir les informations de l'an dernier, veuillez saisir l'adresse email du responsable d'équipe:",
                                function (btn, text) {
                                    if (btn === 'ok') {
                                        Ext.Ajax.request({
                                            url: "/rest/action.php/team/load_register",
                                            params: {
                                                id_team: combo.getValue(),
                                                email: text,
                                            },
                                            method: 'GET',
                                            success: function (response) {
                                                var resp = Ext.decode(response.responseText);
                                                combo.up('form').getForm().setValues(resp);
                                            },
                                        });
                                    }
                                });
                        }
                    }
                },
                {
                    name: 'is_cup_registered',
                    hidden: true,
                    xtype: 'checkboxfield',
                    fieldLabel: "Mon équipe souhaite participer à la coupe 6x6 Isoardi",
                    boxLabel: "Oui",
                    msgTarget: 'under',
                    uncheckedValue: 'off'
                },
                !Ext.isEmpty(week_seeding_tournament) ? {
                    name: 'is_seeding_tournament_requested',
                    xtype: 'checkboxfield',
                    fieldLabel: Ext.String.format("Je souhaiterais que cette équipe participe au tournoi de brassage durant la {0}", week_seeding_tournament),
                    boxLabel: "Oui (sous réserve d'éligibilité, déterminée par la commission)",
                    msgTarget: 'under',
                    uncheckedValue: 'off'
                }: null,
                !Ext.isEmpty(week_seeding_tournament) ? {
                    name: 'can_seeding_tournament_setup',
                    xtype: 'checkboxfield',
                    fieldLabel: Ext.String.format("Mon club peut organiser le tournoi de brassage durant la {0}", week_seeding_tournament),
                    boxLabel: "Oui (fournir la date de préférence à la commission)",
                    msgTarget: 'under',
                    uncheckedValue: 'off'
                }: null,
            ],
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
        user_details.profile_name === 'ADMINISTRATEUR' ? {
            name: 'is_paid',
            xtype: 'checkboxfield',
            fieldLabel: 'Adhésion réglée ?',
            boxLabel: 'Oui',
            uncheckedValue: 'off'
        } : null,
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