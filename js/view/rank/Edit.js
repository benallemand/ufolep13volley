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
        url: '/rest/action.php/rank/saveRank',
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
                store: {type: 'Competitions'},
                queryMode: 'local',
                allowBlank: false,
                forceSelection: true
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
                store: {type: 'Teams'},
                queryMode: 'local',
                allowBlank: false,
                forceSelection: true
            },
            {
                xtype: 'numberfield',
                fieldLabel: 'Classement au départ',
                name: 'rank_start',
                allowBlank: false
            },
            {
                name: 'will_register_again',
                xtype: 'checkboxfield',
                fieldLabel: "Se réengage l'an prochain ?",
                boxLabel: 'Oui',
                msgTarget: 'under',
                uncheckedValue: 'off'
            },
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