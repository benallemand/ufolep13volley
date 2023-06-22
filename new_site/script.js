var Base64 = {
    _keyStr: "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/=",
    encode: function (e) {
        if (!e) {
            return e;
        }
        var t = "";
        var n, r, i, s, o, u, a;
        var f = 0;
        e = Base64._utf8_encode(e);
        while (f < e.length) {
            n = e.charCodeAt(f++);
            r = e.charCodeAt(f++);
            i = e.charCodeAt(f++);
            s = n >> 2;
            o = (n & 3) << 4 | r >> 4;
            u = (r & 15) << 2 | i >> 6;
            a = i & 63;
            if (isNaN(r)) {
                u = a = 64;
            } else if (isNaN(i)) {
                a = 64;
            }
            t = t + this._keyStr.charAt(s) + this._keyStr.charAt(o) + this._keyStr.charAt(u) + this._keyStr.charAt(a);
        }
        return t;
    }, decode: function (e) {
        var t = "";
        var n, r, i;
        var s, o, u, a;
        var f = 0;
        e = e.replace(/[^A-Za-z0-9+/=]/g, "");
        while (f < e.length) {
            s = this._keyStr.indexOf(e.charAt(f++));
            o = this._keyStr.indexOf(e.charAt(f++));
            u = this._keyStr.indexOf(e.charAt(f++));
            a = this._keyStr.indexOf(e.charAt(f++));
            n = s << 2 | o >> 4;
            r = (o & 15) << 4 | u >> 2;
            i = (u & 3) << 6 | a;
            t = t + String.fromCharCode(n);
            if (u != 64) {
                t = t + String.fromCharCode(r);
            }
            if (a != 64) {
                t = t + String.fromCharCode(i);
            }
        }
        t = Base64._utf8_decode(t);
        return t;
    }, _utf8_encode: function (e) {
        e = e.replace(/rn/g, "n");
        var t = "";
        for (var n = 0; n < e.length; n++) {
            var r = e.charCodeAt(n);
            if (r < 128) {
                t += String.fromCharCode(r);
            } else if (r > 127 && r < 2048) {
                t += String.fromCharCode(r >> 6 | 192);
                t += String.fromCharCode(r & 63 | 128);
            } else {
                t += String.fromCharCode(r >> 12 | 224);
                t += String.fromCharCode(r >> 6 & 63 | 128);
                t += String.fromCharCode(r & 63 | 128);
            }
        }
        return t;
    }, _utf8_decode: function (e) {
        var t = "";
        var n = 0;
        var r = 0;
        var c1 = 0;
        var c2 = 0;
        var c3 = 0;
        while (n < e.length) {
            r = e.charCodeAt(n);
            if (r < 128) {
                t += String.fromCharCode(r);
                n++;
            } else if (r > 191 && r < 224) {
                c2 = e.charCodeAt(n + 1);
                t += String.fromCharCode((r & 31) << 6 | c2 & 63);
                n += 2;
            } else {
                c2 = e.charCodeAt(n + 1);
                c3 = e.charCodeAt(n + 2);
                t += String.fromCharCode((r & 15) << 12 | (c2 & 63) << 6 | c3 & 63);
                n += 3;
            }
        }
        return t;
    }
};

// create the module and name it scotchApp
var scotchApp = angular.module('scotchApp', ['ngRoute', 'ngAnimate', 'ui.bootstrap', 'angular.filter', 'angular-loading-bar', 'filters', 'ngSanitize']);


angular.module('filters', []).filter('zpad', function () {
    return function (input, n) {
        if (input === undefined)
            input = "";
        if (input.length >= n)
            return input;
        var zeros = "0".repeat(n);
        return (zeros + input).slice(-1 * n)
    };
});

