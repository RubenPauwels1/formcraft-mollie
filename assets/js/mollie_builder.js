FormCraftApp.controller('MollieController', function($scope, $http) {
	$scope.Init = function(){
		$scope.$parent.Addons.Mollie = $scope.$parent.Addons.Mollie || {};
		$scope.$parent.Addons.Mollie.mode = $scope.$parent.Addons.Mollie.mode || 'test';
	}
	$scope.$watchCollection('Addons', function(newCol, oldCol, scope) {
		if (typeof $scope.$parent.Addons!='undefined' && typeof $scope.$parent.addField!='undefined')
		{
			$scope.$parent.addField.payments.push({
				name: 'Mollie',
				fieldHTMLTemplate: "<div id='paywithmollie'><i class='fas fa-info-circle' id='infoicon'></i>" + objectL10n.infotext + "</div>",
				fieldOptionTemplate: "<label class='w-3'> <span>Name Label</span> <input type='text' ng-model='element.elementDefaults.name_label'></label><label class='w-3'> <span>Customer Email</span> <input type='text' ng-model='element.elementDefaults.mollie_email' placeholder='[field3]'></label><label class='w-3'> <span>Customer Firstname</span> <input type='text' ng-model='element.elementDefaults.mollie_firstname' placeholder='[field4]'></label><label class='w-3'> <span>Customer Lastname</span> <input type='text' ng-model='element.elementDefaults.mollie_lastname' placeholder='[field5]'></label><div ng-slide-toggle='element.elementDefaults.mollie_type=="+' "amount" '+"'> <label class='w-3'> <span>Amount</span> <input type='text' ng-model='element.elementDefaults.mollie_amount' placeholder='[ field1 + field2]'> </label></div><label class='w-3'> <input value='1' type='checkbox' ng-model='element.elementDefaults.hidden_default'> Hide Field on Page Load</label>",
				defaults: {
					main_label: 'Mollie',
					name_label: 'Name',
					currency: 'EUR',
					mollie_type: 'amount',
				}
			});
		}
	});
});