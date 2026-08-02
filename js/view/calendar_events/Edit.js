Ext.define('Ufolep13Volley.view.calendar_events.Edit', {
    extend: 'Ext.window.Window',
    alias: 'widget.calendareventsedit',
    title: "Modification de l'événement",
    height: 380,
    width: 620,
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
        url: '/rest/action.php/calendarevents/saveCalendarEvent',
        items: [
            {
                xtype: 'hidden',
                name: 'id'
            },
            // date_start / date_end sont recomposés par le contrôleur à partir
            // des champs date + heure ci-dessous, pour que l'API ne reçoive
            // qu'une date complète 'Y-m-d H:i:s'.
            {
                xtype: 'hidden',
                name: 'date_start'
            },
            {
                xtype: 'hidden',
                name: 'date_end'
            },
            {
                xtype: 'textfield',
                fieldLabel: 'Saison',
                name: 'season',
                emptyText: '2026-2027',
                regex: /^\d{4}-\d{4}$/,
                regexText: 'Format attendu : 2026-2027',
                allowBlank: false
            },
            {
                xtype: 'textfield',
                fieldLabel: 'Libellé',
                name: 'label',
                emptyText: 'Championnats, Férié / pont, Réunion calendrier...',
                allowBlank: false
            },
            {
                xtype: 'datefield',
                fieldLabel: 'Date de début',
                name: 'date_start_date',
                submitValue: false,
                format: 'd/m/Y',
                allowBlank: false
            },
            {
                xtype: 'timefield',
                fieldLabel: 'Heure de début',
                name: 'date_start_time',
                submitValue: false,
                format: 'H:i',
                increment: 15,
                // Laisser vide pour un événement sur la journée entière : la
                // home n'affiche alors pas d'heure (cas des fériés).
                emptyText: 'journée entière',
                allowBlank: true
            },
            {
                xtype: 'datefield',
                fieldLabel: 'Date de fin',
                name: 'date_end_date',
                submitValue: false,
                format: 'd/m/Y',
                emptyText: 'vide = événement ponctuel',
                allowBlank: true
            },
            {
                xtype: 'timefield',
                fieldLabel: 'Heure de fin',
                name: 'date_end_time',
                submitValue: false,
                format: 'H:i',
                increment: 15,
                allowBlank: true
            }
        ],
        buttons: [
            {
                text: 'Annuler',
                action: 'cancel'
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