// configure our routes
scotchApp.config(function ($routeProvider) {
    $routeProvider
        .when('/', {
            templateUrl: 'pages/home.html',
            controller: 'mainController'
        })
        .when('/lastResults', {
            templateUrl: 'pages/lastResults.html',
            controller: 'lastResultsController'
        })
        .when('/weekMatches', {
            templateUrl: 'pages/matches_of_the_week.html',
            controller: 'weekMatchesController'
        })
        .when('/webSites', {
            templateUrl: 'pages/webSites.html',
            controller: 'webSitesController'
        })
        .when('/hallOfFame', {
            templateUrl: 'pages/hallOfFame.html',
            controller: 'hallOfFameController'
        })
        .when('/gymnasiums', {
            templateUrl: 'pages/gymnasiums.html',
            controller: 'gymnasiumsController'
        })
        .when('/championship/:competition/:division', {
            templateUrl: 'pages/championship.html',
            controller: 'championshipController'
        })
        .when('/matches/:competition', {
            templateUrl: 'pages/matches.html',
            controller: 'cupController'
        })
        .when('/phonebooks', {
            templateUrl: 'pages/phonebooks.html',
            controller: 'phonebooksController'
        })
        .when('/phonebook/:id', {
            templateUrl: 'pages/phonebook.html',
            controller: 'phonebookController'
        })
        .when('/accident', {
            templateUrl: 'pages/accident.html'
        })
        .when('/commission', {
            templateUrl: 'pages/commission.html',
            controller: 'commissionController'
        })
        .when('/login', {
            templateUrl: 'pages/login.html'
        })
        .when('/register', {
            templateUrl: 'pages/add_new_user.html',
            controller: 'registerController'
        })
        .when('/myPage', {
            templateUrl: 'pages/my_page.html',
            controller: 'myPageController'
        })
        .when('/myClub', {
            templateUrl: 'pages/my_club.html',
            controller: 'myClubController'
        })
        .when('/adminPage', {
            templateUrl: '../admin.php'
        })
        .when('/myHistory', {
            templateUrl: 'pages/history.html',
            controller: 'myHistoryController'
        })
        .when('/myPlayers', {
            templateUrl: 'pages/my_players.html',
            controller: 'myPlayersController'
        })
        .when('/myTeam', {
            templateUrl: 'pages/my_team.html',
            controller: 'myTeamController'
        })
        .when('/myTimeslots', {
            templateUrl: 'pages/my_timeslots.html',
            controller: 'myTimeslotsController'
        })
        .when('/myPassword', {
            templateUrl: 'pages/my_password.html',
            controller: 'myPasswordController'
        })
        .when('/myPreferences', {
            templateUrl: 'pages/my_preferences.html',
            controller: 'myPreferencesController'
        })
        .when('/usefulInformations', {
            templateUrl: 'pages/useful_informations.html'
        })
        .when('/generalRules', {
            templateUrl: 'pages/general_rules.html'
        });
});

