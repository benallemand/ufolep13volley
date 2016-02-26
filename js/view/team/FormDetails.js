Ext.define('Ufolep13Volley.view.team.FormDetails', {
    extend: 'Ext.form.Panel',
    title: 'Détails',
    alias: 'widget.formTeamDetails',
    layout: 'anchor',
    autoScroll: true,
    defaults: {
        anchor: '100%',
        margins: 10
    },
    items: [
        {
            xtype: 'displayfield',
            fieldLabel: 'Club',
            name: 'club',
            renderer: function(val) {
                if (val.length === 0) {
                    return 'Pas de valeur';
                }
                return "<img src='ajax/getImageFromText.php?text=" + btoa(val) + "'/>";
            }
        },
        {
            xtype: 'displayfield',
            fieldLabel: 'Responsable',
            name: 'responsable',
            renderer: function(val) {
                if (val.length === 0) {
                    return 'Pas de valeur';
                }
                return "<img src='ajax/getImageFromText.php?text=" + btoa(val) + "'/>";
            }
        },
        {
            xtype: 'displayfield',
            fieldLabel: 'Téléphone 1',
            name: 'telephone_1',
            renderer: function(val) {
                if (val.length === 0) {
                    return 'Pas de valeur';
                }
                return "<img src='ajax/getImageFromText.php?text=" + btoa(val) + "'/>";
            }
        },
        {
            xtype: 'displayfield',
            fieldLabel: 'Téléphone 2',
            name: 'telephone_2',
            renderer: function(val) {
                if (val.length === 0) {
                    return 'Pas de valeur';
                }
                return "<img src='ajax/getImageFromText.php?text=" + btoa(val) + "'/>";
            }
        },
        {
            xtype: 'displayfield',
            fieldLabel: 'Email',
            name: 'email',
            renderer: function(val) {
                if (val.length === 0) {
                    return 'Pas de valeur';
                }
                return "<img src='ajax/getImageFromText.php?text=" + btoa(val) + "'/>";
            }
        },
        {
            xtype: 'textarea',
            fieldLabel: 'Créneaux',
            readOnly: true,
            name: 'gymnasiums_list',
            renderer: function(val) {
                if (val.length === 0) {
                    return 'Pas de gymnase';
                }
                var timeSlots = val.split(', ');
                val = '';
                Ext.each(timeSlots, function(timeSlot) {
                    val = val + timeSlot + '<br/>';
                });
                return val;
            }
        },
        {
            xtype: 'displayfield',
            fieldLabel: 'Site web',
            name: 'web_site',
            renderer: function(val) {
                return "<a href='" + val + "' target='_blank'>" + val + "</a>";
            }
        },
        {
            xtype: 'displayfield',
            fieldLabel: 'Fiche Equipe',
            name: 'id_equipe',
            renderer: function(val) {
                return '<a href="teamSheetPdf.php?id=' + val + '" target="blank">Telecharger</a>';
            }
        }
    ]
});