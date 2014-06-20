Ext.define('Ufolep13Volley.view.team.EditPreferences', {
    extend: 'Ext.window.Window',
    alias: 'widget.editpreferences',
    title: "Préférences",
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
        url: 'ajax/saveMyPreferences.php',
        items: [
            {
                xtype: 'checkbox',
                uncheckedValue: 'off',
                boxLabel: 'Recevoir un rappel des matches de la semaine (le lundi)',
                name: 'is_remind_matches'
            }
        ],
        buttons: [
            {
                text: 'Annuler',
                handler: function() {
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