Ext.define('Ufolep13Volley.view.window.BlacklistTeam', {
    extend: 'Ext.window.Window',
    alias: 'widget.blacklistteam_edit',
    title: "Saisie de date interdite pour les matchs par une équipe",
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
        url: 'ajax/saveBlacklistTeam.php',
        items: [
            {
                xtype: 'hidden',
                fieldLabel: 'id',
                name: 'id'
            },
            {
                xtype: 'combo',
                fieldLabel: 'Equipe',
                name: 'id_team',
                displayField: 'team_full_name',
                valueField: 'id_equipe',
                store: 'Teams',
                queryMode: 'local',
                allowBlank: false,
                forceSelection: true
            },
            {
                xtype: 'datefield',
                fieldLabel: 'Date interdite',
                name: 'closed_date',
                allowBlank: false,
                startDay: 1,
                format: 'd/m/Y'
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