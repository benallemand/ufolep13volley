// create the module and name it scotchApp
var scotchApp = angular.module('scotchApp', ['ngRoute', 'ngAnimate', 'ui.bootstrap', 'angular.filter']);

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
        .when('/lastPosts', {
            templateUrl: 'pages/lastPosts.html',
            controller: 'lastPostsController'
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
        .when('/cup/:competition/:division', {
            templateUrl: 'pages/cup.html',
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
        });
});

scotchApp.controller('mainController', ['$scope', '$http', 'multipartForm', function ($scope, $http, multipartForm) {
    $http.get("../ajax/getLastCommit.php")
        .then(function (response) {
            $scope.lastCommit = response.data;
        });
    $scope.today = new Date();
    $scope.limit_date_for_report = new Date();
    $scope.limit_date_for_report.setDate($scope.today.getDate() + 2);

    $scope.modify_match = {};
    $scope.modify_player = {};
    $scope.newPlayer = {};
    $scope.existingPlayer = {};
    $scope.modify_my_team = {};
    $scope.team = {};

    $scope.add_new_player = function () {
        var uploadUrl = '../ajax/savePlayer.php';
        multipartForm.post(uploadUrl, $scope.newPlayer);
    };

    $scope.edit_existing_player = function () {
        var uploadUrl = '../ajax/savePlayer.php';
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
                    $scope.modify_match.forfait_dom = matches[i].forfait_dom == '1';
                    $scope.modify_match.forfait_ext = matches[i].forfait_ext == '1';
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
                    $scope.modify_player.show_photo = players[i].show_photo == "1" ? 'on' : 'off';
                    $scope.modify_player.telephone = players[i].telephone;
                    $scope.modify_player.email = players[i].email;
                    $scope.modify_player.telephone2 = players[i].telephone2;
                    $scope.modify_player.email2 = players[i].email2;
                    return players[i].full_name;
                }
            }
        }
    };

    $scope.saveMatch = function () {
        $http({
            method: 'POST',
            url: '../ajax/saveMatch.php',
            data: $.param($scope.modify_match),
            headers: {'Content-Type': 'application/x-www-form-urlencoded'}
        }).then(function (response) {
            if (response.data.success) {
                window.location.reload();
                return;
            }
            $scope.myTxt = "Erreur: " + response.data.message;
        });
    };
}]);

scotchApp.controller('myPreferencesController', function ($scope, $http) {
    $http.get("../ajax/getMyPreferences.php")
        .then(function (response) {
            $scope.preferences = response.data[0];
        });
    $scope.saveMyPreferences = function () {
        $http({
            method: 'POST',
            url: '../ajax/saveMyPreferences.php',
            data: $.param($scope.preferences),
            headers: {'Content-Type': 'application/x-www-form-urlencoded'}
        }).then(function (response) {
            if (response.data.success) {
                window.location.reload();
                return;
            }
            $scope.myTxt = "Erreur: " + response.data.message;
        });
    };
});

scotchApp.controller('myTimeslotsController', function ($scope, $http) {
    $http.get("../ajax/getTimeSlots.php")
        .then(function (response) {
            $scope.timeslots = response.data;
        });
    $http.get("../ajax/getGymnasiums.php")
        .then(function (response) {
            $scope.gymnasiums = response.data;
        });
    $scope.removeTimeSlot = function (id) {
        $http({
            method: 'POST',
            url: '../ajax/removeTimeSlot.php',
            data: $.param({
                id: id
            }),
            headers: {'Content-Type': 'application/x-www-form-urlencoded'}
        }).then(function (response) {
            if (response.data.success) {
                window.location.reload();
                return;
            }
            $scope.myTxt = "Erreur: " + response.data.message;
        });
    };
    $scope.addTimeSlot = function () {
        $http({
            method: 'POST',
            url: '../ajax/saveTimeSlot.php',
            data: $.param($scope.newTimeslot),
            headers: {'Content-Type': 'application/x-www-form-urlencoded'}
        }).then(function (response) {
            if (response.data.success) {
                window.location.reload();
                return;
            }
            $scope.myTxt = "Erreur: " + response.data.message;
        });
    };

});

scotchApp.controller('myPasswordController', function ($scope, $http) {
    $scope.modifierMonMotDePasse = function () {
        $http({
            method: 'POST',
            url: '../ajax/modifierMonMotDePasse.php',
            data: $.param($scope.new_password_model),
            headers: {'Content-Type': 'application/x-www-form-urlencoded'}
        }).then(function (response) {
            if (response.data.success) {
                window.location.reload();
                return;
            }
            $scope.myTxt = "Erreur: " + response.data.message;
        });
    };

});

