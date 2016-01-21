Ext.define('Ufolep13Volley.view.rank.Edit', {
    extend: 'Ext.window.Window',
    alias: 'widget.rankedit',
    title: "Modification du classement",
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
        url: 'ajax/saveRank.php',
        items: [
            {
                xtype: 'hidden',
                fieldLabel: 'id',
                name: 'id'
            },
            {
                xtype: 'combo',
                fieldLabel: 'Competition',
                name: 'code_competition',
                displayField: 'libelle',
                valueField: 'code_competition',
                store: 'Competitions',
                queryMode: 'local',
                allowBlank: false
            },
            {
                xtype: 'textfield',
                fieldLabel: 'Division',
                name: 'division',
                allowBlank: false
            },
            {
                xtype: 'combo',
                fieldLabel: 'Equipe',
                name: 'id_equipe',
                displayField: 'team_full_name',
                valueField: 'id_equipe',
                store: 'Teams',
                queryMode: 'local',
                allowBlank: false
            }
        ],
        buttons: [
            {
                text: 'Annuler',
                handler: function () {
                    this.up('window').close();
                }
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