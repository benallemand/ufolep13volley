// create the module and name it scotchApp
var scotchApp = angular.module('scotchApp', ['ngRoute']);

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
        });
});

// create the controller and inject Angular's $scope
scotchApp.controller('mainController', function ($scope, $http) {
    // create a message to display in our view
    $scope.message = 'Everyone come and see how good I look!';
    $http.get("../ajax/getLastCommit.php")
        .then(function (response) {
            $scope.lastCommit = response.data;
        });

});

scotchApp.controller('lastResultsController', function ($scope, $http) {
    $http.get("../ajax/getLastResults.php")
        .then(function (response) {
            $scope.lastResults = response.data;
        });
});

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