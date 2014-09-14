Ext.define('Ufolep13Volley.view.team.TimeSlotsManage', {
    extend: 'Ext.window.Window',
    alias: 'widget.timeslotsmanage',
    title: 'Gestion des cr�neaux',
    height: 750,
    width: 1100,
    modal: true,
    layout: 'fit',
    autoShow: true,
    items: {
        xtype: 'grid',
        store: 'TimeSlots',
        autoScroll: true,
        columns: {
            items: [
                {
                    header: 'Gymnase',
                    dataIndex: 'gymnasium_full_name',
                    width: 400
                },
                {
                    header: 'Jour',
                    dataIndex: 'jour',
                    width: 100
                },
                {
                    header: 'Heure',
                    dataIndex: 'heure',
                    width: 80
                }
            ]
        },
        dockedItems: [
            {
                xtype: 'toolbar',
                dock: 'top',
                items: [
                    {
                        xtype: 'button',
                        text: 'Ajouter un cr�neau',
                        action: 'createTimeSlot'
                    },
                    {
                        xtype: 'button',
                        text: 'Retirer un cr�neau',
                        action: 'removeTimeSlot'
                    },
                    {
                        xtype: 'button',
                        text: 'Editer un cr�neau',
                        action: 'editTimeSlot'
                    }
                ]
            }
        ]
    }
});