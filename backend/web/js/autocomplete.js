$(function() {
    
	/*
	$("#companyaddress-address").change(function() {
		console.log($(this).val());
		//if ($(this).val().lenght)
	});
	*/
	
	$("#companyaddress-address").autocomplete({
		serviceUrl: "/ajax/address-search", 		// url-адрес
		minChars: 2, 						// минимальное количество для совершения запроса
		onSelect: function(data, value) {
			$('#companyaddress-postindex').val(data.data.index);
			$('#companyaddress-kladr').val(data.data.kladr_id);
		}
	});
	
	$("#shippingform-addressfrom").autocomplete({
		serviceUrl: "/ajax/address-search", 		// url-адрес
		minChars: 2, 						// минимальное количество для совершения запроса
		onSelect: function(data, value) {
			$('#shippingform-addressfrompostC').val(data.data.index).trigger('change');
			$('#shippingform-addressfromkladrC').val(data.data.kladr_id).trigger('change');
			$('#shippingform-addressfrompost').val(data.data.index).trigger('change');
			$('#shippingform-addressfromkladr').val(data.data.kladr_id).trigger('change');
			$('#shippingform-addressfromregion').val(data.data.region).trigger('change');
			
			$('#shippingform-addressfrom').trigger('change');
		}
	});
	
	$("#shippingform-addressto").autocomplete({
		serviceUrl: "/ajax/address-search", 		// url-адрес
		minChars: 2, 						// минимальное количество для совершения запроса
		onSelect: function(data, value) {
			$('#shippingform-addresstopostC').val(data.data.index).trigger('change');
			$('#shippingform-addresstokladrC').val(data.data.kladr_id).trigger('change');
			$('#shippingform-addresstopost').val(data.data.index).trigger('change');
			$('#shippingform-addresstokladr').val(data.data.kladr_id).trigger('change');
			$('#shippingform-addresstoregion').val(data.data.region).trigger('change');
			
			$('#shippingform-addressto').trigger('change');
		}
	});
	
	$("#address").autocomplete({
		serviceUrl: "/ajax/address-search", 		// url-адрес
		minChars: 2, 						// минимальное количество для совершения запроса
		onSelect: function(data, value) {
			$('#postIndex').val(data.data.index);
			$('#kladr').val(data.data.kladr_id);
			$('#kladr_hidden').val(data.data.kladr_id);
		}
	});
	
	
	
	/*
	$("#companyaddress-address").suggestions({
		serviceUrl: "/ajax/address-search",
		token: "21837322f7779ee4a713b4929ee72d4cf4d39133",
		type: "ADDRESS",
		
		// Вызывается, когда пользователь выбирает одну из подсказок
		onSelect: function(suggestion) {
			console.log(suggestion);
		}
	});
	*/
	
});

function addArress() {
	//if ($('#companyaddress-kladr').val()) {
		$('#btnAddAddress').attr('disabled', true);
		$.ajax({
			type:'POST',
			cache: false,
			url: '/ajax/add-address',
			data: $("#frmAddressAdd").serialize(),
			dataType: 'json',
			success: function(response) {

				/*if (response.error.lenght) {
					$('.top-right').notify({
						type: 'warning',
						message: { text: 'Такой адрес не найден' }
					}).show(); 
				} else */
				
				if (response) {
					$('#boxAddresses div').append('<label><input type="checkbox" '+
						'value="'+response.id+'" name="Tenders[addressIds][]">'+ 
						response.address +' </label>'
					);
					showMessage('Адрес добавлен');	
					
					$('#boxAddressAdd').modal('toggle');
					$('#btnAddAddress').attr('disabled', false);
					
					$('#companyaddress-address').val('');
					$('#companyaddress-postindex').val('');
					$('#ccompanyaddress-kladr').val('');
				}
			}
		});
		
	/*
	} else {
		showMessage('На выбран адрес');
		$('.top-right').notify({
			type: 'warning',
			message: { text: 'На выбран адрес' }
		}).show(); 
		return false;
	}
	*/
	
}