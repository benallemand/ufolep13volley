var Base64 = (function () {
    var keyStr = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/=";

    function utf8Encode(string) {
        string = string.replace(/\r\n/g, "\n");
        var utftext = "";
        for (var n = 0; n < string.length; n++) {
            var c = string.charCodeAt(n);
            if (c < 128) {
                utftext += String.fromCharCode(c);
            } else if ((c > 127) && (c < 2048)) {
                utftext += String.fromCharCode((c >> 6) | 192);
                utftext += String.fromCharCode((c & 63) | 128);
            } else {
                utftext += String.fromCharCode((c >> 12) | 224);
                utftext += String.fromCharCode(((c >> 6) & 63) | 128);
                utftext += String.fromCharCode((c & 63) | 128);
            }
        }
        return utftext;
    }

    return {
        encode: (typeof btoa == 'function') ? function (input) {
            return btoa(utf8Encode(input));
        } : function (input) {
            var output = "";
            var chr1, chr2, chr3, enc1, enc2, enc3, enc4;
            var i = 0;
            input = utf8Encode(input);
            while (i < input.length) {
                chr1 = input.charCodeAt(i++);
                chr2 = input.charCodeAt(i++);
                chr3 = input.charCodeAt(i++);
                enc1 = chr1 >> 2;
                enc2 = ((chr1 & 3) << 4) | (chr2 >> 4);
                enc3 = ((chr2 & 15) << 2) | (chr3 >> 6);
                enc4 = chr3 & 63;
                if (isNaN(chr2)) {
                    enc3 = enc4 = 64;
                } else if (isNaN(chr3)) {
                    enc4 = 64;
                }
                output = output +
                    keyStr.charAt(enc1) + keyStr.charAt(enc2) +
                    keyStr.charAt(enc3) + keyStr.charAt(enc4);
            }
            return output;
        }
    };
})();

