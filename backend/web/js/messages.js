$(function() {
	$('.chbx-filter-company').click(function() {
		//$('.chbx-filter-company').prop('checked', false);
		//$(this).prop('checked', true);
		var rel = $(this).attr('rel');
	
		$('#invitations-companys option').hide(0);
		if (!(rel > 0)) $('#invitations-companys option').show(0);
		if (rel == 1) $('[isconsumer = 1]').show(0);
		if (rel == 2) $('[issupplier = 1]').show(0);
		
		$('#invitations-companys').trigger("chosen:updated");
	});
});


function showMessageForm(userId, toName, fromName) {
	if ($('#messageForm').length) {
		$('#messageForm').attr('toId', userId);
		$('#messageFormSubjectBox').removeClass('has-error');
		$('#messageFormTextBox').removeClass('has-error');
		$('#messageFormSubject').val('');
		$('#messageFormText').val('');
		
		$('#messageForm').modal('show');
		
		$('#messageFormFromName').html(fromName);
		$('#messageFormToName').html(toName);
	} else {
		console.log('не подключен шаблон формы')
	}
}


function sendMessage() {
	var isErrors = false;
	
	if ($('#messageFormSubject').val()) {
		$('#messageFormSubjectBox').removeClass('has-error');
	} else {
		$('#messageFormSubjectBox').addClass('has-error');
		isErrors = true;
	}
	
	if ($('#messageFormText').val()) {
		$('#messageFormTextBox').removeClass('has-error');
	} else {
		$('#messageFormTextBox').addClass('has-error');
		isErrors = true;
	}
	
	if (!isErrors) {
		$.ajax({
			type:'POST',
			cache: false,
			url: '/ajax/send-message',
			data: {
				to: $('#messageForm').attr('toId'),
				subject: $('#messageFormSubject').val(),
				text: $('#messageFormText').val()
			},
			success: function(response) {
				if (response) {
					showMessage(response);					
				}
				$('#messageForm').modal('hide');
			}
		});
	}
}