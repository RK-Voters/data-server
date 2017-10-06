
var addresses = [];
$.each(voters, function(i, v){
	addresses.push({
		lat : parseFloat(v.lat),
		lng : parseFloat(v.lon)
	})
});



var app = angular.module('turfMgrApp', ['ui.bootstrap']);

app.controller('turfMgrCtrl', ['$scope', '$http', '$sce', '$rootScope', '$window', '$modal',
	function($scope, $http, $sce, $rootScope, $window, $modal){

		window.$scope = $scope;

		$scope.tab = 'mgr';

		$scope.selected_turf   = "";
		$scope.selected_street = false;

		$scope.search = {
			street_name : ''
		}

		$scope.processApi = function(api, request, callback){
			var req = {
 				method: 'POST',
 				url: "/biz/_rkvoter/data-api/api/app.php",
 				data: {
 					"api" : api,
 					"access_token" : "0bf1b21c-4672-4171-a76a-459cefbfe180",
 					"campaign_slug" : "mac",
 					"terms" : request
				}
			}

			$http(req).then(
				function(response){
					callback(response.data);
				}, 
				function(error){
					console.log("API Error.");
					console.log(error);
				}
			);
		}

		
		$scope.init = function(){
			$scope.processApi("getStreetAndTurfLists", {}, function(response){
				$scope.turfs = response;
			});
		}


		$scope.addTurf = function(){

			// replace this with an API call
			var t = angular.copy($scope.new_turf);
			
			$scope.processApi("addTurf", t, function(response){
				t.turfid = response;
				$scope.turfs.push(t);
				$scope.selected_turf = t;
				$scope.tab = 'mgr';
			});

		}

		$scope.loadTurf = function(){

			if($scope.selected_turf == "ADD"){
				$scope.tab = "add_turf";
				$scope.selected_turf = "";
				$scope.new_turf = {
					turf_name : ''
				}
			}
			else {
				$scope.tab = 'mgr';
				$scope.selected_streets = $scope.streets.filter(
					function(street){ 
						return street.turfid == $scope.selected_turf.turfid 
					}
				);

			}

		}




		$scope.openAdder = function(){
			$scope.tab = 'adder';
			$scope.potential_streets = $scope.streets.filter(
				function(street){ 
					return street.turfid == 0; 
				}
			);
		}

		$scope.filterStreets = function(){
			$scope.potential_streets = $scope.streets.filter(
				function(street){ 

					l = street.street_name.toUpperCase();

					s = $scope.search.street_name.toUpperCase();

					return street.turfid == 0 && l.indexOf(s) == 0; 
				}
			);
		}


		$scope.addStreet = function(){

			$scope.selected_street.turfid = $scope.selected_turf.turfid;

			// API CALL

			$scope.loadTurf();
		}


		$scope.selectStreet = function(street){
			$scope.selected_street = street;
		}

		$scope.deselect_street= function(){
			$scope.selected_street = false;
		}

		$scope.removeStreetFromTurf = function(){
			
			// make api call
			

			$scope.selected_street.turfid = "";
			$scope.selected_street = false;
			$scope.loadTurf();

		}

		// AND AWAY WE GO!!!
		$scope.init();
	}
]);



function initMap() {

	var map = new google.maps.Map(document.getElementById('map'), {
		zoom: 11,
		center: {lat: 43.8961, lng: -69.9632},
		// mapTypeId: 'terrain'
	});


	// Create an array of alphabetical characters used to label the markers.
	var labels = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';

	// Add some markers to the map.
	// Note: The code uses the JavaScript Array.prototype.map() method to
	// create an array of markers based on a given "locations" array.
	// The map() method here has nothing to do with the Google Maps API.
	var markers = addresses.map(function(location, i) {
	  return new google.maps.Marker({
	    position: location,
	    label: labels[i % labels.length]
	  });
	});

	// Add a marker clusterer to manage the markers.
	var markerCluster = new MarkerClusterer(map, markers,
	    {imagePath: 'https://developers.google.com/maps/documentation/javascript/examples/markerclusterer/m'});
	

}



