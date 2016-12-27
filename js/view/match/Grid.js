Ext.define('Ufolep13Volley.view.match.Grid', {
    extend: 'Ext.grid.Panel',
    alias: 'widget.gridMatches',
    title: 'Matches',
    store: 'Matches',
    autoScroll: true,
    features: [
        {
            ftype: 'grouping',
            groupHeaderTpl: '{name}'
        }
    ],
    columns: {
        items: [
            {
                header: 'Code',
                width: 100,
                dataIndex: 'code_match',
                renderer: function (value, metaData, record) {
                    if (record.get('retard') === 1) {
                        metaData.tdAttr = 'data-qtip="Match non renseigné de + de 5 jours!"';
                        return '<span style="background-color:#C71585;color:black">' + value + '</span>';
                    }
                    if (record.get('retard') === 2) {
                        metaData.tdAttr = 'data-qtip="Match non renseigné de + de 10 jours!"';
                        return '<span style="background-color:Red;color:black">' + value + '</span>';
                    }
                    return value;
                }
            },
            {
                header: 'Date',
                width: 180,
                dataIndex: 'date_reception',
                renderer: function (value, metaData, record) {
                    return Ext.Date.format(value, 'l d/m/Y') + ' ' + record.get('heure_reception');
                }
            },
            {
                header: 'Equipe Domicile',
                width: 180,
                dataIndex: 'equipe_dom',
                renderer: function (value, metaData, record) {
                    if (record.get('score_equipe_dom') > record.get('score_equipe_ext')) {
                        return '<span style="background-color:GreenYellow;color:black">' + value + '</span>';
                    }
                    var today = new Date();
                    today.setHours(0, 0, 0, 0);
                    if (record.get('date_reception') >= today) {
                        return '<a href="annuaire.php?id=' + record.get('id_equipe_dom') + '&c=' + record.get('code_competition') + '" target="blank">' + value + '</a>';
                    }
                    return value;
                }
            },
            {
                header: 'Score',
                dataIndex: 'score_equipe_dom',
                width: 100,
                renderer: function (val, meta, rec) {
                    if ((rec.get('score_equipe_dom') === 3) || (rec.get('score_equipe_ext') === 3)) {
                        return rec.get('score_equipe_dom') + '/' + rec.get('score_equipe_ext');
                    }
                }
            },
            {
                header: 'Equipe Extérieur',
                width: 180,
                dataIndex: 'equipe_ext',
                renderer: function (value, metaData, record) {
                    if (record.get('score_equipe_ext') > record.get('score_equipe_dom')) {
                        return '<span style="background-color:GreenYellow;color:black">' + value + '</span>';
                    }
                    var today = new Date();
                    today.setHours(0, 0, 0, 0);
                    if (record.get('date_reception') >= today) {
                        return '<a href="annuaire.php?id=' + record.get('id_equipe_ext') + '&c=' + record.get('code_competition') + '" target="blank">' + value + '</a>';
                    }
                    return value;
                }
            },
            {
                header: 'Sets',
                dataIndex: 'set_1_dom',
                width: 250,
                renderer: function (val, meta, rec) {
                    var detailsMatch = '';
                    if ((rec.get('set_1_dom') !== 0) || (rec.get('set_1_ext') !== 0)) {
                        detailsMatch = detailsMatch + rec.get('set_1_dom') + '/' + rec.get('set_1_ext') + ' ';
                    }
                    if ((rec.get('set_2_dom') !== 0) || (rec.get('set_2_ext') !== 0)) {
                        detailsMatch = detailsMatch + rec.get('set_2_dom') + '/' + rec.get('set_2_ext') + ' ';
                    }
                    if ((rec.get('set_3_dom') !== 0) || (rec.get('set_3_ext') !== 0)) {
                        detailsMatch = detailsMatch + rec.get('set_3_dom') + '/' + rec.get('set_3_ext') + ' ';
                    }
                    if ((rec.get('set_4_dom') !== 0) || (rec.get('set_4_ext') !== 0)) {
                        detailsMatch = detailsMatch + rec.get('set_4_dom') + '/' + rec.get('set_4_ext') + ' ';
                    }
                    if ((rec.get('set_5_dom') !== 0) || (rec.get('set_5_ext') !== 0)) {
                        detailsMatch = detailsMatch + rec.get('set_5_dom') + '/' + rec.get('set_5_ext') + ' ';
                    }
                    return detailsMatch;
                }
            },
            {
                header: 'Statut',
                width: 70,
                xtype: 'actioncolumn',
                items: [
                    {
                        icon: 'images/svg/email.svg',
                        tooltip: 'Feuille de match reçue',
                        getClass: function (value, meta, rec) {
                            if (rec.get('sheet_received') === false) {
                                return "x-hidden-display";
                            }
                        }
                    },
                    {
                        icon: 'images/svg/validated.svg',
                        tooltip: 'Feuille de match certifiée',
                        getClass: function (value, meta, rec) {
                            if (rec.get('certif') === false) {
                                return "x-hidden-display";
                            }
                        }
                    },
                    {
                        icon: 'images/svg/warning.svg',
                        tooltip: "La feuille de match n'a pas encore été validée par la commission",
                        getClass: function (value, meta, rec) {
                            var today = new Date();
                            today.setHours(0, 0, 0, 0);
                            if (rec.get('date_reception') >= today) {
                                return "x-hidden-display";
                            }
                            if (rec.get('certif') === true) {
                                return "x-hidden-display";
                            }
                        }
                    },
                    {
                        icon: 'images/svg/warning.svg',
                        tooltip: "Merci au responsable de l'équipe victorieuse de s'assurer qu'il a fait parvenir la feuille de match",
                        getClass: function (value, meta, rec) {
                            var today = new Date();
                            today.setHours(0, 0, 0, 0);
                            if (rec.get('date_reception') >= today) {
                                return "x-hidden-display";
                            }
                            if (rec.get('sheet_received') === true) {
                                return "x-hidden-display";
                            }
                            if (rec.get('certif') === true) {
                                return "x-hidden-display";
                            }
                        }
                    }
                ]
            },
            {
                header: 'Commentaires',
                dataIndex: 'note'
            }
        ]
    },
    dockedItems: {
        xtype: 'toolbar',
        dock: 'top',
        items: [
            {
                glyph: 'xf082@FontAwesome',
                text: 'PARTAGER !',
                tooltip: 'Partager',
                href: 'http://www.facebook.com/sharer/sharer.php?u=' + window.location.href,
                hrefTarget: '_blank'
            }
        ]
    }
});