scotchApp.controller('mainController', ['$scope', '$http', 'multipartForm', function ($scope, $http, multipartForm) {
    $scope.today = new Date();
    // $scope.limit_date_for_report = new Date();
    // $scope.limit_date_for_report.setDate($scope.today.getDate() + 2);

    $scope.modify_match = {};
    $scope.modify_player = {};
    $scope.newPlayer = {};
    $scope.existingPlayer = {};
    $scope.modify_my_team = {};
    $scope.team = {};

    $scope.add_new_player = function () {
        var uploadUrl = '/rest/action.php/player/savePlayer';
        multipartForm.post(uploadUrl, $scope.newPlayer);
    };

    $scope.edit_existing_player = function () {
        var uploadUrl = '/rest/action.php/player/savePlayer';
        multipartForm.post(uploadUrl, $scope.modify_player);
    };

    $scope.formatClubLabel = function (model, all_clubs) {
        if (all_clubs) {
            for (var i = 0; i < all_clubs.length; i++) {
                if (model === all_clubs[i].id) {
                    return all_clubs[i].nom;
                }
            }
        }
    };

    $scope.formatPlayerLabel = function (model, all_players) {
        if (all_players) {
            for (var i = 0; i < all_players.length; i++) {
                if (model === all_players[i].id) {
                    return all_players[i].full_name;
                }
            }
        }
    };

    $scope.initModifyMatchFields = function (model, matches) {
        if (matches) {
            for (var i = 0; i < matches.length; i++) {
                if (model === matches[i].id_match) {
                    $scope.modify_match.score_equipe_dom = parseInt(matches[i].score_equipe_dom);
                    $scope.modify_match.score_equipe_ext = parseInt(matches[i].score_equipe_ext);
                    $scope.modify_match.set_1_dom = parseInt(matches[i].set_1_dom);
                    $scope.modify_match.set_2_dom = parseInt(matches[i].set_2_dom);
                    $scope.modify_match.set_3_dom = parseInt(matches[i].set_3_dom);
                    $scope.modify_match.set_4_dom = parseInt(matches[i].set_4_dom);
                    $scope.modify_match.set_5_dom = parseInt(matches[i].set_5_dom);
                    $scope.modify_match.set_1_ext = parseInt(matches[i].set_1_ext);
                    $scope.modify_match.set_2_ext = parseInt(matches[i].set_2_ext);
                    $scope.modify_match.set_3_ext = parseInt(matches[i].set_3_ext);
                    $scope.modify_match.set_4_ext = parseInt(matches[i].set_4_ext);
                    $scope.modify_match.set_5_ext = parseInt(matches[i].set_5_ext);
                    $scope.modify_match.equipe_dom = matches[i].equipe_dom;
                    $scope.modify_match.equipe_ext = matches[i].equipe_ext;
                    $scope.modify_match.forfait_dom = matches[i].forfait_dom === 1;
                    $scope.modify_match.forfait_ext = matches[i].forfait_ext === 1;
                    $scope.modify_match.code_match = matches[i].code_match;
                    if (matches[i].note) {
                        $scope.modify_match.note = matches[i].note;
                    }
                    $scope.modify_match.id_match = parseInt(matches[i].id_match);
                    return matches[i].code_match;
                }
            }
        }
    };

    $scope.initModifyPlayerFields = function (model, players) {
        if (players) {
            for (var i = 0; i < players.length; i++) {
                if (model === players[i].id) {
                    $scope.modify_player.id = players[i].id;
                    $scope.modify_player.prenom = players[i].prenom;
                    $scope.modify_player.nom = players[i].nom;
                    $scope.modify_player.num_licence = players[i].num_licence;
                    $scope.modify_player.sexe = players[i].sexe;
                    $scope.modify_player.departement_affiliation = players[i].departement_affiliation;
                    $scope.modify_player.id_club = players[i].id_club;
                    $scope.modify_player.show_photo = players[i].show_photo === "1" ? 'on' : 'off';
                    $scope.modify_player.telephone = players[i].telephone;
                    $scope.modify_player.email = players[i].email;
                    $scope.modify_player.telephone2 = players[i].telephone2;
                    $scope.modify_player.email2 = players[i].email2;
                    return players[i].full_name;
                }
            }
        }
    };

    $scope.editMatch = function (model, matches) {
        document.getElementById('modify_match').scrollIntoView(true);
        $scope.initModifyMatchFields(model, matches);
    };

    $scope.saveMatch = function () {
        var uploadUrl = '/rest/action.php/matchmgr/saveMatch';
        multipartForm.post(uploadUrl, $scope.modify_match);
    };

    $scope.makeForfait = function (modify_match) {
        if (modify_match.forfait_dom && modify_match.forfait_ext) {
            return;
        }
        if (modify_match.forfait_dom) {
            modify_match.score_equipe_dom = 0;
            modify_match.score_equipe_ext = 3;
            modify_match.set_1_dom = 0;
            modify_match.set_2_dom = 0;
            modify_match.set_3_dom = 0;
            modify_match.set_4_dom = 0;
            modify_match.set_5_dom = 0;
            modify_match.set_1_ext = 25;
            modify_match.set_2_ext = 25;
            modify_match.set_3_ext = 25;
            modify_match.set_4_ext = 0;
            modify_match.set_5_ext = 0;
        }
        if (modify_match.forfait_ext) {
            modify_match.score_equipe_dom = 3;
            modify_match.score_equipe_ext = 0;
            modify_match.set_1_dom = 25;
            modify_match.set_2_dom = 25;
            modify_match.set_3_dom = 25;
            modify_match.set_4_dom = 0;
            modify_match.set_5_dom = 0;
            modify_match.set_1_ext = 0;
            modify_match.set_2_ext = 0;
            modify_match.set_3_ext = 0;
            modify_match.set_4_ext = 0;
            modify_match.set_5_ext = 0;
        }
    };

    $scope.askForReport = function (code_match) {
        bootbox.prompt(
            "Merci d'indiquer la raison de votre demande de report",
            function (reason) {
                if (reason !== null) {
                    $http({
                        method: 'POST',
                        url: '/rest/action.php/matchmgr/askForReport',
                        data: $.param({
                            code_match: code_match,
                            reason: reason
                        }),
                        headers: {'Content-Type': 'application/x-www-form-urlencoded'}
                    }).then(function (response) {
                        bootbox.alert("Votre demande a été transmise à l'équipe adverse et au responsable de compétition",
                            function () {
                                window.location.reload();
                            });
                    }, function (response) {
                        bootbox.alert("Erreur: " + response.data.message);
                    });
                }
            });
    };

    $scope.refuseReport = function (code_match) {
        bootbox.prompt(
            "Vous allez refuser la demande de report, le match sera donc joué le jour prévu ou l'équipe adverse sera déclarée forfait. Merci d'indiquer la raison du refus",
            function (reason) {
                if (reason !== null) {
                    $http({
                        method: 'POST',
                        url: '/rest/action.php/matchmgr/refuseReport',
                        data: $.param({
                            code_match: code_match,
                            reason: reason
                        }),
                        headers: {'Content-Type': 'application/x-www-form-urlencoded'}
                    }).then(function (response) {
                        bootbox.alert("Votre refus a été transmis à l'équipe adverse et au responsable de compétition",
                            function () {
                                window.location.reload();
                            });
                    }, function (response) {
                        bootbox.alert("Erreur: " + response.data.message);
                    });
                }
            });
    };

    $scope.acceptReport = function (code_match) {
        bootbox.confirm(
            "Vous allez accepter la demande de report, vous devrez donc communiquer une nouvelle date pour jouer le match. Êtes vous sûr de vouloir continuer ?",
            function (confirm_accept_report) {
                if (confirm_accept_report === true) {
                    $http({
                        method: 'POST',
                        url: '/rest/action.php/matchmgr/acceptReport',
                        data: $.param({
                            code_match: code_match
                        }),
                        headers: {'Content-Type': 'application/x-www-form-urlencoded'}
                    }).then(function (response) {
                        bootbox.alert("Votre acceptation a été transmise à l'équipe adverse et au responsable de compétition. Merci d'informer ceux-ci de la nouvelle date de réception.",
                            function () {
                                window.location.reload();
                            });
                    }, function (response) {
                        bootbox.alert("Erreur: " + response.data.message);
                    });
                }
            });
    };

    $scope.giveReportDate = function (code_match) {
        bootbox.prompt({
            title: "Merci d'indiquer la date de report (format: JJ/MM/AAAA)",
            inputType: 'date',
            callback: function (report_date) {
                if (report_date !== null) {
                    $http({
                        method: 'POST',
                        url: '/rest/action.php/matchmgr/giveReportDate',
                        data: $.param({
                            code_match: code_match,
                            report_date: report_date
                        }),
                        headers: {'Content-Type': 'application/x-www-form-urlencoded'}
                    }).then(function (response) {
                        bootbox.alert("La nouvelle date a été transmise à l'équipe adverse et au responsable de compétition",
                            function () {
                                window.location.reload();
                            });
                    }, function (response) {
                        bootbox.alert("Erreur: " + response.data.message);
                    });
                }
            }
        });
    };

    $http.get("../rest/action.php/news/getLastNews")
        .then(function (response) {
            $scope.lastNews = response.data;
        });

}]);

