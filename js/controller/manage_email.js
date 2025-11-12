Ext.define('Ufolep13Volley.controller.manage_email', {
    extend: 'Ext.app.Controller',
    stores: [
        'email',
    ],
    models: [
        'email',
    ],
    views: [
        'grid.email'
    ],
    refs: [],
    init: function () {
        this.control(
            {
                'button[text=administration]': {
                    added: this.add_menu_email
                },
                'menuitem[action=display_email]': {
                    click: this.show_grid
                },
            }
        );
    },
    add_menu_email: function (button) {
        button.menu.add({
            text: 'Emails',
            action: 'display_email'
        });
    },
    show_grid: function (button) {
        button.up('tabpanel').add({
            xtype: 'grid_email',
            layout: 'fit',
            selModel: 'checkboxmodel',
        });
    },
});