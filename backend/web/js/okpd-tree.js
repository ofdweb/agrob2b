jQuery(document).ready(function () {
	$('.glyphicon-info-sign').popover();
    
    $('#okpd-select,#parent-select').chosen();
    
    $('#w0').on('click','#okpd_select_chosen .chosen-results li',function(){
        var text=$(this).text();
        text=text.replace(/\. /g,"");
        $('#division-name').val(text);
        
    });
    
    $('.group-index').find('.grid-view').find('td').find('a.toogle').on('click',function(){
        $(this).next().toggle(300);
        return false;
    });
});


function getTree(parent) {
	lock();
	$('#searchOkpdQuery').val('');
	$('#boxLoader'+parent).addClass('loader');
	var selected = [];
	
	$('.selected-okpd-box').each(function() {
		selected.push($(this).attr('iid'));
	});
	
	$.ajax({
		type:'POST',
		cache: false,
		url: '/ajax/get-okpd-tree',
		data: {
			parent: parent,
			selected: selected
		},
		success: function(response) {
			if (response) {
				$('#boxOkpdTree').html(response);
			}
			$('#boxLoader'+parent).removeClass('loader');
			unlock();
		}
	});
}


function clickChbxOkpd(el, id, name, code) {
	var isSelected = $(el).prop("checked");
	console.log(isSelected);
	
	if (isSelected) {
		$('#selectedOkpd').append('<li style="padding: 0;" class="selected-okpd-box" iid="'+id+'" id="selectedOkpd'+id+'"><input type="hidden" name="Tenders[okpdsId][]" value="'+id+'"/><table><tr><td><span class="badge">'+code+'</span></td><td><div style="padding: 5px;">'+name+'&nbsp;<i style="cursor: pointer;" class="glyphicon glyphicon-remove" onclick="unselectOkpd('+id+')"></i></div></td></tr></table></li>');
	} else {
		unselectOkpd(id);
	}
}

function unselectOkpd(id) {
	$('#selectedOkpd'+id).remove();
	if ($('#chbxOkpd'+id).length) {
		$('#chbxOkpd'+id).prop("checked", false);
	}
}

function getAllOkpds() {
	$('#searchOkpdQuery').val('');
	getTree(0);
	$('#btnOkpdAll').addClass('active');
	$('#btnOkpdFavorites').removeClass('active');
	$('#frmSearchOkpd').show();
}

function getOkpdFavorites() {
	lock();
	$('#searchOkpdQuery').val('');
	var selected = [];
	
	$('.selected-okpd-box').each(function() {
		selected.push($(this).attr('iid'));
	});
	
	$.ajax({
		type:'POST',
		cache: false,
		url: '/ajax/get-okpd-favorites',
		data: {
			selected: selected
		},
		success: function(response) {
			if (response) {
				$('#boxOkpdTree').html(response);
			}
			
			$('#btnOkpdFavorites').addClass('active');
			$('#btnOkpdAll').removeClass('active');
			$('#frmSearchOkpd').hide();
			unlock();
		}
	});
}


function searchOkpd(page) {
	var query = $('#searchOkpdQuery').val();
	var selected = [];
	
	$('.selected-okpd-box').each(function() {
		selected.push($(this).attr('iid'));
	});
	
	
	if (query.trim()!='' && query.length > 2) {
		lock();
		$('#boxSearchLoader').addClass('loader');
		
		$.ajax({
			type:'POST',
			cache: false,
			url: '/ajax/okpd-search',
			data: {
				query: query,
				selected: selected,
				page: page,
			},
			success: function(response) {
				if (response) {
					$('#boxOkpdTree').html(response);
				}
				
				$('#boxSearchLoader').removeClass('loader');
				unlock();
			}
		});
	}
}

function lock() {
	$('#boxOkpdLoader').addClass('loader');
	$('#btnOkpdAll').attr('disabled','disabled');
	$('#btnSearchOkpd').attr('disabled','disabled');
	$('#btnOkpdFavorites').attr('disabled','disabled');
}
function unlock() {
	$('#btnOkpdAll').removeAttr('disabled');
	$('#btnSearchOkpd').removeAttr('disabled');
	$('#btnOkpdFavorites').removeAttr('disabled');
	$('#boxOkpdLoader').removeClass('loader');
}