scotchApp.controller('myPreferencesController', function ($scope, $http) {
    $http.get("/rest/action.php/usermanager/getMyPreferences")
        .then(function (response) {
            $scope.preferences = response.data[0];
        });
    $scope.saveMyPreferences = function () {
        $http({
            method: 'POST',
            url: '/rest/action.php/usermanager/saveMyPreferences',
            data: $.param($scope.preferences),
            headers: {'Content-Type': 'application/x-www-form-urlencoded'}
        }).then(function (response) {
            window.location.reload();
        }, function (response) {
            bootbox.alert("Erreur: " + response.data.message);
        });
    };
});

scotchApp.controller('myTimeslotsController', function ($scope, $http) {
    $http.get("/rest/action.php/timeslot/get_my_timeslots")
        .then(function (response) {
            $scope.timeslots = response.data;
        });
    $http.get("/rest/action.php/court/getGymnasiums")
        .then(function (response) {
            $scope.gymnasiums = response.data;
        });
    $scope.removeTimeSlot = function (id) {
        $http({
            method: 'POST',
            url: '/rest/action.php/timeslot/removeTimeSlot',
            data: $.param({
                id: id
            }),
            headers: {'Content-Type': 'application/x-www-form-urlencoded'}
        }).then(function (response) {
            window.location.reload();
        }, function (response) {
            bootbox.alert("Erreur: " + response.data.message);
        });
    };
    $scope.addTimeSlot = function () {
        $http({
            method: 'POST',
            url: '/rest/action.php/timeslot/saveTimeSlot',
            data: $.param($scope.newTimeslot),
            headers: {'Content-Type': 'application/x-www-form-urlencoded'}
        }).then(function (response) {
            window.location.reload();
        }, function (response) {
            bootbox.alert("Erreur: " + response.data.message);
        });
    };

});

scotchApp.controller('myPasswordController', function ($scope, $http) {
    $scope.modifierMonMotDePasse = function () {
        $http({
            method: 'POST',
            url: '/rest/action.php/usermanager/modifierMonMotDePasse',
            data: $.param($scope.new_password_model),
            headers: {'Content-Type': 'application/x-www-form-urlencoded'}
        }).then(function (response) {
            window.location.reload();
        }, function (response) {
            bootbox.alert("Erreur: " + response.data.message);
        });
    };

});

scotchApp.controller('myPlayersController', ['$scope', '$http', function ($scope, $http) {
    $http.get("/rest/action.php/player/getMyPlayers")
        .then(function (response) {
            $scope.players = response.data;
        });
    $http.get("/rest/action.php/player/getPlayers")
        .then(function (response) {
            $scope.all_players = response.data;
        });
    $http.get("/rest/action.php/club/get")
        .then(function (response) {
            $scope.all_clubs = response.data;
        });
    $scope.addPlayerToTeam = function () {
        $http({
            method: 'POST',
            url: '/rest/action.php/player/addPlayerToMyTeam',
            data: $.param($scope.existingPlayer),
            headers: {'Content-Type': 'application/x-www-form-urlencoded'}
        }).then(function (response) {
            window.location.reload();
        }, function (response) {
            bootbox.alert("Erreur: " + response.data.message);
        });
    };
    $scope.removePlayerFromMyTeam = function (id) {
        $http({
            method: 'POST',
            url: '/rest/action.php/player/removePlayerFromMyTeam',
            data: $.param({
                id: id
            }),
            headers: {'Content-Type': 'application/x-www-form-urlencoded'}
        }).then(function (response) {
            window.location.reload();
        }, function (response) {
            bootbox.alert("Erreur: " + response.data.message);
        });
    };
    $scope.updateMyTeamCaptain = function (id) {
        $http({
            method: 'POST',
            url: '/rest/action.php/team/updateMyTeamCaptain',
            data: $.param({
                id_joueur: id
            }),
            headers: {'Content-Type': 'application/x-www-form-urlencoded'}
        }).then(function (response) {
            window.location.reload();
        }, function (response) {
            bootbox.alert("Erreur: " + response.data.message);
        });
    };
    $scope.updateMyTeamLeader = function (id) {
        $http({
            method: 'POST',
            url: '/rest/action.php/team/updateMyTeamLeader',
            data: $.param({
                id_joueur: id
            }),
            headers: {'Content-Type': 'application/x-www-form-urlencoded'}
        }).then(function (response) {
            window.location.reload();
        }, function (response) {
            bootbox.alert("Erreur: " + response.data.message);
        });
    };
    $scope.updateMyTeamViceLeader = function (id) {
        $http({
            method: 'POST',
            url: '/rest/action.php/team/updateMyTeamViceLeader',
            data: $.param({
                id_joueur: id
            }),
            headers: {'Content-Type': 'application/x-www-form-urlencoded'}
        }).then(function (response) {
            window.location.reload();
        }, function (response) {
            bootbox.alert("Erreur: " + response.data.message);
        });
    };
}]);