scotchApp.controller('myPlayersController', ['$scope', '$http', function ($scope, $http) {
    $http.get("../ajax/getMyPlayers.php")
        .then(function (response) {
            $scope.players = response.data;
        });
    $http.get("../ajax/getPlayers.php")
        .then(function (response) {
            $scope.all_players = response.data;
        });
    $http.get("../ajax/getClubs.php")
        .then(function (response) {
            $scope.all_clubs = response.data;
        });
    $scope.addPlayerToTeam = function () {
        $http({
            method: 'POST',
            url: '../ajax/addPlayerToMyTeam.php',
            data: $.param($scope.existingPlayer),
            headers: {'Content-Type': 'application/x-www-form-urlencoded'}
        }).then(function (response) {
            if (response.data.success) {
                window.location.reload();
                return;
            }
            $scope.myTxt = "Erreur: " + response.data.message;
        });
    };
    $scope.removePlayerFromMyTeam = function (id) {
        $http({
            method: 'POST',
            url: '../ajax/removePlayerFromMyTeam.php',
            data: $.param({
                id: id
            }),
            headers: {'Content-Type': 'application/x-www-form-urlencoded'}
        }).then(function (response) {
            if (response.data.success) {
                window.location.reload();
                return;
            }
            $scope.myTxt = "Erreur: " + response.data.message;
        });
    };
    $scope.updateMyTeamCaptain = function (id) {
        $http({
            method: 'POST',
            url: '../ajax/updateMyTeamCaptain.php',
            data: $.param({
                id_joueur: id
            }),
            headers: {'Content-Type': 'application/x-www-form-urlencoded'}
        }).then(function (response) {
            if (response.data.success) {
                window.location.reload();
                return;
            }
            $scope.myTxt = "Erreur: " + response.data.message;
        });
    };
    $scope.updateMyTeamLeader = function (id) {
        $http({
            method: 'POST',
            url: '../ajax/updateMyTeamLeader.php',
            data: $.param({
                id_joueur: id
            }),
            headers: {'Content-Type': 'application/x-www-form-urlencoded'}
        }).then(function (response) {
            if (response.data.success) {
                window.location.reload();
                return;
            }
            $scope.myTxt = "Erreur: " + response.data.message;
        });
    };
    $scope.updateMyTeamViceLeader = function (id) {
        $http({
            method: 'POST',
            url: '../ajax/updateMyTeamViceLeader.php',
            data: $.param({
                id_joueur: id
            }),
            headers: {'Content-Type': 'application/x-www-form-urlencoded'}
        }).then(function (response) {
            if (response.data.success) {
                window.location.reload();
                return;
            }
            $scope.myTxt = "Erreur: " + response.data.message;
        });
    };
}]);

scotchApp.controller('myTeamController', ['$scope', '$http', 'multipartForm', function ($scope, $http, multipartForm) {
    $http.get("../ajax/getMonEquipe.php")
        .then(function (response) {
            $scope.team = response.data[0];
            $scope.team["responsable_base64"] = window.btoa($scope.team["responsable"]);
            $scope.team["telephone_1_base64"] = window.btoa($scope.team["telephone_1"]);
            $scope.team["telephone_2_base64"] = window.btoa($scope.team["telephone_2"]);
            $scope.team["email_base64"] = window.btoa($scope.team["email"]);
            $scope.modify_my_team.web_site = $scope.team.web_site;
            $scope.modify_my_team.id_club = $scope.team.id_club;
        });
    $http.get("../ajax/getClubs.php")
        .then(function (response) {
            $scope.all_clubs = response.data;
        });
    $scope.Submit = function () {
        var uploadUrl = '../ajax/saveTeam.php';
        multipartForm.post(uploadUrl, $scope.modify_my_team);
    }

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
    }
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
            if (response.data.success) {
                window.location.reload();
                return;
            }
            alert("Erreur: " + response.data.message);
        });
    }
}]);

scotchApp.controller('myHistoryController', function ($scope, $http) {
    $http.get("../ajax/getActivity.php")
        .then(function (response) {
            $scope.activities = response.data;
        });
});

