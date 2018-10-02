$(function() {
	$('.datepicker').datetimepicker({
		format:'Y-m-d H:i:s',
		inline: true,
		step: 5,
		lang: $('#language').val(),
		defaultDate: new Date()
	});
});