scotchApp.controller('myTeamController', ['$scope', '$http', 'multipartForm', function ($scope, $http, multipartForm) {
    $http.get("/rest/action.php/team/getMyTeam")
        .then(function (response) {
            $scope.team = response.data[0];
            $scope.modify_my_team.web_site = $scope.team.web_site;
            $scope.modify_my_team.id_club = $scope.team.id_club;
        });
    $http.get("/rest/action.php/club/get")
        .then(function (response) {
            $scope.all_clubs = response.data;
        });
    $scope.Submit = function () {
        var uploadUrl = '/rest/action.php/team/saveTeam';
        multipartForm.post(uploadUrl, $scope.modify_my_team);
    };

}]);

scotchApp.directive('fileModel', ['$parse', function ($parse) {
    return {
        restrict: 'A',
        link: function (scope, element, attrs) {
            var model = $parse(attrs.fileModel);
            var modelSetter = model.assign;

            element.bind('change', function () {
                scope.$apply(function () {
                    modelSetter(scope, element[0].files[0]);
                });
            });
        }
    };
}]);

scotchApp.service('multipartForm', ['$http', function ($http) {
    this.post = function (uploadUrl, data) {
        var fd = new FormData();
        for (var key in data) {
            fd.append(key, data[key]);
        }
        $http.post(uploadUrl, fd, {
            transformRequest: angular.identity,
            headers: {'Content-Type': undefined}
        }).then(function (response) {
            window.location.reload();
        }, function (response) {
            bootbox.alert("Erreur: " + response.data.message);
        });
    };
}]);

scotchApp.controller('myHistoryController', function ($scope, $http) {
    $http.get("/rest/action.php/activity/getActivity")
        .then(function (response) {
            $scope.activities = response.data;
        });
});

scotchApp.controller('registerController', function ($scope, $http) {
    $scope.newUser = {};
    $scope.add_new_user = function () {
        $http({
            method: 'POST',
            url: '/rest/action.php/usermanager/createUser',
            data: $.param($scope.newUser),
            headers: {'Content-Type': 'application/x-www-form-urlencoded'}
        }).then(function (response) {
            bootbox.alert("Votre compte est maintenant créé, veuillez vous connecter avec les identifiants reçus par email." +
                " Si vous êtes responsable d'équipe veuillez demander à votre responsable de division les permissions nécessaires.",
                function () {
                    window.location = '/';
                });
        }, function (response) {
            bootbox.alert("Erreur: " + response.data.message);
        });
    };
    $scope.formatTeamLabel = function (model, teams) {
        if (teams) {
            for (var i = 0; i < teams.length; i++) {
                if (model === teams[i].id_equipe) {
                    return teams[i].team_full_name;
                }
            }
        }
    };
    $http.get("/rest/action.php/team/getTeams")
        .then(function (response) {
            $scope.teams = response.data;
        });
});

