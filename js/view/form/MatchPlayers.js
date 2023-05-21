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
            items: {
                xtype: 'tag_field_players'
            },
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