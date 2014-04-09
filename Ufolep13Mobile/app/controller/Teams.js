Ext.define('Ufolep13Mobile.controller.Teams', {
    extend: 'Ext.app.Controller',
    requires: [
        'Ext.form.Panel',
        'Ext.field.Hidden'
    ],
    config: {
        refs: {
            teamsList: 'listteams',
            mainPanel: 'navigationview',
            formPanel: 'formpanel',
            callButton: 'button[text=Appeler]'
        },
        control: {
            teamsList: {
                itemtap: 'doSelectTeam'
            },
            callButton: {
                tap: 'doPhoneCall'
            }
        }
    },
    doPhoneCall: function() {
        window.open('tel:' + this.getFormPanel().getValues().telephone_1);
    },
    doSelectTeam: function(list, index, item, record) {
        this.getMainPanel().push({
            title: record.get('nom_equipe'),
            layout: 'fit',
            items: [
                {
                    xtype: 'formpanel',
                    url: '../ajax/getQuickDetails.php?id_equipe=' + record.get('id_equipe'),
                    defaults: {
                        xtype: 'textfield',
                        readOnly: true
                    },
                    items: [
                        {
                            xtype: 'toolbar',
                            docked: 'bottom',
                            items: [
                                {
                                    text: 'Appeler'
                                }
                            ]
                        },
                        {
                            xtype: 'hiddenfield',
                            name: 'id_equipe'
                        },
                        {
                            label: 'Responsable',
                            name: 'responsable'
                        },
                        {
                            label: 'Telephone',
                            name: 'telephone_1'
                        },
                        {
                            label: 'Mail',
                            name: 'email'
                        },
                        {
                            label: 'Gymnase',
                            name: 'gymnase'
                        },
                        {
                            label: 'GPS',
                            name: 'localisation'
                        },
                        {
                            label: 'Jour',
                            name: 'jour_reception'
                        },
                        {
                            label: 'Heure',
                            name: 'heure_reception'
                        }
                    ]
                }
            ]
        });
        this.getFormPanel().load();
    }
}
);