scotchApp.controller('myPageController', function ($scope, $http) {
    $http.get("/rest/action.php/matchmgr/getMesMatches")
        .then(function (response) {
            $scope.matches = response.data;
        });
    $http.get("/rest/action.php/alerts/getAlerts")
        .then(function (response) {
            $scope.alerts = response.data;
            for (var currentAlertIndex = 0; currentAlertIndex < $scope.alerts.length; currentAlertIndex++) {
                switch ($scope.alerts[currentAlertIndex]["expected_action"]) {
                    case 'showHelpSelectLeader':
                        $scope.alerts[currentAlertIndex]["title"] = "Responsable d'équipe non défini";
                        $scope.alerts[currentAlertIndex]["content"] = "Merci de vous rendre dans le menu de gestion des joueurs, et de désigner un responsable d'équipe";
                        break;
                    case 'showHelpSelectViceLeader':
                        $scope.alerts[currentAlertIndex]["title"] = "Responsable d'équipe suppléant non défini";
                        $scope.alerts[currentAlertIndex]["content"] = "Merci de vous rendre dans le menu de gestion des joueurs, et de désigner un suppléant au responsable d'équipe (cette action est optionnelle).";
                        break;
                    case 'showHelpSelectCaptain':
                        $scope.alerts[currentAlertIndex]["title"] = "Capitaine non défini";
                        $scope.alerts[currentAlertIndex]["content"] = "Merci de vous rendre dans le menu de gestion des joueurs, et de désigner le capitaine de l'équipe.";
                        break;
                    case 'showHelpSelectTimeSlot':
                        $scope.alerts[currentAlertIndex]["title"] = "Ajout de créneau de gymnase";
                        $scope.alerts[currentAlertIndex]["content"] = "Merci de vous rendre dans le menu de gestion des gymnases, et d'indiquer les créneaux auxquels vous pouvez recevoir les matches.";
                        break;
                    case 'showHelpAddPhoneNumber':
                        $scope.alerts[currentAlertIndex]["title"] = "Numéro de téléphone";
                        $scope.alerts[currentAlertIndex]["content"] = "Merci de vous rendre dans le menu de gestion des joueurs, éditer le responsable ou le suppléant, et ajouter au moins un numéro de téléphone.";
                        break;
                    case 'showHelpAddEmail':
                        $scope.alerts[currentAlertIndex]["title"] = "Adresse email";
                        $scope.alerts[currentAlertIndex]["content"] = "Merci de vous rendre dans le menu de gestion des joueurs, éditer le responsable ou le suppléant, et ajouter au moins une adresse email.";
                        break;
                    case 'showHelpAddPlayer':
                        $scope.alerts[currentAlertIndex]["title"] = "Ajout de joueur";
                        $scope.alerts[currentAlertIndex]["content"] = "Merci de vous rendre dans le menu de gestion des joueurs, cliquer sur 'Ajouter un joueur' pour sélectionner l'un des joueurs connus du système. Si ce joueur n'existe pas, cliquer sur 'Créer un joueur'. Les joueurs n'apparaissent pas immédiatement sur la fiche équipe, ils doivent être activés par les responsables UFOLEP.";
                        break;
                    case 'showHelpInactivePlayers':
                        $scope.alerts[currentAlertIndex]["title"] = "Joueurs inactifs";
                        $scope.alerts[currentAlertIndex]["content"] = "Merci de vous rendre dans le menu de gestion des joueurs. Les joueurs en rouge sont inactifs. Ils n'apparaitront sur la fiche équipe qu'une fois actifs. Pour ce faire, les responsables UFOLEP doivent vérifier la validité de ces joueurs. Si le délai de prise en compte vous semble long, merci de relancer le responsable UFOLEP du championnat/division/coupe/poule concerné.";
                        break;
                    case 'showHelpPlayersWithoutLicenceNumber':
                        $scope.alerts[currentAlertIndex]["title"] = "Joueurs sans licence";
                        $scope.alerts[currentAlertIndex]["content"] = "Merci de vous rendre dans le menu de gestion des joueurs. Certains joueurs n'ont pas encore leur numéro de licence. Ils ne peuvent être vérifiés par la commission que lorsqu'ils auront leur numéro de licence. Merci de renseigner ce numéro dès que vous l'aurez récupéré.";
                        break;
                }
            }
        });
    $http.get("/rest/action.php/team/getMyTeam")
        .then(function (response) {
            $scope.team = response.data[0];
        });
});

scotchApp.controller('myClubController', function ($scope, $http) {
    $http.get("/rest/action.php/matchmgr/getMyClubMatches")
        .then(function (response) {
            $scope.matches = response.data;
        });
});

scotchApp.controller('phonebooksController', function ($scope, $http) {
    $http.get("/rest/action.php/competition/getCompetitions")
        .then(function (response) {
            $scope.competitions = response.data;
        });
    $http.get("/rest/action.php/rank/getDivisions")
        .then(function (response) {
            $scope.divisions = response.data;
        });
    $http.get("/rest/action.php/rank/getRanks")
        .then(function (response) {
            $scope.ranks = response.data;
        });
});

scotchApp.controller('commissionController', function ($scope, $http) {
    $http.get("../ajax/commission.php")
        .then(function (response) {
            $scope.commission = response.data.results;
            var commission_team = $scope.commission;
            var commission_team_count = commission_team.length;
            for (var currentIndex = 0; currentIndex < commission_team_count; currentIndex++) {
                $scope.commission[currentIndex]["prenom_base64"] = Base64.encode($scope.commission[currentIndex]["prenom"]);
                $scope.commission[currentIndex]["nom_base64"] = Base64.encode($scope.commission[currentIndex]["nom"]);
                $scope.commission[currentIndex]["telephone1_base64"] = Base64.encode($scope.commission[currentIndex]["telephone1"]);
                $scope.commission[currentIndex]["telephone2_base64"] = Base64.encode($scope.commission[currentIndex]["telephone2"]);
                $scope.commission[currentIndex]["email_base64"] = Base64.encode($scope.commission[currentIndex]["email"]);
                $scope.commission[currentIndex]["photo"] = '/' + $scope.commission[currentIndex]["photo"];
            }
        });
});

scotchApp.controller('phonebookController', ['$scope', '$routeParams', '$http', function ($scope, $routeParams, $http) {
    $http.get("/rest/action.php/team/getTeam", {
        params: {
            id: $routeParams.id
        }
    })
        .then(function (response) {
            $scope.team = response.data;
        });
}]);

