



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
				$scope.turfs = response.turfs;
				$scope.streets = response.streets;
			});
		}

		$scope.updateData = function(){
			$scope.processApi("updateTotals", {}, function(response){
				$scope.turfs = response.turfs;
				$scope.streets = response.streets;
			});
		}


		$scope.addTurf = function(){
			var t = angular.copy($scope.new_turf);
			$scope.processApi("createTurf", t, function(response){
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
				$scope.selected_streetIds = [];
				angular.forEach($scope.selected_streets, function(s, i){ 
					$scope.selected_streetIds.push(s.streetid) 
				})

				console.log($scope.selected_streetIds);

				$scope.clearMap();
				$scope.redrawAddresses({turfid : $scope.selected_turf.turfid})

			}
		}

		$scope.deleteTurf = function(){
			var r = {
				turfid : $scope.selected_turf.turfid
			}
			$scope.processApi("deleteTurf", r, function(response){
				$scope.turfs = response.turfs;
				$scope.streets = response.streets;
				$scope.selected_turf = "";
				$scope.tab = 'mgr';
			});
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



		// MANAGE STREETS

		$scope.addStreet = function(){

			$scope.selected_street.turfid = $scope.selected_turf.turfid;

			// API CALL
			var r = {
				turfid : $scope.selected_turf.turfid,
				streetid : $scope.selected_street.streetid	
			}

			$scope.processApi("updateTurfAssignment", r, function(updated_turf){
				for(var field in updated_turf){
					$scope.selected_turf[field] = updated_turf[field];
				}
			});

			$scope.loadTurf();
		}

		$scope.selectStreet = function(street){
			$scope.selected_street = street;
			$scope.clearMap();
			$scope.redrawAddresses({ streetId : street.streetid })

		}

		$scope.deselect_street= function(){
			$scope.selected_street = false;
			$scope.redrawAddresses({"turfid" : $scope.selected_turf.turfid});
		}

		$scope.removeStreetFromTurf = function(){
			
			// API CALL
			var r = {
				turfid : $scope.selected_turf.turfid,
				streetid : $scope.selected_street.streetid	
			}

			$scope.processApi("removeStreetFromTurf", r, function(updated_turf){
				for(var field in updated_turf){
					$scope.selected_turf[field] = updated_turf[field];
				}

				$scope.selected_street.turfid = "";
				$scope.selected_street = false;
				$scope.loadTurf();

			});
			
		}

		$scope.redrawAddresses = function(opts){
			for(var i = 0; i < markers.length; i++){
				marker = markers[i];
				if(!opts){
					marker.setMap(map);
					mc.addMarker(marker);
				}
				else if("streetId" in opts && marker.streetId == opts.streetId){
					marker.setMap(map);
				}
				else if("turfid" in opts && $scope.selected_streetIds.indexOf(marker.streetId) !== -1) {
					marker.setMap(map);
				}
			}
		}

		$scope.clearMap = function(){
			for(var i = 0; i < markers.length; i++){
				marker = markers[i];
				mc.removeMarker_(marker);
				marker.setMap(null);
			}
			mc.resetViewport();
		}

		// AND AWAY WE GO!!!
		$scope.init();
	}
]);



function initMap() {

	map = new google.maps.Map(document.getElementById('map'), {
		zoom: 11,
		center: {lat: 43.8961, lng: -69.9632},
		// mapTypeId: 'terrain'
	});

	
	markers = [];
	
	// Create an array of alphabetical characters used to label the markers.
	var labels = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';

	

	for(var i = 0; i < voters.length; i++){
		var location = voters[i];

		var m = new google.maps.Marker({
			position: {
				lat : parseFloat(location.lat),
				lng : parseFloat(location.lon)
			},
			label: labels[i % labels.length]
		});
		m.streetId = location.streetId;
		m.setMap(map);
		markers.push(m);
	}


	// Add a marker clusterer to manage the markers.
	mc = new MarkerClusterer(map, markers,
	    {imagePath: 'https://developers.google.com/maps/documentation/javascript/examples/markerclusterer/m'});
	
}