Ext.define('Ufolep13Volley.overrides.view.Grid', {
    override: 'Ext.grid.GridPanel',
    requires: 'Ext.form.action.StandardSubmit',
    downloadExcelXml: function (includeHidden, title) {
        if (!title) title = this.title;
        var vExportContent = this.getExcelXml(includeHidden, title);


        /*
         dynamically create and anchor tag to force download with suggested filename
         note: download attribute is Google Chrome specific
         */

        if (Ext.isChrome) {
            var gridEl = this.getEl();
            var location = 'data:application/vnd.ms-excel;base64,' + Base64.encode(vExportContent);

            var el = Ext.DomHelper.append(gridEl, {
                tag: "a",
                download: title + "-" + Ext.Date.format(new Date(), 'Y-m-d Hi') + '.xls',
                href: location
            });

            el.click();

            Ext.fly(el).destroy();

        } else {

            var form = this.down('form#uploadForm');
            if (form) {
                form.destroy();
            }
            form = this.add({
                xtype: 'form',
                itemId: 'uploadForm',
                hidden: true,
                standardSubmit: true,
                url: 'http://webapps.figleaf.com/dataservices/Excel.cfc?method=echo&mimetype=application/vnd.ms-excel&filename=' + escape(title + ".xls"),
                items: [{
                    xtype: 'hiddenfield',
                    name: 'data',
                    value: vExportContent
                }]
            });

            form.getForm().submit();

        }
    },

    /*

     Welcome to XML Hell
     See: http://msdn.microsoft.com/en-us/library/office/aa140066(v=office.10).aspx
     for more details

     */
    getExcelXml: function (includeHidden, title) {

        var theTitle = title || this.title;

        var worksheet = this.createWorksheet(includeHidden, theTitle);
        if (this.columnManager.columns) {
            var totalWidth = this.columnManager.columns.length;
        } else {
            var totalWidth = this.columns.length;
        }

        return ''.concat(
            '<?xml version="1.0"?>',
            '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet" xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet" xmlns:html="http://www.w3.org/TR/REC-html40">',
            '<DocumentProperties xmlns="urn:schemas-microsoft-com:office:office"><Title>' + theTitle + '</Title></DocumentProperties>',
            '<OfficeDocumentSettings xmlns="urn:schemas-microsoft-com:office:office"><AllowPNG/></OfficeDocumentSettings>',
            '<ExcelWorkbook xmlns="urn:schemas-microsoft-com:office:excel">',
            '<WindowHeight>' + worksheet.height + '</WindowHeight>',
            '<WindowWidth>' + worksheet.width + '</WindowWidth>',
            '<ProtectStructure>False</ProtectStructure>',
            '<ProtectWindows>False</ProtectWindows>',
            '</ExcelWorkbook>',

            '<Styles>',

            '<Style ss:ID="Default" ss:Name="Normal">',
            '<Alignment ss:Vertical="Bottom"/>',
            '<Borders/>',
            '<Font ss:FontName="Calibri" x:Family="Swiss" ss:Size="12" ss:Color="#000000"/>',
            '<Interior/>',
            '<NumberFormat/>',
            '<Protection/>',
            '</Style>',

            '<Style ss:ID="title">',
            '<Borders />',
            '<Font ss:Bold="1" ss:Size="18" />',
            '<Alignment ss:Horizontal="Center" ss:Vertical="Center" ss:WrapText="1" />',
            '<NumberFormat ss:Format="@" />',
            '</Style>',

            '<Style ss:ID="headercell">',
            '<Font ss:Bold="1" ss:Size="10" />',
            '<Alignment ss:Horizontal="Center" ss:WrapText="1" />',
            '<Interior ss:Color="#A3C9F1" ss:Pattern="Solid" />',
            '</Style>',


            '<Style ss:ID="even">',
            '<Interior ss:Color="#CCFFFF" ss:Pattern="Solid" />',
            '</Style>',


            '<Style ss:ID="evendate" ss:Parent="even">',
            '<NumberFormat ss:Format="yyyy-mm-dd" />',
            '</Style>',


            '<Style ss:ID="evenint" ss:Parent="even">',
            '<Numberformat ss:Format="0" />',
            '</Style>',

            '<Style ss:ID="evenfloat" ss:Parent="even">',
            '<Numberformat ss:Format="0.00" />',
            '</Style>',

            '<Style ss:ID="odd">',
            '<Interior ss:Color="#CCCCFF" ss:Pattern="Solid" />',
            '</Style>',

            '<Style ss:ID="groupSeparator">',
            '<Interior ss:Color="#D3D3D3" ss:Pattern="Solid" />',
            '</Style>',

            '<Style ss:ID="odddate" ss:Parent="odd">',
            '<NumberFormat ss:Format="yyyy-mm-dd" />',
            '</Style>',

            '<Style ss:ID="oddint" ss:Parent="odd">',
            '<NumberFormat Format="0" />',
            '</Style>',

            '<Style ss:ID="oddfloat" ss:Parent="odd">',
            '<NumberFormat Format="0.00" />',
            '</Style>',


            '</Styles>',
            worksheet.xml,
            '</Workbook>'
        );
    },

    /*

     Support function to return field info from store based on fieldname

     */

    getModelField: function (fieldName) {

        var fields = this.store.model.getFields();
        for (var i = 0; i < fields.length; i++) {
            if (fields[i].name === fieldName) {
                return fields[i];
            }
        }
    },

    /*

     Convert store into Excel Worksheet

     */
    generateEmptyGroupRow: function (dataIndex, value, cellTypes, includeHidden) {


        var cm = this.columnManager.columns;
        var colCount = cm.length;
        var rowTpl = '<Row ss:AutoFitHeight="0"><Cell ss:StyleID="groupSeparator" ss:MergeAcross="{0}"><Data ss:Type="String"><html:b>{1}</html:b></Data></Cell></Row>';
        var visibleCols = 0;

        // rowXml += '<Cell ss:StyleID="groupSeparator">'

        for (var j = 0; j < colCount; j++) {
            if (cm[j].xtype != 'actioncolumn' && (cm[j].dataIndex != '') && (includeHidden || !cm[j].hidden)) {
                // rowXml += '<Cell ss:StyleID="groupSeparator"/>';
                visibleCols++;
            }
        }

        // rowXml += "</Row>";

        return Ext.String.format(rowTpl, visibleCols - 1, Ext.String.htmlEncode(value));
    },


    createWorksheet: function (includeHidden, theTitle) {
        // Calculate cell data types and extra class names which affect formatting
        var cellType = [];
        var cellTypeClass = [];
        console.log(this);
        if (this.columnManager.columns) {
            var cm = this.columnManager.columns;
        } else {
            var cm = this.columns;
        }
        console.log(cm);
        var colCount = cm.length;
        var totalWidthInPixels = 0;
        var colXml = '';
        var headerXml = '';
        var visibleColumnCountReduction = 0;


        for (var i = 0; i < cm.length; i++) {
            if (cm[i].xtype != 'actioncolumn' && (cm[i].dataIndex != '') && (includeHidden || !cm[i].hidden)) {
                var w = cm[i].getEl().getWidth();
                totalWidthInPixels += w;

                if (cm[i].text === "") {
                    cellType.push("None");
                    cellTypeClass.push("");
                    ++visibleColumnCountReduction;
                } else {
                    colXml += '<Column ss:AutoFitWidth="1" ss:Width="' + w + '" />';
                    headerXml += '<Cell ss:StyleID="headercell">' +
                        '<Data ss:Type="String">' + cm[i].text.replace("<br>", " ") + '</Data>' +
                        '<NamedCell ss:Name="Print_Titles"></NamedCell></Cell>';


                    var fld = this.getModelField(cm[i].dataIndex);

                    switch (fld.$className) {
                        case "Ext.data.field.Integer":
                            cellType.push("Number");
                            cellTypeClass.push("int");
                            break;
                        case "Ext.data.field.Number":
                            cellType.push("Number");
                            cellTypeClass.push("float");
                            break;
                        case "Ext.data.field.Boolean":
                            cellType.push("String");
                            cellTypeClass.push("");
                            break;
                        case "Ext.data.field.Date":
                            cellType.push("DateTime");
                            cellTypeClass.push("date");
                            break;
                        default:
                            cellType.push("String");
                            cellTypeClass.push("");
                            break;
                    }
                }
            }
        }
        var visibleColumnCount = cellType.length - visibleColumnCountReduction;

        var result = {
            height: 9000,
            width: Math.floor(totalWidthInPixels * 30) + 50
        };

        // Generate worksheet header details.

        // determine number of rows
        var numGridRows = this.store.getCount() + 2;
        if ((this.store.groupField && !Ext.isEmpty(this.store.groupField)) || (this.store.groupers && this.store.groupers.items.length > 0)) {
            numGridRows = numGridRows + this.store.getGroups().length;
        }

        // create header for worksheet
        var t = ''.concat(
            '<Worksheet ss:Name="' + theTitle + '">',

            '<Names>',
            '<NamedRange ss:Name="Print_Titles" ss:RefersTo="=\'' + theTitle + '\'!R1:R2">',
            '</NamedRange></Names>',

            '<Table ss:ExpandedColumnCount="' + (visibleColumnCount + 2),
            '" ss:ExpandedRowCount="' + numGridRows + '" x:FullColumns="1" x:FullRows="1" ss:DefaultColumnWidth="65" ss:DefaultRowHeight="15">',
            colXml,
            '<Row ss:Height="38">',
            '<Cell ss:MergeAcross="' + (visibleColumnCount - 1) + '" ss:StyleID="title">',
            '<Data ss:Type="String" xmlns:html="http://www.w3.org/TR/REC-html40">',
            '<html:b>' + theTitle + '</html:b></Data><NamedCell ss:Name="Print_Titles">',
            '</NamedCell></Cell>',
            '</Row>',
            '<Row ss:AutoFitHeight="1">',
            headerXml +
            '</Row>'
        );

        // Generate the data rows from the data in the Store
        var groupVal = "";
        var groupField = "";
        if (this.store.groupers && this.store.groupers.keys.length > 0) {
            groupField = this.store.groupers.keys[0];
        } else if (this.store.groupField != '') {
            groupField = this.store.groupField;
        }

        for (var i = 0, it = this.store.data.items, l = it.length; i < l; i++) {

            if (!Ext.isEmpty(groupField)) {
                if (groupVal != this.store.getAt(i).get(groupField)) {
                    groupVal = this.store.getAt(i).get(groupField);
                    t += this.generateEmptyGroupRow(groupField, groupVal, cellType, includeHidden);
                }
            }
            t += '<Row>';
            var cellClass = (i & 1) ? 'odd' : 'even';
            r = it[i].data;
            var k = 0;
            for (var j = 0; j < colCount; j++) {
                if (cm[j].xtype != 'actioncolumn' && (cm[j].dataIndex != '') && (includeHidden || !cm[j].hidden)) {
                    var v = r[cm[j].dataIndex];
                    if (cellType[k] !== "None") {
                        t += '<Cell ss:StyleID="' + cellClass + cellTypeClass[k] + '"><Data ss:Type="' + cellType[k] + '">';
                        if (cellType[k] == 'DateTime') {
                            t += Ext.Date.format(v, 'Y-m-d');
                        } else if (!Ext.isEmpty(v)) {
                            t += Ext.String.htmlEncode(v);
                        }
                        t += '</Data></Cell>';
                    }
                    k++;
                }
            }
            t += '</Row>';
        }

        result.xml = t.concat(
            '</Table>',
            '<WorksheetOptions xmlns="urn:schemas-microsoft-com:office:excel">',
            '<PageLayoutZoom>0</PageLayoutZoom>',
            '<Selected/>',
            '<Panes>',
            '<Pane>',
            '<Number>3</Number>',
            '<ActiveRow>2</ActiveRow>',
            '</Pane>',
            '</Panes>',
            '<ProtectObjects>False</ProtectObjects>',
            '<ProtectScenarios>False</ProtectScenarios>',
            '</WorksheetOptions>',
            '</Worksheet>'
        );
        return result;
    }
});

