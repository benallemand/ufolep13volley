Ext.onReady(function() {
    var panel = Ext.create('Ext.panel.Panel', {
        renderTo: Ext.get('photos'),
        layout : 'fit',
        tpl: '<img name="image" width="314" height="235" alt="" src="{url}" />'
    });
    function pad(n, width, z) {
        z = z || '0';
        n = n + '';
        return n.length >= width ? n : new Array(width - n.length + 1).join(z) + n;
    }
    ;
    var task = {
        run: function() {
            panel.update({
                url: 'images/photos/imagevolley' + pad(Ext.Number.randomInt(1, 20), 3) + '.jpg'
            });
        }, interval: 5000

    };
    Ext.TaskManager.start(task);
});