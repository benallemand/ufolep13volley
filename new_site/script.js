// create the module and name it scotchApp
var scotchApp = angular.module('scotchApp', ['ngRoute', 'ngAnimate', 'ui.bootstrap']);

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
        });
});

// create the controller and inject Angular's $scope
scotchApp.controller('mainController', function ($scope, $http) {
    // create a message to display in our view
    $http.get("../ajax/getLastCommit.php")
        .then(function (response) {
            $scope.lastCommit = response.data;
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

scotchApp.controller('phonebookController', ['$scope', '$routeParams', '$http', function ($scope, $routeParams, $http) {
    $http.get("../ajax/getTeam.php", {
        params: {
            id: $routeParams.id
        }
    })
        .then(function (response) {
            $scope.team = response.data;
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
    });

    $http.get("../ajax/getMatches.php", {
        params: {
            competition: $routeParams.competition,
            division: $routeParams.division
        }
    }).then(function (response) {
        $scope.matches = response.data;
    });
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
    $http.get("../ajax/getHallOfFame.php")
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
