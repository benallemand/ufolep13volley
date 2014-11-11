Ext.define('Ufolep13Volley.view.site.LimitDatePanel', {
    extend: 'Ext.panel.Panel',
    alias: 'widget.limitDatePanel',
    border: false,
    html: Ext.is.Phone ? "<div id='infos_mobile'>" + limitDateLabel + "</div>" : "<div id='infos'>" + limitDateLabel + "</div>"
});
