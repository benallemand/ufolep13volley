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
        .when('/adminPage', {
            templateUrl: '../admin.php'
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
    $scope.team = {};

    $http.get("../rest/action.php/news/getLastNews")
        .then(function (response) {
            $scope.lastNews = response.data;
        });

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
    $http.get("/rest/action.php/commission/get")
        .then(function (response) {
            $scope.commission = response.data;
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
