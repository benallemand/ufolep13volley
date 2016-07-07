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
            controller: 'championshipsController'
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

scotchApp.controller('championshipsController', ['$scope', '$routeParams', '$http', function ($scope, $routeParams, $http) {
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

// scotchApp.controller('CarouselDemoCtrl', function ($scope) {
//     var slides = $scope.slides = [];
//     var currIndex = 0;
//
//     $scope.addSlide = function () {
//         var newWidth = 600 + slides.length + 1;
//         slides.push({
//             image: 'http://lorempixel.com/' + newWidth + '/300',
//             text: ['Nice image', 'Awesome photograph', 'That is so cool', 'I love that'][slides.length % 4],
//             id: currIndex++
//         });
//     };
//
//     $scope.randomize = function () {
//         var indexes = generateIndexesArray();
//         assignNewIndexesToSlides(indexes);
//     };
//
//     for (var i = 0; i < 4; i++) {
//         $scope.addSlide();
//     }
//
//     // Randomize logic below
//
//     function assignNewIndexesToSlides(indexes) {
//         for (var i = 0, l = slides.length; i < l; i++) {
//             slides[i].id = indexes.pop();
//         }
//     }
//
//     function generateIndexesArray() {
//         var indexes = [];
//         for (var i = 0; i < currIndex; ++i) {
//             indexes[i] = i;
//         }
//         return shuffle(indexes);
//     }
//
//     // http://stackoverflow.com/questions/962802#962890
//     function shuffle(array) {
//         var tmp, current, top = array.length;
//
//         if (top) {
//             while (--top) {
//                 current = Math.floor(Math.random() * (top + 1));
//                 tmp = array[current];
//                 array[current] = array[top];
//                 array[top] = tmp;
//             }
//         }
//
//         return array;
//     }
// });