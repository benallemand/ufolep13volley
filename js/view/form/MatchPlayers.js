Ext.define('Ufolep13Volley.view.form.MatchPlayers', {
    extend: 'Ext.form.Panel',
    alias: 'widget.form_match_players',
    layout: 'form',
    scrollable: true,
    defaults: {
        flex: 1,
    },
    url: 'rest/action.php/matchmgr/manage_match_players',
    items: [
        {
            xtype: 'hidden',
            fieldLabel: 'id_match',
            name: 'id_match',
        },
        {
            title: 'Présents',
            items: {
                xtype: 'view_match_players',
            }
        },
        {
            title: 'Ajouter',
            layout: 'form',
            items: [
                {
                    xtype: 'tag_field_players',
                },
                {
                    xtype: 'combo_player',
                    fieldLabel: "Déclarer un renfort",
                    name: 'reinforcement_player_id',
                    store: {
                        type: 'ReinforcementPlayers',
                        sorters: [
                            {
                                property: 'id_club',
                                direction: 'ASC'
                            },
                            {
                                property: 'sexe',
                                direction: 'ASC'
                            }
                        ]
                    },
                },
                {
                    xtype: 'displayfield',
                    hideLabel: true,
                    value: "<small style='font-style: italic'>Renfort: 1 joueur autorisé par match et par équipe. Le même renfort ne peut pas être utilisé sur 2 matchs dans la même demi-saison.</small> ",
                },
            ],
        },
    ],
    buttons: [
        {
            text: 'Ajouter les joueurs sélectionnés',
            formBind: true,
            disabled: true,
            action: 'save',
            iconCls: 'fa-solid fa-floppy-disk',
        },
        {
            action: 'sign_team_sheet',
            iconCls: 'fa-solid fa-signature',
            text: 'Signer les fiches équipes'
        },
    ]
});