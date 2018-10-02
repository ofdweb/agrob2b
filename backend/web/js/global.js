$(document).ready(function() {
	
	$('.phone').mask('+7 (999) 999-99-99');
	
	//$(".multi-select").multiselect().multiselectfilter();	
	//$('#tree').treed({openedClass:'glyphicon-folder-open', closedClass:'glyphicon-folder-close'});
	
	$('.cat-name').on('click', function() {
		/*
		var li = $(this).closest("li");
		var icon = li.children('i');
		var ul = li.children('ul');
		icon.toggleClass("glyphicon-folder-open glyphicon-folder-close");
		*/
	});
	
	$('.tender-config-tab').click(function() {
		$('.tender-config-tab').removeClass('active');
		$(this).addClass('active');
		
		$('.tender-config-box').hide();
		$('#tender_config_box_'+$(this).attr('iid')).show();
	});
	
	$('#inptCreateActsDate').daterangepicker({
		minDate: new Date($('#yearCompanyCreate').val(), 0, 1),
		maxDate: new Date(),
		format: 'DD.MM.YYYY'
	});
	
	$('#inptCreateActsDate').on('show.daterangepicker', function(ev, picker) {
		console.log('ok');
		$(".daterangepicker.calendar.right").hide();
	});

	if ($('#companystatisticsfilterform-daterange').length) {
		$('#companystatisticsfilterform-daterange').daterangepicker({
			"startDate": $('#companystatisticsfilterform-daterange').attr('startdate'),
			"endDate": $(this).attr('stopdate')
		}, function(start, end, label) {
			//console.log("New date range selected: ' + start.format('YYYY-MM-DD') + ' to ' + end.format('YYYY-MM-DD') + ' (predefined range: ' + label + ')");
		});
	}
	
	if ($('#companydocs-dateadd').length) {
		$('#companydocs-dateadd').daterangepicker({
			"startDate": $('#companydocs-dateadd').attr('startdate'),
			"endDate": $(this).attr('stopdate')
		}, function(start, end, label) {
			//console.log("New date range selected: ' + start.format('YYYY-MM-DD') + ' to ' + end.format('YYYY-MM-DD') + ' (predefined range: ' + label + ')");
		});
	}
    
    var chartMirror=$('#line-chart').find('.morris-hover');
    $('#line-chart').find('circle').on('mouseover',function(){
        $(this).attr('r',7);
        chartMirror.find('.morris-hover-row-label').text($(this).data('date')).next().find('.count').text($(this).data('count'));
        var cx=parseFloat($(this).attr('cx'))-chartMirror.outerWidth()/2+'px';
        var cy=parseFloat($(this).attr('cy'))-chartMirror.outerHeight()-20+'px';
        chartMirror.css({'left':cx,'top':cy}).fadeIn(300);
    });
    $('#line-chart').find('circle').on('mouseleave',function(){
        chartMirror.fadeOut(300);
        $(this).attr('r',5);
    });
    
    $('.messages-menu,.user-menu').on('mouseover',function(){
        $(this).find('.dropdown-menu').fadeIn(300);
    });
    $('.messages-menu,.user-menu').on('mouseleave',function(){
        $(this).find('.dropdown-menu').fadeOut(300);
    });
    
    $('#tenderFilter>a').click(function(){
        $(this).parent().toggleClass('active');
    });
    
    $('.unvictoryLink').click(function(){
        $(this).next().toggle();
        return false;
    });
    
    $('.target').click(function(e) {
        e.preventDefault(); //prevents the default submit action
        $(this).closest('form').attr('target', '_blank').submit();
        setTimeout(function(){location.reload();},500);
    });
    
    $('.konvert_modal').click(function(e) {
        e.preventDefault(); //prevents the default submit action
        $($(this).data('target')).modal('show');
    });
    
    $('.statistic_ajax_load').click(function(e) {
        e.preventDefault(); //prevents the default submit action
        var url = $(this).attr('href');
        var key = $(this).data('key');
        
        $.pjax.reload({
            type: "POST",
            url:url,
            container: "#container_statistic_" + key,
        }); 
    });
});

function linkToogle(el) {
    $(el).closest('.cont').find('.groupList').slideToggle();
    return false;
}