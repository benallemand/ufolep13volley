Ext.define('Ufolep13Volley.view.form.MatchPlayers', {
    extend: 'Ext.form.Panel',
    alias: 'widget.form_match_players',
    title: "Cocher les joueurs du match",
    layout: 'form',
    url: 'rest/action.php/manage_match_players',
    items: [
        {
            xtype: 'hidden',
            fieldLabel: 'id_match',
            name: 'id_match',
        },
        {
            xtype: 'tagfield',
            fieldLabel: 'Joueurs',
            name: 'player_ids[]',
            store: {
                type: 'Players',
                autoLoad: false,
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
            queryMode: 'local',
            displayField: 'full_name',
            tpl: '<tpl for=".">' +
                '<tpl if="est_actif">' +
                '<div class="x-boundlist-item"><img src="{path_photo}" width="50px" style="vertical-align: middle"/><span>{full_name}</span></div>' +
                '<tpl else>' +
                '<div class="x-boundlist-item" style="background: pink"><img src="{path_photo}" width="50px" style="vertical-align: middle"/><span>{full_name}</span></div>' +
                '</tpl>' +
                '</tpl>',
            valueField: 'id',
            forceSelection: true
        }
    ],
    buttons: [
        {
            text: 'Annuler',
            action: 'cancel',
        },
        {
            text: 'Sauver',
            formBind: true,
            disabled: true,
            action: 'save'
        }
    ]
});