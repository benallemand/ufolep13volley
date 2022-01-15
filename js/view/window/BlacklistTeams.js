Ext.define('Ufolep13Volley.view.window.BlacklistTeams', {
    extend: 'Ext.window.Window',
    alias: 'widget.blacklistteams_edit',
    title: "Equipes qui ne peuvent pas jouer le même soir",
    height: 400,
    width: 700,
    modal: true,
    layout: 'fit',
    autoShow: true,
    items: {
        xtype: 'form',
        trackResetOnLoad: true,
        layout: 'anchor',
        defaults: {
            anchor: '90%',
            margins: 10
        },
        url: 'ajax/saveBlacklistTeams.php',
        items: [
            {
                xtype: 'hidden',
                fieldLabel: 'id',
                name: 'id'
            },
            {
                xtype: 'combo',
                fieldLabel: 'Equipe 1',
                name: 'id_team_1',
                displayField: 'team_full_name',
                valueField: 'id_equipe',
                store: 'Teams',
                queryMode: 'local',
                allowBlank: false,
                forceSelection: true
            },
            {
                xtype: 'combo',
                fieldLabel: 'Equipe 2',
                name: 'id_team_2',
                displayField: 'team_full_name',
                valueField: 'id_equipe',
                store: 'Teams',
                queryMode: 'local',
                allowBlank: false,
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
    }
});