scotchApp.controller('myPageController', function ($scope, $http) {
    $http.get("../ajax/getMesMatches.php")
        .then(function (response) {
            $scope.matches = response.data;
        });
    $http.get("../ajax/getAlerts.php")
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
    $http.get("../ajax/getMonEquipe.php")
        .then(function (response) {
            $scope.team = response.data[0];
            $scope.team["responsable_base64"] = window.btoa($scope.team["responsable"]);
            $scope.team["telephone_1_base64"] = window.btoa($scope.team["telephone_1"]);
            $scope.team["telephone_2_base64"] = window.btoa($scope.team["telephone_2"]);
            $scope.team["email_base64"] = window.btoa($scope.team["email"]);
        });

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
        var reason = prompt("Merci d'indiquer la raison de votre demande de report", "Ecrire ici la raison");
        if (reason != null) {
            $http({
                method: 'POST',
                url: '../ajax/askForReport.php',
                data: $.param({
                    code_match: code_match,
                    reason: reason
                }),
                headers: {'Content-Type': 'application/x-www-form-urlencoded'}
            }).then(function (response) {
                if (response.data.success) {
                    alert("Votre demande a été transmise à l'équipe adverse et au responsable de compétition");
                    window.location.reload();
                    return;
                }
                $scope.myTxt = "Erreur: " + response.data.message;
            });
        }
    };

    $scope.refuseReport = function (code_match) {
        var confirm_refuse_report = confirm("Vous allez refuser la demande de report, le match sera donc joué le jour prévu ou l'équipe adverse sera déclarée forfait. Êtes vous sûr de vouloir continuer ?");
        if (confirm_refuse_report == true) {
            $http({
                method: 'POST',
                url: '../ajax/refuseReport.php',
                data: $.param({
                    code_match: code_match
                }),
                headers: {'Content-Type': 'application/x-www-form-urlencoded'}
            }).then(function (response) {
                if (response.data.success) {
                    alert("Votre refus a été transmise à l'équipe adverse et au responsable de compétition");
                    window.location.reload();
                    return;
                }
                $scope.myTxt = "Erreur: " + response.data.message;
            });
        }
    };

    $scope.acceptReport = function (code_match) {
        var confirm_accept_report = confirm("Vous allez accepter la demande de report, vous devrez donc communiquer une nouvelle date pour jouer le match. Êtes vous sûr de vouloir continuer ?");
        if (confirm_accept_report == true) {
            $http({
                method: 'POST',
                url: '../ajax/acceptReport.php',
                data: $.param({
                    code_match: code_match
                }),
                headers: {'Content-Type': 'application/x-www-form-urlencoded'}
            }).then(function (response) {
                if (response.data.success) {
                    alert("Votre acceptation a été transmise à l'équipe adverse et au responsable de compétition. Merci d'informer ceux-ci de la nouvelle date de réception.");
                    window.location.reload();
                    return;
                }
                $scope.myTxt = "Erreur: " + response.data.message;
            });
        }
    };
});

scotchApp.controller('myClubController', function ($scope, $http) {
    $http.get("../ajax/getMyClubMatches.php")
        .then(function (response) {
            $scope.matches = response.data;
        });
});

scotchApp.controller('phonebooksController', function ($scope, $http) {
    $http.get("../ajax/getCompetitions.php")
        .then(function (response) {
            $scope.competitions = response.data;
        });
    $http.get("../ajax/getDivisions.php")
        .then(function (response) {
            $scope.divisions = response.data;
        });
    $http.get("../ajax/getRanks.php")
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
                $scope.commission[currentIndex]["prenom_base64"] = window.btoa($scope.commission[currentIndex]["prenom"]);
                $scope.commission[currentIndex]["nom_base64"] = window.btoa($scope.commission[currentIndex]["nom"]);
                $scope.commission[currentIndex]["telephone1_base64"] = window.btoa($scope.commission[currentIndex]["telephone1"]);
                $scope.commission[currentIndex]["telephone2_base64"] = window.btoa($scope.commission[currentIndex]["telephone2"]);
                $scope.commission[currentIndex]["email_base64"] = window.btoa($scope.commission[currentIndex]["email"]);
            }
        });
});

scotchApp.controller('phonebookController', ['$scope', '$routeParams', '$http', function ($scope, $routeParams, $http) {
    $http.get("../ajax/getTeam.php", {
        params: {
            id: $routeParams.id
        }
    })
        .then(function (response) {
            $scope.team = response.data;
            $scope.team["responsable_base64"] = window.btoa($scope.team["responsable"]);
            $scope.team["telephone_1_base64"] = window.btoa($scope.team["telephone_1"]);
            $scope.team["telephone_2_base64"] = window.btoa($scope.team["telephone_2"]);
            $scope.team["email_base64"] = window.btoa($scope.team["email"]);
        });
}]);