scotchApp.controller('lastResultsController', function ($scope, $http) {
    $http.get("/rest/action.php/matchmgr/getLastResults")
        .then(function (response) {
            $scope.lastResults = response.data;
        });
});

scotchApp.controller('weekMatchesController', function ($scope, $http) {
    $http.get("/rest/action.php/matchmgr/getWeekMatches")
        .then(function (response) {
            $scope.matches_of_the_week = response.data;
        });
});

scotchApp.controller('championshipController', ['$scope', '$routeParams', '$http', function ($scope, $routeParams, $http) {
    $scope.code_competition = $routeParams.competition;
    $scope.division = $routeParams.division;
    $http.get("/rest/action.php/competition/getCompetitions")
        .then(function (response) {
            $scope.competitions = response.data;
            var competitions = $scope.competitions;
            for (var currentCompetitionIndex = 0; currentCompetitionIndex < competitions.length; currentCompetitionIndex++) {
                if (competitions[currentCompetitionIndex]['code_competition'] === $scope.code_competition) {
                    $scope.libelle_competition = competitions[currentCompetitionIndex]['libelle'];
                    $scope.limit_date = competitions[currentCompetitionIndex]['limit_date'];
                    return;
                }
            }
        });
    $http.get("/rest/action.php/rank/getRank", {
        params: {
            competition: $routeParams.competition,
            division: $routeParams.division
        }
    }).then(function (response) {
        $scope.rankings = response.data;
        var teams = $scope.rankings;
        var teams_count = teams.length;
        for (var currentTeamIndex = 0; currentTeamIndex < teams.length; currentTeamIndex++) {
            // $scope.rankings[currentTeamIndex]["is_promotion"] = "0";
            $scope.rankings[currentTeamIndex]["is_promotion"] = ((teams[currentTeamIndex]["rang"] === 1) || (teams[currentTeamIndex]["rang"] === 2)) ? "1" : "0";
            // $scope.rankings[currentTeamIndex]["is_relegation"] = "0";
            $scope.rankings[currentTeamIndex]["is_relegation"] = ((teams[currentTeamIndex]["rang"] === teams_count) || (teams[currentTeamIndex]["rang"] === (teams_count - 1))) ? "1" : "0";
            if (teams[currentTeamIndex]["joues"] === "0") {
                $scope.rankings[currentTeamIndex]["exact_deuce"] = "0";
                continue;
            }
            $scope.rankings[currentTeamIndex]["exact_deuce"] = "0";
            for (var compareTeamIndex = 0; compareTeamIndex < teams.length; compareTeamIndex++) {
                if (teams[compareTeamIndex]["id_equipe"] === teams[currentTeamIndex]["id_equipe"]) {
                    continue;
                }
                if (teams[compareTeamIndex]["points"] !== teams[currentTeamIndex]["points"]) {
                    continue;
                }
                if (teams[compareTeamIndex]["joues"] !== teams[currentTeamIndex]["joues"]) {
                    continue;
                }
                if (teams[compareTeamIndex]["diff"] !== teams[currentTeamIndex]["diff"]) {
                    continue;
                }
                $scope.rankings[currentTeamIndex]["exact_deuce"] = "1";
                break;
            }
        }
    });

    $http.get("/rest/action.php/matchmgr/getMatches", {
        params: {
            competition: $routeParams.competition,
            division: $routeParams.division
        }
    }).then(function (response) {
        $scope.matches = response.data;
    });

    $scope.removePenalty = function (id_equipe, competition) {
        $http({
            method: 'POST',
            url: '/rest/action.php/rank/removePenalty',
            data: $.param({
                compet: competition,
                id_equipe: id_equipe
            }),
            headers: {'Content-Type': 'application/x-www-form-urlencoded'}
        }).then(function (response) {
            window.location.reload();
        }, function (response) {
            bootbox.alert("Erreur: " + response.data.message);
        });
    };
    $scope.addPenalty = function (id_equipe, competition) {
        $http({
            method: 'POST',
            url: '/rest/action.php/rank/addPenalty',
            data: $.param({
                compet: competition,
                id_equipe: id_equipe
            }),
            headers: {'Content-Type': 'application/x-www-form-urlencoded'}
        }).then(function (response) {
            window.location.reload();
        }, function (response) {
            bootbox.alert("Erreur: " + response.data.message);
        });
    };

    $scope.DecrementReportCount = function (id_equipe, competition) {
        $http({
            method: 'POST',
            url: '/rest/action.php/rank/decrementReportCount',
            data: $.param({
                compet: competition,
                equipe: id_equipe
            }),
            headers: {'Content-Type': 'application/x-www-form-urlencoded'}
        }).then(function (response) {
            window.location.reload();
        }, function (response) {
            bootbox.alert("Erreur: " + response.data.message);
        });
    };
    $scope.IncrementReportCount = function (id_equipe, competition) {
        $http({
            method: 'POST',
            url: '/rest/action.php/rank/incrementReportCount',
            data: $.param({
                compet: competition,
                id_equipe: id_equipe
            }),
            headers: {'Content-Type': 'application/x-www-form-urlencoded'}
        }).then(function (response) {
            window.location.reload();
        }, function (response) {
            bootbox.alert("Erreur: " + response.data.message);
        });
    };

    $scope.validateMatch = function (code_match) {
        $http({
            method: 'POST',
            url: '/rest/action.php/matchmgr/certifyMatch',
            data: $.param({
                code_match: code_match
            }),
            headers: {'Content-Type': 'application/x-www-form-urlencoded'}
        }).then(function (response) {
            window.location.reload();
        }, function (response) {
            bootbox.alert("Erreur: " + response.data.message);
        });
    };

    $scope.declareSheetReceived = function (code_match) {
        $http({
            method: 'POST',
            url: '/rest/action.php/matchmgr/declare_sheet_received',
            data: $.param({
                code_match: code_match
            }),
            headers: {'Content-Type': 'application/x-www-form-urlencoded'}
        }).then(function (response) {
            window.location.reload();
        }, function (response) {
            bootbox.alert("Erreur: " + response.data.message);
        });
    };

    $scope.invalidateMatch = function (code_match) {
        $http({
            method: 'POST',
            url: '/rest/action.php/matchmgr/invalidateMatch',
            data: $.param({
                code_match: code_match
            }),
            headers: {'Content-Type': 'application/x-www-form-urlencoded'}
        }).then(function (response) {
            window.location.reload();
        }, function (response) {
            bootbox.alert("Erreur: " + response.data.message);
        });
    };
}]);

