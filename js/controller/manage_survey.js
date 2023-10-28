Ext.define('Ufolep13Volley.controller.manage_survey', {
    extend: 'Ext.app.Controller',
    stores: [
        'survey',
    ],
    models: [
        'survey',
    ],
    views: [
        'grid.survey'
    ],
    refs: [],
    init: function () {
        this.control(
            {
                'button[text=Menu]': {
                    added: this.add_menu_survey
                },
                'menuitem[action=display_survey]': {
                    click: this.show_grid
                },
            }
        );
    },
    add_menu_survey: function (button) {
        button.menu.add({
            text: 'Sondages',
            action: 'display_survey'
        });
    },
    show_grid: function (button) {
        button.up('tabpanel').add({
            xtype: 'survey_grid',
            layout: 'fit'
        });
    },
});