scotchApp.controller('lastResultsController', function ($scope, $http) {
    $http.get("../ajax/getLastResults.php")
        .then(function (response) {
            $scope.lastResults = response.data;
        });
});

scotchApp.controller('championshipController', ['$scope', '$routeParams', '$http', function ($scope, $routeParams, $http) {
    $http.get("../ajax/getClassement.php", {
        params: {
            competition: $routeParams.competition,
            division: $routeParams.division
        }
    }).then(function (response) {
        $scope.rankings = response.data;
        var teams = $scope.rankings;
        var teams_count = teams.length;
        for (var currentTeamIndex = 0; currentTeamIndex < teams.length; currentTeamIndex++) {
            $scope.rankings[currentTeamIndex]["is_promotion"] = ((teams[currentTeamIndex]["rang"] == "1") || (teams[currentTeamIndex]["rang"] == "2")) ? "1" : "0";
            $scope.rankings[currentTeamIndex]["is_relegation"] = ((teams[currentTeamIndex]["rang"] == teams_count.toString()) || (teams[currentTeamIndex]["rang"] == (teams_count - 1).toString())) ? "1" : "0";
            if (teams[currentTeamIndex]["joues"] == "0") {
                $scope.rankings[currentTeamIndex]["exact_deuce"] = "0";
                continue;
            }
            $scope.rankings[currentTeamIndex]["exact_deuce"] = "0";
            for (var compareTeamIndex = 0; compareTeamIndex < teams.length; compareTeamIndex++) {
                if (teams[compareTeamIndex]["id_equipe"] == teams[currentTeamIndex]["id_equipe"]) {
                    continue;
                }
                if (teams[compareTeamIndex]["points"] != teams[currentTeamIndex]["points"]) {
                    continue;
                }
                if (teams[compareTeamIndex]["joues"] != teams[currentTeamIndex]["joues"]) {
                    continue;
                }
                if (teams[compareTeamIndex]["diff"] != teams[currentTeamIndex]["diff"]) {
                    continue;
                }
                $scope.rankings[currentTeamIndex]["exact_deuce"] = "1";
                break;
            }
        }
    });

    $http.get("../ajax/getMatches.php", {
        params: {
            competition: $routeParams.competition,
            division: $routeParams.division
        }
    }).then(function (response) {
        $scope.matches = response.data;
    });

    $scope.refuseReport = function (code_match) {
        var confirm_refuse_report = confirm("Vous allez refuser la demande de report, le match sera donc joué le jour prévu ou l'équipe adverse sera déclarée forfait. Êtes vous sûr de vouloir continuer ?");
        if (confirm_refuse_report == true) {
            $http({
                method: 'POST',
                url: '../ajax/refuseReport.php',
                data: $.param({
                    code_match: code_match
                }),
                headers: {'Content-Type': 'application/x-www-form-urlencoded'}
            }).then(function (response) {
                if (response.data.success) {
                    alert("Votre refus a été transmise à l'équipe adverse et au responsable de compétition");
                    window.location.reload();
                    return;
                }
                $scope.myTxt = "Erreur: " + response.data.message;
            });
        }
    };

    $scope.removePenalty = function (id_equipe, competition) {
        $http({
            method: 'POST',
            url: '../ajax/penalite.php',
            data: $.param({
                type: 'suppression',
                compet: competition,
                equipe: id_equipe
            }),
            headers: {'Content-Type': 'application/x-www-form-urlencoded'}
        }).then(function (response) {
            if (response.data.success) {
                window.location.reload();
                return;
            }
            $scope.myTxt = "Erreur: " + response.data.message;
        });
    };
    $scope.addPenalty = function (id_equipe, competition) {
        $http({
            method: 'POST',
            url: '../ajax/penalite.php',
            data: $.param({
                type: 'ajout',
                compet: competition,
                equipe: id_equipe
            }),
            headers: {'Content-Type': 'application/x-www-form-urlencoded'}
        }).then(function (response) {
            if (response.data.success) {
                window.location.reload();
                return;
            }
            $scope.myTxt = "Erreur: " + response.data.message;
        });
    };

    $scope.DecrementReportCount = function (id_equipe, competition) {
        $http({
            method: 'POST',
            url: '../ajax/decrementReportCount.php',
            data: $.param({
                compet: competition,
                equipe: id_equipe
            }),
            headers: {'Content-Type': 'application/x-www-form-urlencoded'}
        }).then(function (response) {
            if (response.data.success) {
                window.location.reload();
                return;
            }
            $scope.myTxt = "Erreur: " + response.data.message;
        });
    };
    $scope.IncrementReportCount = function (id_equipe, competition) {
        $http({
            method: 'POST',
            url: '../ajax/incrementReportCount.php',
            data: $.param({
                compet: competition,
                equipe: id_equipe
            }),
            headers: {'Content-Type': 'application/x-www-form-urlencoded'}
        }).then(function (response) {
            if (response.data.success) {
                window.location.reload();
                return;
            }
            $scope.myTxt = "Erreur: " + response.data.message;
        });
    };

    $scope.validateMatch = function (code_match) {
        $http({
            method: 'POST',
            url: '../ajax/certifierMatch.php',
            data: $.param({
                code_match: code_match
            }),
            headers: {'Content-Type': 'application/x-www-form-urlencoded'}
        }).then(function (response) {
            if (response.data.success) {
                window.location.reload();
                return;
            }
            $scope.myTxt = "Erreur: " + response.data.message;
        });
    };

    $scope.declareSheetReceived = function (code_match) {
        $http({
            method: 'POST',
            url: '../ajax/declareSheetReceived.php',
            data: $.param({
                code_match: code_match
            }),
            headers: {'Content-Type': 'application/x-www-form-urlencoded'}
        }).then(function (response) {
            if (response.data.success) {
                window.location.reload();
                return;
            }
            $scope.myTxt = "Erreur: " + response.data.message;
        });
    };

    $scope.invalidateMatch = function (code_match) {
        $http({
            method: 'POST',
            url: '../ajax/invalidateMatch.php',
            data: $.param({
                code_match: code_match
            }),
            headers: {'Content-Type': 'application/x-www-form-urlencoded'}
        }).then(function (response) {
            if (response.data.success) {
                window.location.reload();
                return;
            }
            $scope.myTxt = "Erreur: " + response.data.message;
        });
    };
}]);

