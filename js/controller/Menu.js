Ext.define('Ufolep13Volley.controller.Menu', {
    extend: 'Ext.app.Controller',
    stores: ['Gymnasiums'],
    models: ['Gymnasium'],
    views: [],
    refs: [
        {
            ref: 'LastCommitField',
            selector: 'tbtext[id=textShowLastCommit]'
        }
    ],
    init: function () {
        this.control(
                {
                    'menuitem[action=showGymnasiumsMap]': {
                        click: this.showGymnasiumsMap
                    },
                    'mainPanel': {
                        added: this.proposeMobileVersion
                    },
                    'tbtext[id=textShowLastCommit]': {
                        added: this.showLastCommitInformations
                    }
                });
    },
    showLastCommitInformations: function () {
        var me = this;
        Ext.Ajax.request({
            url: 'ajax/getLastCommit.php',
            success: function (response) {
                var text = response.responseText;
                me.getLastCommitField().setText(text);
            }
        });
    },
    proposeMobileVersion: function () {
        if (Ext.is.Phone) {
            Ext.Msg.show({
                title: 'Mobile?',
                msg: 'Accéder à la version mobile?',
                buttons: Ext.Msg.YESNO,
                icon: Ext.Msg.QUESTION,
                fn: function (btn) {
                    if (btn === 'yes') {
                        window.location = 'index_mobile.php';
                    }
                }
            });
        }
    },
    showGymnasiumsMap: function () {
        this.getGymnasiumsStore().load(function (records) {
            var markers = [];
            Ext.each(records, function (record) {
                var latLongStrings = record.get('gps').split(',');
                if (latLongStrings.length === 2) {
                    var lat = parseFloat(latLongStrings[0]);
                    var long = parseFloat(latLongStrings[1]);
                    markers.push({
                        lat: lat,
                        lng: long,
                        title: record.get('nom'),
                        listeners: {
                            click: function () {
                                var markerInsance = this;
                                var infowindow = new google.maps.InfoWindow({
                                    content: '<h3>Adresse : </h3>' + record.get('adresse') + '<br>' +
                                            '<h3>Ville : </h3>' + record.get('ville') + '<br>' +
                                            '<h3>Lien Google Maps : </h3><a href=\"http://maps.google.com/maps?z=12&t=m&q=loc:' + record.get('gps') + '\" target=\"_blank\">Cliquez ici</a>'
                                });
                                infowindow.open(markerInsance.map, markerInsance);
                            }
                        }
                    });
                }
            });
            Ext.create('Ext.window.Window', {
                title: 'Localisation des Gymnases',
                maximizable: true,
                modal: true,
                width: 800,
                height: 500,
                layout: 'fit',
                items: [
                    {
                        xtype: 'gmappanel',
                        width: '100%',
                        height: 500,
                        mapOptions: {
                            zoom: 10,
                            mapTypeId: google.maps.MapTypeId.ROADMAP
                        },
                        center: {
                            geoCodeAddr: 'Aix en provence'
                        },
                        markers: markers
                    }
                ]
            }).show();
        });
    }
});