Ext.define('Ufolep13Volley.controller.Administration', {
    extend: 'Ext.app.Controller',
    stores: [
        'Players',
        'Clubs',
        'Teams',
        'RankTeams',
        'Competitions',
        'ParentCompetitions',
        'Profiles',
        'Users',
        'Gymnasiums',
        'Activity',
        'WeekSchedule',
        'AdminMatches',
        'AdminDays',
        'LimitDates',
        'AdminRanks',
        'HallOfFame'
    ],
    models: [
        'Player',
        'Club',
        'Team',
        'RankTeam',
        'Competition',
        'Profile',
        'User',
        'Gymnasium',
        'Activity',
        'WeekSchedule',
        'Match',
        'WeekDay',
        'Day',
        'LimitDate',
        'Rank',
        'HallOfFame'
    ],
    views: [
        'player.Grid',
        'player.Edit',
        'club.Select',
        'team.Select',
        'team.Grid',
        'team.Edit',
        'match.AdminGrid',
        'match.Edit',
        'day.AdminGrid',
        'day.Edit',
        'limitdate.Grid',
        'limitdate.Edit',
        'profile.Grid',
        'profile.Edit',
        'profile.Select',
        'user.Grid',
        'user.Edit',
        'gymnasium.Grid',
        'gymnasium.Edit',
        'club.Grid',
        'club.Edit',
        'activity.Grid',
        'timeslot.WeekScheduleGrid',
        'rank.AdminGrid',
        'rank.Edit',
        'grid.HallOfFame',
        'window.HallOfFame'
    ],
    refs: [
        {
            ref: 'ImagePlayer',
            selector: 'playeredit image'
        },
        {
            ref: 'managePlayersGrid',
            selector: 'playersgrid'
        },
        {
            ref: 'manageProfilesGrid',
            selector: 'profilesgrid'
        },
        {
            ref: 'manageUsersGrid',
            selector: 'usersgrid'
        },
        {
            ref: 'manageGymnasiumsGrid',
            selector: 'gymnasiumsgrid'
        },
        {
            ref: 'manageClubsGrid',
            selector: 'clubsgrid'
        },
        {
            ref: 'manageTeamsGrid',
            selector: 'teamsgrid'
        },
        {
            ref: 'manageMatchesGrid',
            selector: 'matchesgrid'
        },
        {
            ref: 'manageRanksGrid',
            selector: 'rankgrid'
        },
        {
            ref: 'manageDaysGrid',
            selector: 'daysgrid'
        },
        {
            ref: 'manageLimitDatesGrid',
            selector: 'limitdatesgrid'
        },
        {
            ref: 'mainPanel',
            selector: 'tabpanel'
        },
        {
            ref: 'formPanelSelectClub',
            selector: 'clubselect form'
        },
        {
            ref: 'formPanelSelectProfile',
            selector: 'profileselect form'
        },
        {
            ref: 'formPanelSelectTeam',
            selector: 'teamselect form'
        },
        {
            ref: 'formPanelEditPlayer',
            selector: 'playeredit form'
        },
        {
            ref: 'formPanelEditProfile',
            selector: 'profileedit form'
        },
        {
            ref: 'formPanelEditUser',
            selector: 'useredit form'
        },
        {
            ref: 'formPanelEditGymnasium',
            selector: 'gymnasiumedit form'
        },
        {
            ref: 'formPanelEditClub',
            selector: 'clubedit form'
        },
        {
            ref: 'formPanelEditTeam',
            selector: 'teamedit form'
        },
        {
            ref: 'formPanelEditMatch',
            selector: 'matchedit form'
        },
        {
            ref: 'formPanelEditRank',
            selector: 'rankedit form'
        },
        {
            ref: 'formPanelEditDay',
            selector: 'dayedit form'
        },
        {
            ref: 'formPanelEditLimitDate',
            selector: 'limitdateedit form'
        },
        {
            ref: 'windowSelectClub',
            selector: 'clubselect'
        },
        {
            ref: 'windowSelectProfile',
            selector: 'profileselect'
        },
        {
            ref: 'windowSelectTeam',
            selector: 'teamselect'
        },
        {
            ref: 'windowEditPlayer',
            selector: 'playeredit'
        },
        {
            ref: 'windowEditProfile',
            selector: 'profileedit'
        },
        {
            ref: 'windowEditUser',
            selector: 'useredit'
        },
        {
            ref: 'windowEditGymnasium',
            selector: 'gymnasiumedit'
        },
        {
            ref: 'windowEditClub',
            selector: 'clubedit'
        },
        {
            ref: 'windowEditTeam',
            selector: 'teamedit'
        },
        {
            ref: 'windowEditMatch',
            selector: 'matchedit'
        },
        {
            ref: 'windowEditRank',
            selector: 'rankedit'
        },
        {
            ref: 'windowEditDay',
            selector: 'dayedit'
        },
        {
            ref: 'windowEditLimitDate',
            selector: 'limitdateedit'
        },
        {
            ref: 'displayFilteredCount',
            selector: 'displayfield[action=displayFilteredCount]'
        }
    ],
    init: function () {
        this.control(
            {
                'checkbox[action=filterPlayersWith2TeamsSameCompetition]': {
                    change: this.filterPlayersWith2TeamsSameCompetition
                },
                'checkbox[action=filterPlayersWithoutLicence]': {
                    change: this.filterPlayersWithoutLicence
                },
                'checkbox[action=filterPlayersWithoutClub]': {
                    change: this.filterPlayersWithoutClub
                },
                'checkbox[action=filterInactivePlayers]': {
                    change: this.filterInactivePlayers
                },
                'button[action=addPlayer]': {
                    click: this.addPlayer
                },
                'button[action=editPlayer]': {
                    click: this.editPlayer
                },
                'button[action=addProfile]': {
                    click: this.addProfile
                },
                'button[action=editProfile]': {
                    click: this.editProfile
                },
                'usersgrid button[action=add]': {
                    click: this.addUser
                },
                'gymnasiumsgrid button[action=add]': {
                    click: this.addGymnasium
                },
                'clubsgrid button[action=add]': {
                    click: this.addClub
                },
                'teamsgrid button[action=add]': {
                    click: this.addTeam
                },
                'matchesgrid button[action=add]': {
                    click: this.addMatch
                },
                'rankgrid button[action=add]': {
                    click: this.addRank
                },
                'daysgrid button[action=add]': {
                    click: this.addDay
                },
                'limitdatesgrid button[action=add]': {
                    click: this.addLimitDate
                },
                'usersgrid button[action=edit]': {
                    click: this.editUser
                },
                'gymnasiumsgrid button[action=edit]': {
                    click: this.editGymnasium
                },
                'clubsgrid button[action=edit]': {
                    click: this.editClub
                },
                'teamsgrid button[action=edit]': {
                    click: this.editTeam
                },
                'matchesgrid button[action=edit]': {
                    click: this.editMatch
                },
                'daysgrid button[action=edit]': {
                    click: this.editDay
                },
                'limitdatesgrid button[action=edit]': {
                    click: this.editLimitDate
                },
                'usersgrid button[action=delete]': {
                    click: this.deleteUsers
                },
                'gymnasiumsgrid button[action=delete]': {
                    click: this.deleteGymnasiums
                },
                'clubsgrid button[action=delete]': {
                    click: this.deleteClubs
                },
                'teamsgrid button[action=delete]': {
                    click: this.deleteTeams
                },
                'matchesgrid button[action=delete]': {
                    click: this.deleteMatches
                },
                'rankgrid button[action=delete]': {
                    click: this.deleteRanks
                },
                'rankgrid button[action=razPoints]': {
                    click: this.razPoints
                },
                'daysgrid button[action=delete]': {
                    click: this.deleteDays
                },
                'limitdatesgrid button[action=delete]': {
                    click: this.deleteLimitDates
                },
                'playersgrid': {
                    itemdblclick: this.editPlayer
                },
                'profilesgrid': {
                    itemdblclick: this.editProfile
                },
                'usersgrid': {
                    itemdblclick: this.editUser
                },
                'gymnasiumsgrid': {
                    itemdblclick: this.editGymnasium
                },
                'clubsgrid': {
                    itemdblclick: this.editClub
                },
                'teamsgrid': {
                    itemdblclick: this.editTeam
                },
                'matchesgrid': {
                    itemdblclick: this.editMatch
                },
                'rankgrid': {
                    itemdblclick: this.editRank
                },
                'daysgrid': {
                    itemdblclick: this.editDay
                },
                'limitdatesgrid': {
                    itemdblclick: this.editLimitDate
                },
                'playeredit button[action=save]': {
                    click: this.updatePlayer
                },
                'profileedit button[action=save]': {
                    click: this.updateProfile
                },
                'useredit button[action=save]': {
                    click: this.updateUser
                },
                'gymnasiumedit button[action=save]': {
                    click: this.updateGymnasium
                },
                'clubedit button[action=save]': {
                    click: this.updateClub
                },
                'teamedit button[action=save]': {
                    click: this.updateTeam
                },
                'matchedit button[action=save]': {
                    click: this.updateMatch
                },
                'rankedit button[action=save]': {
                    click: this.updateRank
                },
                'dayedit button[action=save]': {
                    click: this.updateDay
                },
                'limitdateedit button[action=save]': {
                    click: this.updateLimitDate
                },
                'button[action=displayActivity]': {
                    click: this.showActivityGrid
                },
                'button[action=managePlayers]': {
                    click: this.showPlayersGrid
                },
                'button[action=manageProfiles]': {
                    click: this.showProfilesGrid
                },
                'button[action=manageUsers]': {
                    click: this.showUsersGrid
                },
                'button[action=manageGymnasiums]': {
                    click: this.showGymnasiumsGrid
                },
                'button[action=manageClubs]': {
                    click: this.showClubsGrid
                },
                'button[action=manageTeams]': {
                    click: this.showTeamsGrid
                },
                'button[action=manageMatches]': {
                    click: this.showMatchesGrid
                },
                'button[action=manageRanks]': {
                    click: this.showRanksGrid
                },
                'button[action=manageDays]': {
                    click: this.showDaysGrid
                },
                'button[action=manageLimitDates]': {
                    click: this.showLimitDatesGrid
                },
                'button[action=displayWeekSchedule]': {
                    click: this.showWeekScheduleGrid
                },
                'button[action=showClubSelect]': {
                    click: this.showClubSelect
                },
                'button[action=showProfileSelect]': {
                    click: this.showProfileSelect
                },
                'button[action=showTeamSelect]': {
                    click: this.showTeamSelect
                },
                'clubselect button[action=save]': {
                    click: this.linkPlayerToClub
                },
                'profileselect button[action=save]': {
                    click: this.linkUsersToProfile
                },
                'teamselect button[action=save]': {
                    click: this.linkPlayerToTeam
                },
                'playersgrid button[action=delete]': {
                    click: this.deletePlayers
                },
                'button[action=displayIndicators]': {
                    click: this.displayIndicators
                },
                'grid > toolbar[dock=top] > textfield[fieldLabel=Recherche]': {
                    change: this.searchInGrid
                },
                'button[action=displayHallOfFame]': {
                    click: this.displayHallOfFame
                },
                'hall_of_fame_grid': {
                    added: this.addToolbarHallOfFame
                },
                'button[action=addHallOfFame]': {
                    click: this.addHallOfFame
                },
                'button[action=editHallOfFame]': {
                    click: this.editHallOfFame
                },
                'button[action=deleteHallOfFame]': {
                    click: this.deleteHallOfFame
                },
                'hall_of_fame_edit button[action=save]': {
                    click: this.updateHallOfFame
                },
            }
        );
    },
    displayIndicators: function () {
        var mainPanel = this.getMainPanel();
        mainPanel.setAutoScroll(true);
        var tab = mainPanel.add({
            title: 'Indicateurs',
            layout: {
                type: 'vbox',
                align: 'stretch'
            },
            autoScroll: true,
            items: []
        });
        mainPanel.setActiveTab(tab);
        var storeIndicators = Ext.create('Ext.data.Store', {
            fields: [
                'fieldLabel',
                'value',
                'details'
            ],
            proxy: {
                type: 'rest',
                url: 'ajax/indicators.php',
                reader: {
                    type: 'json',
                    root: 'results'
                }
            }
        });
        storeIndicators.load({
            callback: function (records) {
                Ext.each(records, function (record) {
                    var detailsData = record.get('details');
                    if (!detailsData) {
                        return;
                    }
                    var fields = [];
                    var columns = [];
                    for (var k in detailsData[0]) {
                        fields.push(k);
                        columns.push({
                            header: k,
                            dataIndex: k,
                            flex: 1
                        });
                    }
                    var indicatorPanel = Ext.ComponentQuery.query('panel[title=Indicateurs]')[0];
                    if (record.get('value') === 0) {
                        return;
                    }
                    indicatorPanel.add(
                        {
                            layout: 'hbox',
                            items: [
                                {
                                    xtype: 'displayfield',
                                    margin: 10,
                                    fieldLabel: record.get('fieldLabel'),
                                    labelWidth: 250,
                                    value: record.get('value'),
                                    width: 300
                                },
                                {
                                    xtype: 'button',
                                    margin: 10,
                                    text: 'Détails',
                                    handler: function () {
                                        Ext.create('Ext.window.Window', {
                                            title: 'Détails',
                                            height: 500,
                                            width: 700,
                                            maximizable: true,
                                            layout: 'fit',
                                            items: {
                                                xtype: 'grid',
                                                autoScroll: true,
                                                store: Ext.create('Ext.data.Store', {
                                                    fields: fields,
                                                    data: {
                                                        'items': detailsData
                                                    },
                                                    proxy: {
                                                        type: 'memory',
                                                        reader: {
                                                            type: 'json',
                                                            root: 'items'
                                                        }
                                                    }
                                                }),
                                                columns: columns
                                            },
                                            dockedItems: [
                                                {
                                                    xtype: 'toolbar',
                                                    dock: 'bottom',
                                                    items: [
                                                        {
                                                            text: 'Télécharger',
                                                            handler: function (button) {
                                                                button.up('window').down('grid').downloadExcelXml(false, 'Rapport');
                                                            }
                                                        }
                                                    ]
                                                }
                                            ]
                                        }).show();
                                    }
                                }
                            ]
                        });
                });
            }
        });
    },
    filterPlayersWithoutClub: function (checkbox, newValue) {
        var store = this.getPlayersStore();
        if (newValue !== true) {
            store.clearFilter();
            this.getDisplayFilteredCount().setValue(store.getCount());
            return;
        }
        store.clearFilter(true);
        store.filter(
            {
                filterFn: function (item) {
                    return ((item.get('teams_list') !== null) && (item.get('id_club') === 0));
                }
            }
        );
        this.getDisplayFilteredCount().setValue(store.getCount());
    },
    filterPlayersWithoutLicence: function (checkbox, newValue) {
        var store = this.getPlayersStore();
        if (newValue !== true) {
            store.clearFilter();
            this.getDisplayFilteredCount().setValue(store.getCount());
            return;
        }
        store.clearFilter(true);
        store.filter(
            {
                filterFn: function (item) {
                    return ((item.get('teams_list') !== null) && (item.get('num_licence').length === 0));
                }
            }
        );
        this.getDisplayFilteredCount().setValue(store.getCount());
    },
    filterPlayersWith2TeamsSameCompetition: function (checkbox, newValue) {
        var store = this.getPlayersStore();
        if (newValue !== true) {
            store.clearFilter();
            this.getDisplayFilteredCount().setValue(store.getCount());
            return;
        }
        store.clearFilter(true);
        store.filter(
            {
                filterFn: function (item) {
                    if (item.get('teams_list') !== null) {
                        var countM = (item.get('teams_list').match(/\(m\)/g) || []).length;
                        var countF = (item.get('teams_list').match(/\(f\)/g) || []).length;
                        var countKH = (item.get('teams_list').match(/\(kh\)/g) || []).length;
                        var countC = (item.get('teams_list').match(/\(c\)/g) || []).length;
                        return ((countM > 1) || (countF > 1) || (countKH > 1) || (countC > 1));
                    }
                    return false;
                }
            }
        );
        this.getDisplayFilteredCount().setValue(store.getCount());
    },
    filterInactivePlayers: function (checkbox, newValue) {
        var store = this.getPlayersStore();
        if (newValue !== true) {
            store.clearFilter();
            this.getDisplayFilteredCount().setValue(store.getCount());
            return;
        }
        store.clearFilter(true);
        store.filter(
            {
                filterFn: function (item) {
                    return ((item.get('num_licence') !== null) && (item.get('est_actif') === false) && (item.get('teams_list') !== null));
                }
            }
        );
        this.getDisplayFilteredCount().setValue(store.getCount());
    },
    searchInGrid: function (textfield, searchText) {
        var searchTerms = searchText.split(',');
        var store = textfield.up('grid').getStore();
        store.clearFilter(true);
        var model = store.first();
        if (!model) {
            this.getDisplayFilteredCount().setValue(store.getCount());
            return;
        }
        store.filter(
            {
                filterFn: function (item) {
                    var fields = model.getFields();
                    var queribleFields = [];
                    Ext.each(fields, function (field) {
                        if (field.getType() === 'string' || field.getType() === 'auto') {
                            Ext.Array.push(queribleFields, field.getName());
                        }
                    });
                    var found = false;
                    Ext.each(searchTerms, function (searchTerm) {
                        var regExp = new RegExp(searchTerm, "i");
                        Ext.each(queribleFields, function (queribleField) {
                            if (!item.get(queribleField)) {
                                return true;
                            }
                            if (regExp.test(item.get(queribleField))) {
                                found = true;
                                return false;
                            }
                        });
                        return !found;
                    });
                    return found;
                }
            }
        );
        this.getDisplayFilteredCount().setValue(store.getCount());
    },
    editPlayer: function () {
        var record = this.getManagePlayersGrid().getSelectionModel().getSelection()[0];
        if (!record) {
            return;
        }
        Ext.widget('playeredit');
        this.getFormPanelEditPlayer().loadRecord(record);
        this.getImagePlayer().show();
        this.getImagePlayer().setSrc(record.get('path_photo'));
        this.getFormPanelEditPlayer().down('textfield[name=prenom]').focus();
    },
    editProfile: function () {
        var record = this.getManageProfilesGrid().getSelectionModel().getSelection()[0];
        if (!record) {
            return;
        }
        Ext.widget('profileedit');
        this.getFormPanelEditProfile().loadRecord(record);
    },
    editUser: function () {
        var record = this.getManageUsersGrid().getSelectionModel().getSelection()[0];
        if (!record) {
            return;
        }
        Ext.widget('useredit');
        this.getFormPanelEditUser().loadRecord(record);
    },
    editGymnasium: function () {
        var record = this.getManageGymnasiumsGrid().getSelectionModel().getSelection()[0];
        if (!record) {
            return;
        }
        Ext.widget('gymnasiumedit');
        this.getFormPanelEditGymnasium().loadRecord(record);
    },
    editClub: function () {
        var record = this.getManageClubsGrid().getSelectionModel().getSelection()[0];
        if (!record) {
            return;
        }
        Ext.widget('clubedit');
        this.getFormPanelEditClub().loadRecord(record);
    },
    editTeam: function () {
        var record = this.getManageTeamsGrid().getSelectionModel().getSelection()[0];
        if (!record) {
            return;
        }
        Ext.widget('teamedit');
        this.getFormPanelEditTeam().loadRecord(record);
    },
    editMatch: function () {
        var record = this.getManageMatchesGrid().getSelectionModel().getSelection()[0];
        if (!record) {
            return;
        }
        Ext.widget('matchedit');
        this.getFormPanelEditMatch().loadRecord(record);
    },
    editRank: function () {
        var record = this.getManageRanksGrid().getSelectionModel().getSelection()[0];
        if (!record) {
            return;
        }
        Ext.widget('rankedit');
        this.getFormPanelEditRank().loadRecord(record);
    },
    editDay: function () {
        var record = this.getManageDaysGrid().getSelectionModel().getSelection()[0];
        if (!record) {
            return;
        }
        Ext.widget('dayedit');
        this.getFormPanelEditDay().loadRecord(record);
    },
    editLimitDate: function () {
        var record = this.getManageLimitDatesGrid().getSelectionModel().getSelection()[0];
        if (!record) {
            return;
        }
        Ext.widget('limitdateedit');
        this.getFormPanelEditLimitDate().loadRecord(record);
    },
    addPlayer: function () {
        Ext.widget('playeredit');
        this.getImagePlayer().hide();
        this.getFormPanelEditPlayer().down('textfield[name=prenom]').focus();
    },
    addProfile: function () {
        Ext.widget('profileedit');
    },
    addUser: function () {
        Ext.widget('useredit');
    },
    addGymnasium: function () {
        Ext.widget('gymnasiumedit');
    },
    addClub: function () {
        Ext.widget('clubedit');
    },
    addTeam: function () {
        Ext.widget('teamedit');
    },
    addMatch: function () {
        Ext.widget('matchedit');
        var record = this.getManageMatchesGrid().getSelectionModel().getSelection()[0];
        if (!record) {
            return;
        }
        this.getFormPanelEditMatch().loadRecord(record);
        this.getFormPanelEditMatch().getForm().findField('id_match').setValue("");
        this.getFormPanelEditMatch().getForm().findField('code_match').setValue("");
    },
    addRank: function () {
        Ext.widget('rankedit');
    },
    addDay: function () {
        Ext.widget('dayedit');
    },
    addLimitDate: function () {
        Ext.widget('limitdateedit');
    },
    addHallOfFame: function () {
        Ext.widget('hall_of_fame_edit');
    },
    editHallOfFame: function (button) {
        var grid = button.up('grid');
        var record = grid.getSelectionModel().getSelection()[0];
        if (!record) {
            return;
        }
        var windowEdit = Ext.widget('hall_of_fame_edit');
        windowEdit.down('form').loadRecord(record);
    },
    deleteHallOfFame: function (button) {
        var grid = button.up('grid');
        var records = grid.getSelectionModel().getSelection();
        if (records.length == 0) {
            return;
        }
        Ext.Msg.show({
            title: 'Supprimer?',
            msg: 'Etes-vous certain de vouloir supprimer ces lignes?',
            buttons: Ext.Msg.YESNO,
            icon: Ext.Msg.QUESTION,
            fn: function (btn) {
                if (btn !== 'yes') {
                    return;
                }
                var ids = [];
                Ext.each(records, function (record) {
                    ids.push(record.get('id'));
                });
                Ext.Ajax.request({
                    url: 'ajax/deleteHallOfFame.php',
                    params: {
                        ids: ids.join(',')
                    },
                    success: function () {
                        grid.getStore().load();
                    }
                });
            }
        });
    },
    updateHallOfFame: function (button) {
        var thisController = this;
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
                },
                success: function () {
                    thisController.getStore('HallOfFame').load();
                    button.up('window').close();
                },
                failure: function (form, action) {
                    Ext.Msg.alert('Erreur', action.result.message);
                }
            });
        }
    },
    deleteUsers: function () {
        var me = this;
        var records = this.getManageUsersGrid().getSelectionModel().getSelection();
        if (records.length == 0) {
            return;
        }
        Ext.Msg.show({
            title: 'Supprimer?',
            msg: 'Etes-vous certain de vouloir supprimer ces lignes?',
            buttons: Ext.Msg.YESNO,
            icon: Ext.Msg.QUESTION,
            fn: function (btn) {
                if (btn !== 'yes') {
                    return;
                }
                var ids = [];
                Ext.each(records, function (record) {
                    ids.push(record.get('id'));
                });
                Ext.Ajax.request({
                    url: 'ajax/deleteUsers.php',
                    params: {
                        ids: ids.join(',')
                    },
                    success: function () {
                        me.getUsersStore().load();
                    }
                });
            }
        });
    },
    deleteGymnasiums: function () {
        var me = this;
        var records = this.getManageGymnasiumsGrid().getSelectionModel().getSelection();
        if (records.length == 0) {
            return;
        }
        Ext.Msg.show({
            title: 'Supprimer?',
            msg: 'Etes-vous certain de vouloir supprimer ces lignes?',
            buttons: Ext.Msg.YESNO,
            icon: Ext.Msg.QUESTION,
            fn: function (btn) {
                if (btn !== 'yes') {
                    return;
                }
                var ids = [];
                Ext.each(records, function (record) {
                    ids.push(record.get('id'));
                });
                Ext.Ajax.request({
                    url: 'ajax/deleteGymnasiums.php',
                    params: {
                        ids: ids.join(',')
                    },
                    success: function () {
                        me.getGymnasiumsStore().load();
                    }
                });
            }
        });
    },
    deleteClubs: function () {
        var me = this;
        var records = this.getManageClubsGrid().getSelectionModel().getSelection();
        if (records.length == 0) {
            return;
        }
        Ext.Msg.show({
            title: 'Supprimer?',
            msg: 'Etes-vous certain de vouloir supprimer ces lignes?',
            buttons: Ext.Msg.YESNO,
            icon: Ext.Msg.QUESTION,
            fn: function (btn) {
                if (btn !== 'yes') {
                    return;
                }
                var ids = [];
                Ext.each(records, function (record) {
                    ids.push(record.get('id'));
                });
                Ext.Ajax.request({
                    url: 'ajax/deleteClubs.php',
                    params: {
                        ids: ids.join(',')
                    },
                    success: function () {
                        me.getClubsStore().load();
                    }
                });
            }
        });
    },
    deleteTeams: function () {
        var me = this;
        var records = this.getManageTeamsGrid().getSelectionModel().getSelection();
        if (records.length == 0) {
            return;
        }
        Ext.Msg.show({
            title: 'Supprimer?',
            msg: 'Etes-vous certain de vouloir supprimer ces lignes?',
            buttons: Ext.Msg.YESNO,
            icon: Ext.Msg.QUESTION,
            fn: function (btn) {
                if (btn !== 'yes') {
                    return;
                }
                var ids = [];
                Ext.each(records, function (record) {
                    ids.push(record.get('id_equipe'));
                });
                Ext.Ajax.request({
                    url: 'ajax/deleteTeams.php',
                    params: {
                        ids: ids.join(',')
                    },
                    success: function () {
                        me.getTeamsStore().load();
                    }
                });
            }
        });
    },
    deleteMatches: function () {
        var me = this;
        var records = this.getManageMatchesGrid().getSelectionModel().getSelection();
        if (records.length == 0) {
            return;
        }
        Ext.Msg.show({
            title: 'Supprimer?',
            msg: 'Etes-vous certain de vouloir supprimer ces lignes?',
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
                    url: 'ajax/deleteMatches.php',
                    params: {
                        ids: ids.join(',')
                    },
                    success: function () {
                        me.getAdminMatchesStore().load();
                    }
                });
            }
        });
    },
    deleteRanks: function () {
        var me = this;
        var records = this.getManageRanksGrid().getSelectionModel().getSelection();
        if (records.length == 0) {
            return;
        }
        Ext.Msg.show({
            title: 'Supprimer?',
            msg: 'Etes-vous certain de vouloir supprimer ces lignes?',
            buttons: Ext.Msg.YESNO,
            icon: Ext.Msg.QUESTION,
            fn: function (btn) {
                if (btn !== 'yes') {
                    return;
                }
                var ids = [];
                Ext.each(records, function (record) {
                    ids.push(record.get('id'));
                });
                Ext.Ajax.request({
                    url: 'ajax/deleteRanks.php',
                    params: {
                        ids: ids.join(',')
                    },
                    success: function () {
                        me.getAdminRanksStore().load();
                    }
                });
            }
        });
    },
    razPoints: function () {
        var me = this;
        var records = this.getManageRanksGrid().getSelectionModel().getSelection();
        if (records.length == 0) {
            return;
        }
        Ext.Msg.show({
            title: 'Remise à zéro?',
            msg: 'Etes-vous certain de vouloir effectuer une remise à zéro des points de cette/ces équipe(s)?',
            buttons: Ext.Msg.YESNO,
            icon: Ext.Msg.QUESTION,
            fn: function (btn) {
                if (btn !== 'yes') {
                    return;
                }
                var ids = [];
                Ext.each(records, function (record) {
                    ids.push(record.get('id'));
                });
                Ext.Ajax.request({
                    url: 'ajax/razPoints.php',
                    params: {
                        ids: ids.join(',')
                    },
                    success: function () {
                        me.getAdminRanksStore().load();
                    }
                });
            }
        });
    },
    deleteDays: function () {
        var me = this;
        var records = this.getManageDaysGrid().getSelectionModel().getSelection();
        if (records.length == 0) {
            return;
        }
        Ext.Msg.show({
            title: 'Supprimer?',
            msg: 'Etes-vous certain de vouloir supprimer ces lignes?',
            buttons: Ext.Msg.YESNO,
            icon: Ext.Msg.QUESTION,
            fn: function (btn) {
                if (btn !== 'yes') {
                    return;
                }
                var ids = [];
                Ext.each(records, function (record) {
                    ids.push(record.get('id'));
                });
                Ext.Ajax.request({
                    url: 'ajax/deleteDays.php',
                    params: {
                        ids: ids.join(',')
                    },
                    success: function () {
                        me.getAdminDaysStore().load();
                    }
                });
            }
        });
    },
    deleteLimitDates: function () {
        var me = this;
        var records = this.getManageLimitDatesGrid().getSelectionModel().getSelection();
        if (records.length == 0) {
            return;
        }
        Ext.Msg.show({
            title: 'Supprimer?',
            msg: 'Etes-vous certain de vouloir supprimer ces lignes?',
            buttons: Ext.Msg.YESNO,
            icon: Ext.Msg.QUESTION,
            fn: function (btn) {
                if (btn !== 'yes') {
                    return;
                }
                var ids = [];
                Ext.each(records, function (record) {
                    ids.push(record.get('id_date'));
                });
                Ext.Ajax.request({
                    url: 'ajax/deleteLimitDates.php',
                    params: {
                        ids: ids.join(',')
                    },
                    success: function () {
                        me.getLimitDatesStore().load();
                    }
                });
            }
        });
    },
    deletePlayers: function () {
        var me = this;
        var records = this.getManagePlayersGrid().getSelectionModel().getSelection();
        if (records.length == 0) {
            return;
        }
        Ext.Msg.show({
            title: 'Supprimer?',
            msg: 'Etes-vous certain de vouloir supprimer ces lignes?',
            buttons: Ext.Msg.YESNO,
            icon: Ext.Msg.QUESTION,
            fn: function (btn) {
                if (btn !== 'yes') {
                    return;
                }
                var ids = [];
                Ext.each(records, function (record) {
                    ids.push(record.get('id'));
                });
                Ext.Ajax.request({
                    url: 'ajax/deletePlayers.php',
                    params: {
                        ids: ids.join(',')
                    },
                    success: function () {
                        me.getPlayersStore().load();
                    }
                });
            }
        });
    },
    updatePlayer: function () {
        var thisController = this;
        var form = this.getFormPanelEditPlayer().getForm();
        if (form.isValid()) {
            var dirtyFieldsJson = form.getFieldValues(true);
            var dirtyFieldsArray = [];
            for (var key in dirtyFieldsJson) {
                dirtyFieldsArray.push(key);
            }
            form.submit({
                params: {
                    dirtyFields: dirtyFieldsArray.join(',')
                },
                success: function () {
                    thisController.getPlayersStore().load();
                    thisController.getWindowEditPlayer().close();
                    thisController.getManagePlayersGrid().getSelectionModel().deselectAll();
                },
                failure: function (form, action) {
                    Ext.Msg.alert('Erreur', action.result.message);
                }
            });
        }
    },
    updateProfile: function () {
        var thisController = this;
        var form = this.getFormPanelEditProfile().getForm();
        if (form.isValid()) {
            var dirtyFieldsJson = form.getFieldValues(true);
            var dirtyFieldsArray = [];
            for (var key in dirtyFieldsJson) {
                dirtyFieldsArray.push(key);
            }
            form.submit({
                params: {
                    dirtyFields: dirtyFieldsArray.join(',')
                },
                success: function () {
                    thisController.getProfilesStore().load();
                    thisController.getWindowEditProfile().close();
                    thisController.getManageProfilesGrid().getSelectionModel().deselectAll();
                },
                failure: function (form, action) {
                    Ext.Msg.alert('Erreur', action.result.message);
                }
            });
        }
    },
    updateUser: function () {
        var thisController = this;
        var form = this.getFormPanelEditUser().getForm();
        if (form.isValid()) {
            var dirtyFieldsJson = form.getFieldValues(true);
            var dirtyFieldsArray = [];
            for (var key in dirtyFieldsJson) {
                dirtyFieldsArray.push(key);
            }
            form.submit({
                params: {
                    dirtyFields: dirtyFieldsArray.join(',')
                },
                success: function () {
                    thisController.getUsersStore().load();
                    thisController.getWindowEditUser().close();
                    thisController.getManageUsersGrid().getSelectionModel().deselectAll();
                },
                failure: function (form, action) {
                    Ext.Msg.alert('Erreur', action.result.message);
                }
            });
        }
    },
    updateGymnasium: function () {
        var thisController = this;
        var form = this.getFormPanelEditGymnasium().getForm();
        if (form.isValid()) {
            var dirtyFieldsJson = form.getFieldValues(true);
            var dirtyFieldsArray = [];
            for (var key in dirtyFieldsJson) {
                dirtyFieldsArray.push(key);
            }
            form.submit({
                params: {
                    dirtyFields: dirtyFieldsArray.join(',')
                },
                success: function () {
                    thisController.getGymnasiumsStore().load();
                    thisController.getWindowEditGymnasium().close();
                    thisController.getManageGymnasiumsGrid().getSelectionModel().deselectAll();
                },
                failure: function (form, action) {
                    Ext.Msg.alert('Erreur', action.result.message);
                }
            });
        }
    },
    updateClub: function () {
        var thisController = this;
        var form = this.getFormPanelEditClub().getForm();
        if (form.isValid()) {
            var dirtyFieldsJson = form.getFieldValues(true);
            var dirtyFieldsArray = [];
            for (var key in dirtyFieldsJson) {
                dirtyFieldsArray.push(key);
            }
            form.submit({
                params: {
                    dirtyFields: dirtyFieldsArray.join(',')
                },
                success: function () {
                    thisController.getClubsStore().load();
                    thisController.getWindowEditClub().close();
                    thisController.getManageClubsGrid().getSelectionModel().deselectAll();
                },
                failure: function (form, action) {
                    Ext.Msg.alert('Erreur', action.result.message);
                }
            });
        }
    },
    updateTeam: function () {
        var thisController = this;
        var form = this.getFormPanelEditTeam().getForm();
        if (form.isValid()) {
            var dirtyFieldsJson = form.getFieldValues(true);
            var dirtyFieldsArray = [];
            for (var key in dirtyFieldsJson) {
                dirtyFieldsArray.push(key);
            }
            form.submit({
                params: {
                    dirtyFields: dirtyFieldsArray.join(',')
                },
                success: function () {
                    thisController.getTeamsStore().load();
                    thisController.getWindowEditTeam().close();
                    thisController.getManageTeamsGrid().getSelectionModel().deselectAll();
                },
                failure: function (form, action) {
                    Ext.Msg.alert('Erreur', action.result.message);
                }
            });
        }
    },
    updateMatch: function () {
        var thisController = this;
        var form = this.getFormPanelEditMatch().getForm();
        if (form.isValid()) {
            var dirtyFieldsJson = form.getFieldValues(true);
            var dirtyFieldsArray = [];
            for (var key in dirtyFieldsJson) {
                dirtyFieldsArray.push(key);
            }
            form.submit({
                params: {
                    dirtyFields: dirtyFieldsArray.join(',')
                },
                success: function (form, action) {
                    Ext.Msg.alert('Modification OK', action.result.message);
                    thisController.getAdminMatchesStore().load();
                    thisController.getWindowEditMatch().close();
                    thisController.getManageMatchesGrid().getSelectionModel().deselectAll();
                },
                failure: function (form, action) {
                    Ext.Msg.alert('Erreur', action.result.message);
                }
            });
        }
    },
    updateRank: function () {
        var thisController = this;
        var form = this.getFormPanelEditRank().getForm();
        if (form.isValid()) {
            var dirtyFieldsJson = form.getFieldValues(true);
            var dirtyFieldsArray = [];
            for (var key in dirtyFieldsJson) {
                dirtyFieldsArray.push(key);
            }
            form.submit({
                params: {
                    dirtyFields: dirtyFieldsArray.join(',')
                },
                success: function () {
                    thisController.getAdminRanksStore().load();
                    thisController.getWindowEditRank().close();
                    thisController.getManageRanksGrid().getSelectionModel().deselectAll();
                },
                failure: function (form, action) {
                    Ext.Msg.alert('Erreur', action.result.message);
                }
            });
        }
    },
    updateDay: function () {
        var thisController = this;
        var form = this.getFormPanelEditDay().getForm();
        if (form.isValid()) {
            var dirtyFieldsJson = form.getFieldValues(true);
            var dirtyFieldsArray = [];
            for (var key in dirtyFieldsJson) {
                dirtyFieldsArray.push(key);
            }
            form.submit({
                params: {
                    dirtyFields: dirtyFieldsArray.join(',')
                },
                success: function () {
                    thisController.getAdminDaysStore().load();
                    thisController.getWindowEditDay().close();
                    thisController.getManageDaysGrid().getSelectionModel().deselectAll();
                },
                failure: function (form, action) {
                    Ext.Msg.alert('Erreur', action.result.message);
                }
            });
        }
    },
    updateLimitDate: function () {
        var thisController = this;
        var form = this.getFormPanelEditLimitDate().getForm();
        if (form.isValid()) {
            var dirtyFieldsJson = form.getFieldValues(true);
            var dirtyFieldsArray = [];
            for (var key in dirtyFieldsJson) {
                dirtyFieldsArray.push(key);
            }
            form.submit({
                params: {
                    dirtyFields: dirtyFieldsArray.join(',')
                },
                success: function () {
                    thisController.getLimitDatesStore().load();
                    thisController.getWindowEditLimitDate().close();
                    thisController.getManageLimitDatesGrid().getSelectionModel().deselectAll();
                },
                failure: function (form, action) {
                    Ext.Msg.alert('Erreur', action.result.message);
                }
            });
        }
    },
    showActivityGrid: function () {
        var tab = this.getMainPanel().add({
            xtype: 'activitygrid'
        });
        this.getMainPanel().setActiveTab(tab);
    },
    showPlayersGrid: function () {
        var tab = this.getMainPanel().add({
            xtype: 'playersgrid'
        });
        this.getMainPanel().setActiveTab(tab);
    },
    showProfilesGrid: function () {
        var tab = this.getMainPanel().add({
            xtype: 'profilesgrid'
        });
        this.getMainPanel().setActiveTab(tab);
    },
    showUsersGrid: function () {
        var tab = this.getMainPanel().add({
            xtype: 'usersgrid'
        });
        this.getMainPanel().setActiveTab(tab);
    },
    showWeekScheduleGrid: function () {
        var tab = this.getMainPanel().add({
            xtype: 'weekschedulegrid'
        });
        this.getMainPanel().setActiveTab(tab);
    },
    showGymnasiumsGrid: function () {
        var tab = this.getMainPanel().add({
            xtype: 'gymnasiumsgrid'
        });
        this.getMainPanel().setActiveTab(tab);
    },
    showClubsGrid: function () {
        var tab = this.getMainPanel().add({
            xtype: 'clubsgrid'
        });
        this.getMainPanel().setActiveTab(tab);
    },
    showTeamsGrid: function () {
        var tab = this.getMainPanel().add({
            xtype: 'teamsgrid'
        });
        this.getMainPanel().setActiveTab(tab);
    },
    showMatchesGrid: function () {
        var tab = this.getMainPanel().add({
            xtype: 'matchesgrid'
        });
        this.getMainPanel().setActiveTab(tab);
    },
    showRanksGrid: function () {
        var tab = this.getMainPanel().add({
            xtype: 'rankgrid'
        });
        this.getMainPanel().setActiveTab(tab);
    },
    showDaysGrid: function () {
        var tab = this.getMainPanel().add({
            xtype: 'daysgrid'
        });
        this.getMainPanel().setActiveTab(tab);
    },
    showLimitDatesGrid: function () {
        var tab = this.getMainPanel().add({
            xtype: 'limitdatesgrid'
        });
        this.getMainPanel().setActiveTab(tab);
    },
    showClubSelect: function () {
        var records = this.getManagePlayersGrid().getSelectionModel().getSelection();
        var idPlayers = [];
        Ext.each(records, function (record) {
            idPlayers.push(record.get('id'));
        });
        if (idPlayers.length === 0) {
            return;
        }
        Ext.widget('clubselect');
        this.getFormPanelSelectClub().getForm().setValues({
            id_players: idPlayers.join(',')
        });
    },
    showProfileSelect: function () {
        var records = this.getManageUsersGrid().getSelectionModel().getSelection();
        var idUsers = [];
        Ext.each(records, function (record) {
            idUsers.push(record.get('id'));
        });
        if (idUsers.length === 0) {
            return;
        }
        Ext.widget('profileselect');
        this.getFormPanelSelectProfile().getForm().setValues({
            id_users: idUsers.join(',')
        });
    },
    showTeamSelect: function () {
        var records = this.getManagePlayersGrid().getSelectionModel().getSelection();
        var idPlayers = [];
        Ext.each(records, function (record) {
            idPlayers.push(record.get('id'));
        });
        if (idPlayers.length === 0) {
            return;
        }
        Ext.widget('teamselect');
        this.getFormPanelSelectTeam().getForm().setValues({
            id_players: idPlayers.join(',')
        });
    },
    linkPlayerToClub: function () {
        var thisController = this;
        var form = this.getFormPanelSelectClub().getForm();
        if (form.isValid()) {
            form.submit({
                success: function () {
                    thisController.getPlayersStore().load();
                    thisController.getWindowSelectClub().close();
                },
                failure: function (form, action) {
                    Ext.Msg.alert('Erreur', action.result.message);
                }
            });
        }
    },
    linkUsersToProfile: function () {
        var thisController = this;
        var form = this.getFormPanelSelectProfile().getForm();
        if (form.isValid()) {
            form.submit({
                success: function () {
                    thisController.getUsersStore().load();
                    thisController.getWindowSelectProfile().close();
                },
                failure: function (form, action) {
                    Ext.Msg.alert('Erreur', action.result.message);
                }
            });
        }
    },
    linkPlayerToTeam: function () {
        var thisController = this;
        var form = this.getFormPanelSelectTeam().getForm();
        if (form.isValid()) {
            form.submit({
                success: function () {
                    thisController.getPlayersStore().load();
                    thisController.getWindowSelectTeam().close();
                },
                failure: function (form, action) {
                    Ext.Msg.alert('Erreur', action.result.message);
                }
            });
        }
    },
    displayHallOfFame: function () {
        var tab = this.getMainPanel().add({
            xtype: 'hall_of_fame_grid'
        });
        this.getMainPanel().setActiveTab(tab);
    },
    addToolbarHallOfFame: function (grid) {
        grid.addDocked({
            xtype: 'toolbar',
            dock: 'top',
            items: [
                'ACTIONS',
                {
                    xtype: 'tbseparator'
                },
                {
                    text: 'Ajouter',
                    action: 'addHallOfFame'
                },
                {
                    text: 'Modifier',
                    action: 'editHallOfFame'
                },
                {
                    text: 'Supprimer',
                    action: 'deleteHallOfFame'
                }
            ]
        });
        grid.addDocked({
            xtype: 'toolbar',
            dock: 'top',
            items: [
                'FILTRES',
                {
                    xtype: 'tbseparator'
                },
                {
                    xtype: 'textfield',
                    fieldLabel: 'Recherche'
                },
                {
                    xtype: 'displayfield',
                    fieldLabel: 'Total',
                    action: 'displayFilteredCount'
                }
            ]
        });
    }
});