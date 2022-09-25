Ext.define('Ufolep13Volley.view.form.field.combo.hour', {
    extend: 'Ext.form.field.ComboBox',
    alias: 'widget.combo_hour',
    fieldLabel: "Heure de réception",
    name: 'hour_court',
    displayField: 'name',
    valueField: 'name',
    store: Ext.create('Ext.data.Store', {
        fields: ['name'],
        data: [
            {
                "name": "19:00"
            },
            {
                "name": "19:15"
            },
            {
                "name": "19:30"
            },
            {
                "name": "19:45"
            },
            {
                "name": "20:00"
            },
            {
                "name": "20:15"
            },
            {
                "name": "20:30"
            },
            {
                "name": "20:45"
            },
            {
                "name": "21:00"
            },
            {
                "name": "21:15"
            },
            {
                "name": "21:30"
            },
            {
                "name": "21:45"
            }
        ]
    }),
    queryMode: 'local',
    allowBlank: true,
    forceSelection: true
});