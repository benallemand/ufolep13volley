Ext.define('Ufolep13Volley.view.team.FormDetails', {
    extend: 'Ext.form.Panel',
    title: 'D�tails',
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
            fieldLabel: 'T�l�phone 1',
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
            fieldLabel: 'T�l�phone 2',
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
            xtype: 'displayfield',
            fieldLabel: 'Gymnase',
            name: 'gymnase',
            renderer: function(val) {
                if (val.length === 0) {
                    return 'Pas de gymnase';
                }
                return val;
            }
        },
        {
            xtype: 'displayfield',
            fieldLabel: 'R�ception le',
            name: 'jour_reception',
            renderer: function(val) {
                if (val.length === 0) {
                    return 'NA';
                }
                return val;
            }
        },
        {
            xtype: 'displayfield',
            fieldLabel: 'Horaire',
            name: 'heure_reception',
            renderer: function(val) {
                if (val.length === 0) {
                    return 'NA';
                }
                return val;
            }
        },
        {
            xtype: 'displayfield',
            fieldLabel: 'Site web',
            name: 'site_web',
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
        },
        {
            xtype: 'displayfield',
            fieldLabel: 'Localisation GPS',
            name: 'localisation',
            regex: /^\d+[\.]\d+,\d+[\.]\d+$/,
            regexText: "Merci d'utiliser le format Google Maps, par exemple : 43.410496,5.242646",
            renderer: function(val) {
                if (val.length === 0) {
                    return 'Champ Absent';
                }
                return "<iframe width='450' height='300' frameborder='0' scrolling='no' marginheight='0' marginwidth='0' src='https://maps.google.com/?ie=UTF8&t=m&q=" + val + "&z=12&output=embed'></iframe>";
            }
        }
    ]
});