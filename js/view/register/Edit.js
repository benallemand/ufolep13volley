Ext.define('Ufolep13Volley.view.register.Edit', {
    extend: 'Ext.window.Window',
    alias: 'widget.registeredit',
    title: 'Inscription — attribution (admin)',
    layout: 'fit',
    modal: true,
    width: 500,
    autoShow: true,
    items: {
        xtype: 'form',
        trackResetOnLoad: true,
        defaults: {
            xtype: 'textfield',
            anchor: '90%',
            margin: 10
        },
        url: '/rest/action.php/register/register',
        autoScroll: true,
        layout: 'anchor',
        items: [
            // champs édités par l'admin
            {
                name: 'new_team_name',
                fieldLabel: "Nom de l'équipe",
                readOnly: true
            },
            {
                name: 'division',
                fieldLabel: 'Division',
                allowBlank: true,
                msgTarget: 'under'
            },
            {
                xtype: 'numberfield',
                name: 'rank_start',
                fieldLabel: 'Rang de départ',
                allowBlank: true,
                msgTarget: 'under',
                minValue: 1
            },
            {
                xtype: 'checkboxfield',
                name: 'is_paid',
                fieldLabel: 'Adhésion réglée ?',
                boxLabel: 'Oui',
                uncheckedValue: 'off'
            },
            // le endpoint register/register attend l'intégralité des champs :
            // ils sont rechargés depuis la ligne sélectionnée et repostés tels quels
            {xtype: 'hidden', name: 'id'},
            {xtype: 'hidden', name: 'id_club'},
            {xtype: 'hidden', name: 'id_competition'},
            {xtype: 'hidden', name: 'old_team_id'},
            {xtype: 'hidden', name: 'leader_name'},
            {xtype: 'hidden', name: 'leader_first_name'},
            {xtype: 'hidden', name: 'leader_email'},
            {xtype: 'hidden', name: 'leader_phone'},
            {xtype: 'hidden', name: 'id_court_1'},
            {xtype: 'hidden', name: 'day_court_1'},
            {xtype: 'hidden', name: 'hour_court_1'},
            {xtype: 'hidden', name: 'id_court_2'},
            {xtype: 'hidden', name: 'day_court_2'},
            {xtype: 'hidden', name: 'hour_court_2'},
            {xtype: 'hidden', name: 'remarks'}
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
                handler: function (button) {
                    button.up('window').close();
                }
            }
        ]
    }
});
