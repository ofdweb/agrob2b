$(document).ready(function(){
	
	var selectApp = $('#app-id');
	var selectPremission = $("#permissions-id");
	
	selectApp.val("");
	selectPremission.prop('disabled','disabled');
	
	var dataArray = new Array();
	
	selectApp.select2().on("change", function(e) {
		$.ajax({
			url: '/accespanel/tokens/get-defaults-permission?id='+e.currentTarget.value,
			beforeSend: function (data) {
				console.log(e.currentTarget.value);
				selectPremission.prop('disabled','disabled');
			},
			success: function (data) {
				dataArray = data.split(',');
				selectPremission.val(dataArray).trigger("change");
				selectPremission.prop('disabled','');
			}
		});
		$.ajax({
			url: '/accespanel/tokens/get-default-date-to?id='+e.currentTarget.value,
			success: function (data) {
				$('#app-dateto').val(data);
			}
		});
	})
});