scotchApp.controller('cupController', ['$scope', '$routeParams', '$http', function ($scope, $routeParams, $http) {
    $http.get("../ajax/getMatches.php", {
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
            url: '../ajax/certifierMatch.php',
            data: $.param({
                code_match: code_match
            }),
            headers: {'Content-Type': 'application/x-www-form-urlencoded'}
        }).then(function (response) {
            if (response.data.success) {
                window.location.reload();
                return;
            }
            $scope.myTxt = "Erreur: " + response.data.message;
        });
    };

    $scope.declareSheetReceived = function (code_match) {
        $http({
            method: 'POST',
            url: '../ajax/declareSheetReceived.php',
            data: $.param({
                code_match: code_match
            }),
            headers: {'Content-Type': 'application/x-www-form-urlencoded'}
        }).then(function (response) {
            if (response.data.success) {
                window.location.reload();
                return;
            }
            $scope.myTxt = "Erreur: " + response.data.message;
        });
    };

    $scope.invalidateMatch = function (code_match) {
        $http({
            method: 'POST',
            url: '../ajax/invalidateMatch.php',
            data: $.param({
                code_match: code_match
            }),
            headers: {'Content-Type': 'application/x-www-form-urlencoded'}
        }).then(function (response) {
            if (response.data.success) {
                window.location.reload();
                return;
            }
            $scope.myTxt = "Erreur: " + response.data.message;
        });
    };
}]);

scotchApp.controller('lastPostsController', function ($scope, $http) {
    $http.get("../ajax/getLastPosts.php")
        .then(function (response) {
            $scope.lastPosts = response.data;
        });
});


scotchApp.controller('webSitesController', function ($scope, $http) {
    $http.get("../ajax/getWebSites.php")
        .then(function (response) {
            $scope.webSites = response.data;
        });
});
scotchApp.controller('hallOfFameController', function ($scope, $http) {
    $http.get("../ajax/getHallOfFameDisplay.php")
        .then(function (response) {
            $scope.hallOfFame = response.data;
        });
});
scotchApp.controller('gymnasiumsController', function ($scope, $http) {
    $http.get("../ajax/getGymnasiums.php")
        .then(function (response) {
            $scope.gymnasiums = response.data;
        });
});

scotchApp.controller('volleyballImagesController', function ($scope, $http) {
    $http.get("../ajax/getVolleyballImages.php")
        .then(function (response) {
            $scope.volleyballImages = [];
            for (var i = 0; i < 20; i++) {
                response.data.photo[i]["index"] = i;
                $scope.volleyballImages.push(response.data.photo[i]);
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
