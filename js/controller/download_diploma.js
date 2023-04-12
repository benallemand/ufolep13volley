Ext.define('Ufolep13Volley.controller.download_diploma', {
    extend: 'Ext.app.Controller',
    stores: [],
    models: [],
    views: [],
    refs: [],
    init: function () {
        this.control(
            {
                'button[action=download_diploma]': {
                    click: this.download_diploma
                },
                'hall_of_fame_grid': {
                    selectionchange: this.manage_display
                }
            }
        );
    },
    manage_display: function (selection_model, selected) {
        var button = selection_model.view.ownerCt.down('button[action=download_diploma]');
        var is_hidden = false;
        if (Ext.isEmpty(selected)) {
            is_hidden = true;
        }
        if (!Ext.isArray(selected)) {
            is_hidden = true;
        }
        button.setHidden(is_hidden);
    },
    download_diploma: function (button) {
        var grid = button.up('grid');
        var records = grid.getSelectionModel().getSelection();
        if (records.length === 0) {
            return;
        }
        var ids = [];
        Ext.each(records, function (record) {
            ids.push(record.get('id'));
        });
        var url = Ext.String.format('/rest/action.php/halloffame/download_diploma?ids={0}',
            ids.join(','));
        window.open(url);
    },
});