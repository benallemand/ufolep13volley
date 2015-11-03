Ext.define('Ufolep13Volley.view.match.Edit', {
    extend: 'Ext.window.Window',
    alias: 'widget.matchedit',
    title: "Modification du match",
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
        url: 'ajax/saveMatch.php',
        items: [
            {
                xtype: 'hidden',
                fieldLabel: 'id_match',
                name: 'id_match'
            },
            {
                xtype: 'textfield',
                fieldLabel: 'Code',
                name: 'code_match',
                allowBlank: false
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
                xtype: 'textfield',
                fieldLabel: 'Journée',
                name: 'numero_journee',
                allowBlank: false
            },
            {
                xtype: 'combo',
                fieldLabel: 'Domicile',
                name: 'id_equipe_dom',
                displayField: 'team_full_name',
                valueField: 'id_equipe',
                store: 'Teams',
                queryMode: 'local',
                allowBlank: false
            },
            {
                xtype: 'combo',
                fieldLabel: 'Extérieur',
                name: 'id_equipe_ext',
                displayField: 'team_full_name',
                valueField: 'id_equipe',
                store: 'Teams',
                queryMode: 'local',
                allowBlank: false
            },
            {
                xtype: 'datefield',
                fieldLabel: 'Date',
                name: 'date_reception',
                allowBlank: false
            },
            {
                xtype: 'textfield',
                fieldLabel: 'Heure',
                name: 'heure_reception',
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