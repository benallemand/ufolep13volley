Ext.define('Ufolep13Mobile.controller.Teams', {
    extend: 'Ext.app.Controller',
    requires: [
        'Ext.form.Panel',
        'Ext.field.Hidden',
        'Ext.util.Geolocation'
    ],
    config: {
        refs: {
            teamsList: 'listteams',
            mainPanel: 'navigationview',
            formPanel: 'formpanel',
            callButton: 'button[text=Appeler]',
            mapButton: 'button[text=Itineraire]'
        },
        control: {
            teamsList: {
                itemtap: 'doSelectTeam'
            },
            callButton: {
                tap: 'doPhoneCall'
            },
            mapButton: {
                tap: 'doMap'
            }
        }
    },
    doPhoneCall: function() {
        window.open('tel:' + this.getFormPanel().getValues().telephone_1, '_self');
    },
    doMap: function() {
        var controller = this;
        var geo = Ext.create('Ext.util.Geolocation', {
            autoUpdate: false,
            listeners: {
                locationupdate: function(geo) {
                    var currentLat = geo.getLatitude();
                    var currentLong = geo.getLongitude();
                    var currentLoc = currentLat + ',' + currentLong;
                    window.open('http://maps.apple.com?daddr=' + controller.getFormPanel().getValues().localisation + '&saddr=' + currentLoc);
                },
                locationerror: function(geo, bTimeout, bPermissionDenied, bLocationUnavailable, message) {
                    if (bTimeout) {
                        alert('Erreur : Temps de reponse trop important.');
                    } else {
                        alert('Erreur : ' + message);
                    }
                }
            }
        });
        geo.updateLocation();
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
                                    text: 'Appeler',
                                    icon: '../images/phone.png'
                                },
                                {
                                    text: 'Itineraire',
                                    icon: '../images/map.png'
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
                            label: 'Telephone (2)',
                            name: 'telephone_2'
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
