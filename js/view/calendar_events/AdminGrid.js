Ext.define('Ufolep13Volley.view.calendar_events.AdminGrid', {
    extend: 'Ufolep13Volley.view.grid.ufolep',
    alias: 'widget.calendareventsgrid',
    title: 'Calendrier de la page d\'accueil',
    store: {type: 'AdminCalendarEvents'},
    columns: {
        items: [
            {
                header: 'ID',
                dataIndex: 'id',
                width: 60
            },
            {
                header: 'Saison',
                dataIndex: 'season',
                width: 110
            },
            {
                header: 'Libellé',
                dataIndex: 'label',
                flex: 1
            },
            {
                header: 'Début',
                dataIndex: 'date_start',
                width: 150,
                renderer: Ext.util.Format.dateRenderer('d/m/Y H:i')
            },
            {
                header: 'Fin',
                dataIndex: 'date_end',
                width: 150,
                // Vide = événement ponctuel, affiché comme un point sur la home.
                renderer: function (value) {
                    return value ? Ext.Date.format(value, 'd/m/Y H:i') : '(ponctuel)';
                }
            }
        ]
    },
    dockedItems: [
        {
            xtype: 'toolbar',
            dock: 'top',
            items: [
                'ACTIONS',
                {
                    xtype: 'tbseparator'
                },
                {
                    text: 'Ajouter un événement',
                    glyph: 'xf067@FontAwesome',
                    action: 'addCalendarEvent'
                },
                {
                    text: 'Editer',
                    glyph: 'xf044@FontAwesome',
                    action: 'editCalendarEvent'
                },
                {
                    text: 'Supprimer',
                    glyph: 'xf1f8@FontAwesome',
                    action: 'deleteCalendarEvent'
                }
            ]
        }
    ]
});
