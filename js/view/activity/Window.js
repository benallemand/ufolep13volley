Ext.define('Ufolep13Volley.view.activity.Window', {
    extend: 'Ext.window.Window',
    alias: 'widget.activitywindow',
    title: 'Activité',
    layout: 'fit',
    modal: true,
    width: 700,
    height: 500,
    autoShow: true,
    items: {
        xtype: 'activitygrid'
    }
});