scotchApp.controller('cupController', ['$scope', '$routeParams', '$http', function ($scope, $routeParams, $http) {
    $scope.code_competition = $routeParams.competition;
    $http.get("/rest/action.php/competition/getCompetitions")
        .then(function (response) {
            $scope.competitions = response.data;
            var competitions = $scope.competitions;
            for (var currentCompetitionIndex = 0; currentCompetitionIndex < competitions.length; currentCompetitionIndex++) {
                if (competitions[currentCompetitionIndex]['code_competition'] === $scope.code_competition) {
                    $scope.libelle_competition = competitions[currentCompetitionIndex]['libelle'];
                    return;
                }
            }
        });
    $http.get("/rest/action.php/matchmgr/getMatches", {
        params: {
            competition: $routeParams.competition,
            division: 1
        }
    }).then(function (response) {
        $scope.matches = response.data;
    });

    $scope.validateMatch = function (code_match) {
        $http({
            method: 'POST',
            url: '/rest/action.php/matchmgr/certifyMatch',
            data: $.param({
                code_match: code_match
            }),
            headers: {'Content-Type': 'application/x-www-form-urlencoded'}
        }).then(function (response) {
            window.location.reload();
        }, function (response) {
            bootbox.alert("Erreur: " + response.data.message);
        });
    };

    $scope.declareSheetReceived = function (code_match) {
        $http({
            method: 'POST',
            url: '/rest/action.php/matchmgr/declare_sheet_received',
            data: $.param({
                code_match: code_match
            }),
            headers: {'Content-Type': 'application/x-www-form-urlencoded'}
        }).then(function (response) {
            window.location.reload();
        }, function (response) {
            bootbox.alert("Erreur: " + response.data.message);
        });
    };

    $scope.invalidateMatch = function (code_match) {
        $http({
            method: 'POST',
            url: '/rest/action.php/matchmgr/invalidateMatch',
            data: $.param({
                code_match: code_match
            }),
            headers: {'Content-Type': 'application/x-www-form-urlencoded'}
        }).then(function (response) {
            window.location.reload();
        }, function (response) {
            bootbox.alert("Erreur: " + response.data.message);
        });
    };
}]);

scotchApp.controller('webSitesController', function ($scope, $http) {
    $http.get("/rest/action.php/team/getWebSites")
        .then(function (response) {
            $scope.webSites = response.data;
        });
});
scotchApp.controller('hallOfFameController', function ($scope, $http) {
    $http.get("/rest/action.php/halloffame/getHallOfFameDisplay")
        .then(function (response) {
            $scope.hallOfFame = response.data;
        });
});
scotchApp.controller('gymnasiumsController', function ($scope, $http) {
    $http.get("/rest/action.php/court/getGymnasiums")
        .then(function (response) {
            $scope.gymnasiums = response.data;
        });
});

scotchApp.controller('volleyballImagesController', function ($scope, $http) {
    $scope.volleyballImages = [];
    $http.get("../ajax/getVolleyballImages.php")
        .then(function (response) {
            for (var i in response.data.photo) {
                var index = parseInt(i);
                response.data.photo[index]["index"] = index;
                $scope.volleyballImages.push(response.data.photo[index]);
            }
        });
});

scotchApp.filter('sanitize', ['$sce', function ($sce) {
    return function (htmlCode) {
        return $sce.trustAsHtml(htmlCode);
    }
}]);

scotchApp.filter('parseDate', function () {
    return function (input) {
        return new Date(input);
    };
});

scotchApp.filter('num', function () {
    return function (input) {
        return parseInt(input, 10);
    };
});
