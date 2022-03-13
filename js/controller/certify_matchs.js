Ext.define('Ufolep13Volley.controller.certify_matchs', {
    extend: 'Ext.app.Controller',
    stores: [],
    models: [],
    views: [],
    refs: [],
    init: function () {
        this.control(
            {
                'button[action=certify_matchs]': {
                    click: this.certify_matchs
                },
                'matchesgrid': {
                    selectionchange: this.manage_display
                }
            }
        );
    },
    manage_display: function (selection_model, selected) {
        var button = selection_model.view.ownerCt.down('button[action=certify_matchs]');
        var is_hidden = false;
        if(Ext.isEmpty(selected)) {
            is_hidden = true;
        }
        if(!Ext.isArray(selected)) {
            is_hidden = true;
        }
        button.setHidden(is_hidden);
    },
    certify_matchs: function (button) {
        var grid = button.up('grid');
        var records = grid.getSelectionModel().getSelection();
        if (records.length === 0) {
            return;
        }
        Ext.Msg.show({
            title: 'Certifier ?',
            msg: 'Etes-vous certain de vouloir certifier ces matchs ?',
            buttons: Ext.Msg.YESNO,
            icon: Ext.Msg.QUESTION,
            fn: function (btn) {
                if (btn !== 'yes') {
                    return;
                }
                var ids = [];
                Ext.each(records, function (record) {
                    ids.push(record.get('id_match'));
                });
                Ext.Ajax.request({
                    url: 'rest/action.php/certify_matchs',
                    params: {
                        ids: ids.join(',')
                    },
                    success: function () {
                        grid.getStore().load();
                    }
                });
            }
        });
    }
});