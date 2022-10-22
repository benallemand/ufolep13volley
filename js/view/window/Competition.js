Ext.define('Ufolep13Volley.view.window.Competition', {
    extend: 'Ext.window.Window',
    alias: 'widget.competition_edit',
    title: "Edition de compétition",
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
        url: '/rest/action.php/competition/saveCompetition',
        items: [
            {
                xtype: 'hidden',
                fieldLabel: 'id',
                name: 'id'
            },
            {
                xtype: 'textfield',
                fieldLabel: 'Code compétition',
                name: 'code_competition',
                allowBlank: false
            },
            {
                xtype: 'textfield',
                fieldLabel: 'Libellé',
                name: 'libelle',
                allowBlank: false
            },
            {
                xtype: 'textfield',
                fieldLabel: 'Code compétition maître',
                name: 'id_compet_maitre',
                allowBlank: false
            },
            {
                xtype: 'datefield',
                fieldLabel: 'Date',
                name: 'start_date',
                allowBlank: true,
                startDay: 1
            },
            {
                name: 'is_home_and_away',
                xtype: 'checkboxfield',
                fieldLabel: 'Matchs aller-retour ?',
                boxLabel: 'Oui',
                uncheckedValue: 'off'
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