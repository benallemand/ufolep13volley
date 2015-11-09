Ext.define('Ufolep13Volley.view.timeslot.Edit', {
    extend: 'Ext.window.Window',
    alias: 'widget.timeslotedit',
    title: 'Créneau',
    layout: 'fit',
    modal: true,
    width: 700,
    height: 500,
    autoShow: true,
    items: {
        xtype: 'form',
        trackResetOnLoad: true,
        defaults: {
            xtype: 'textfield',
            anchor: '90%'
        },
        url: 'ajax/saveTimeSlot.php',
        autoScroll: true,
        layout: 'anchor',
        items: [
            {
                xtype: 'hidden',
                name: 'id',
                fieldLabel: 'Id',
                msgTarget: 'under'
            },
            {
                xtype: 'combo',
                name: 'id_equipe',
                fieldLabel: 'Equipe',
                displayField: 'team_full_name',
                valueField: 'id_equipe',
                store: 'Teams',
                queryMode: 'local',
                msgTarget: 'under'
            },
            {
                xtype: 'combo',
                name: 'id_gymnase',
                fieldLabel: 'Gymnase',
                store: 'Gymnasiums',
                queryMode: 'local',
                displayField: 'full_name',
                valueField: 'id',
                allowBlank: false,
                msgTarget: 'under'
            },
            {
                xtype: 'combo',
                name: 'jour',
                fieldLabel: 'Jour',
                store: 'WeekDays',
                queryMode: 'local',
                displayField: 'name',
                valueField: 'name',
                allowBlank: false,
                msgTarget: 'under'
            },
            {
                xtype: 'timefield',
                name: 'heure',
                fieldLabel: 'Heure',
                minValue: '6:00 PM',
                maxValue: '10:00 PM',
                increment: 15,
                allowBlank: false,
                msgTarget: 'under'
            }
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
                handler: function() {
                    this.up('window').close();
                }
            }
        ]
    }
});