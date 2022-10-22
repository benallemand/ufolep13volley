Ext.define('Ufolep13Volley.controller.my_players', {
    extend: 'Ext.app.Controller',
    stores: [
        'Players',
        'my_players',
        'Clubs',
    ],
    models: ['Player', 'Club',],
    views: ['grid.my_players', 'form.player',],
    refs: [],
    init: function () {
        this.control({
            'grid_my_players': {
                selectionchange: this.manage_display
            },
            'button[action=remove_from_team], button[action=set_leader], button[action=set_vice_leader], button[action=set_captain]': {
                click: this.manage_post
            },
            'button[action=save]': {
                click: this.save_form
            },
            'button[action=add_to_team]': {
                click: this.add_to_team
            },
            'tagfield[name=add_to_team_player_id]': {
                select: this.enable_add_to_team
            },
            'button[action=new_player]': {
                click: this.display_new_player
            },
        });
    },
    enable_add_to_team: function (combo) {
        combo.up('toolbar').down('button[action=add_to_team]').enable();
    },
    manage_display: function (selection_model, selected) {
        var buttons = [selection_model.view.ownerCt.down('button[action=set_leader]'), selection_model.view.ownerCt.down('button[action=set_vice_leader]'), selection_model.view.ownerCt.down('button[action=set_captain]'),];
        var is_hidden = Ext.isEmpty(selected) || !Ext.isArray(selected) || selected.length > 1;
        Ext.each(buttons, function (button) {
            button.setHidden(is_hidden);
        });
        var buttons = [selection_model.view.ownerCt.down('button[action=remove_from_team]'),];
        var is_hidden = Ext.isEmpty(selected) || !Ext.isArray(selected);
        Ext.each(buttons, function (button) {
            button.setHidden(is_hidden);
        });
        var form = selection_model.view.ownerCt.up('viewport').down('form');
        if (selected.length === 1) {
            form.loadRecord(selected[0]);
            form.down('image').setSrc(selected[0].get('path_photo'));
        } else {
            form.getForm().getFields().each(function (f) {
                f.originalValue = undefined;
            });
            form.getForm().reset();
            form.down('image').setSrc('');
        }
    },
    display_new_player: function (button) {
        var form = button.up('viewport').down('form');
        form.getForm().getFields().each(function (f) {
            f.originalValue = undefined;
        });
        form.getForm().reset();
        form.down('image').setSrc('');
        Ext.Msg.alert("Info", "Vous pouvez utiliser le fomulaire pour créer un(e) joueur/joueuse !");
    },
    manage_post: function (button) {
        var action = button.action;
        var records = button.up('grid').getSelectionModel().getSelection();
        this.post_ids(button, action, records);
    },
    add_to_team: function (button) {
        var action = button.action;
        var records = button.up('toolbar').down('tagfield[name=add_to_team_player_id]').getValueRecords();
        this.post_ids(button, action, records);
    },
    save_form: function (button) {
        var viewport = button.up('viewport');
        var grid = viewport.down('grid');
        var form = button.up('form').getForm();
        if (form.isValid()) {
            var dirtyFieldsJson = form.getFieldValues(true);
            var dirtyFieldsArray = [];
            for (var key in dirtyFieldsJson) {
                dirtyFieldsArray.push(key);
            }
            form.submit({
                params: {
                    dirtyFields: dirtyFieldsArray.join(',')
                }, success: function () {
                    Ext.Msg.alert('Info', this.result.message);
                    grid.getStore().load();
                }, failure: function (form, action) {
                    Ext.Msg.alert('Erreur', action.result.message);
                }
            });
        }

    },
    post_ids(button, action, records) {
        var ids = [];
        Ext.each(records, function (record) {
            ids.push(record.get('id'));
        });
        Ext.Ajax.request({
            url: Ext.String.format('rest/action.php/player/{0}', action),
            params: {
                'ids[]': ids
            },
            success: function (response) {
                var obj = Ext.decode(response.responseText);
                Ext.Msg.alert('Info', obj.message);
                button.up('grid').getStore().load();
            },
            failure: function (response) {
                var obj = Ext.decode(response.responseText);
                Ext.Msg.alert('Erreur', obj.message);
            },
        });
    }
});