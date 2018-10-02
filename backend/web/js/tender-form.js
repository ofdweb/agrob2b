var tplIconNotFilled; 
var tplIconFilled; 
var isFormChange = false;

var countValues = 0;


$(function() {
	countValues = parseInt($('.feature-row').length) - 1;
	
	refreshTitlteFeature();
	$('#btnNewFeature').click(function(){
		countValues++;

		$('#tplFeature').find('.feature-name').attr('name', "Tenders[dopValues]["+countValues+"][name]");
		$('#tplFeature').find('.feature-type').attr('name', "Tenders[dopValues]["+countValues+"][type]");
		$('#tplFeature').find('.feature-values').attr('name', "Tenders[dopValues]["+countValues+"][values]");
		$('#tplFeature').find('.feature-default').attr('name', "Tenders[dopValues]["+countValues+"][default]");
		$('#tplFeature').find('.feature-default2').attr('name', "Tenders[dopValues]["+countValues+"][default2]");
		$('#tplFeature').find('.feature-required').attr('name', "Tenders[dopValues]["+countValues+"][required]");
		
		$('#boxFeatures').append($('#tplFeature').html());
		
		$('#tplFeature').find('.feature-name').attr('name', "");
		$('#tplFeature').find('.feature-type').attr('name', "");
		$('#tplFeature').find('.feature-values').attr('name', "");
		$('#tplFeature').find('.feature-default').attr('name', "");
		$('#tplFeature').find('.feature-default2').attr('name', "");
		$('#tplFeature').find('.feature-required').attr('name', "");
		
		refreshTitlteFeature();
	});
	
	$('input[type=submit], .btn-change-ignore').click(function() {
		isFormChange = false;
	});
	
	jQuery(window).bind('beforeunload', function (){
      if (isFormChange) return $('#translate-leaveform').val();
    });
	
	$("form").find("input,select,textarea").not('[type="submit"]').change(function(){
		isFormChange = true;
	});
	
	$('#tenders-lotid').change(function() {
		if ($(this).val() == 'new') {
			$('#box-new-lot-name').show();
		} else {
			$('#box-new-lot-name').hide();
		}
	});
	
	$('#tenders-autoisprolong').change(function() {
		if ($(this).prop('checked')) {
			$('#boxAutoProlong').show();
		} else {
			$('#boxAutoProlong').hide();
		}
	});
	$('#tenders-selltype input').change(function() {
		if ($(this).val() == 'prices') {
			$('.tenders-isnotuseprice').prop('checked', false);
			$('.isnotuseprice').hide();
			$('#boxPFU').show();
			$('#boxPUA').show();
			$('#boxPU').show();
			$('.boxNdsBx').show();
			
			if (!$('#tenders-pricefromfullprice').prop('checked')) {
				$('#boxPriceItem').show();
			}
			
			if (!$('#tenders-fullpricefromitemprice').prop('checked')) {
				$('#boxPriceAll').show();
			}
			
		} else {
			$('.isnotuseprice').show();
			/*			
			$('#boxPFU').hide();
			$('#boxPUA').hide();
			$('#boxPriceAll').hide();
			$('#boxPU').hide();
			$('.boxNdsBx').hide();
			*/
		}
		
		fieldsValidate();
		
		/*
		if (prop) {
			$('#boxPFU').hide();
			$('#boxPUA').hide();
			$('#boxPriceAll').hide();
			$('#boxPU').hide();
			$('.boxNdsBx').hide();
		} else {
			$('#boxPFU').show();
			$('#boxPUA').show();
			$('#boxPriceAll').show();
			$('#boxPU').show();
			$('.boxNdsBx').show();
		}
		*/
	});
	
	tplIconNotFilled = $('#tplIconNotFilled').html(); 
	tplIconFilled = $('#tplIconFilled').html(); 
	
	fieldsValidate();
	$('#tenders-ispoposition, #tenders-priceunitwithoutvat, #tenders-vatpercent, #tenders-priceunitvat, #tenders-pricefullunitwithoutvat, #tenders-vatfullpercent, #tenders-pricefullunitvat, #tenders-dateoffersstop, #tenders-productdesc, #tenders-productcount, #tenders-stepvalue, #tenders-conditionpayment, #tenders-conditiondelivery').keypress(fieldsValidate);
	$('#tenders-ispoposition, #tenders-priceunitwithoutvat, #tenders-vatpercent, #tenders-priceunitvat, #tenders-pricefullunitwithoutvat, #tenders-vatfullpercent, #tenders-pricefullunitvat, #tenders-dateoffersstop, #tenders-productdesc, #tenders-productcount, #tenders-stepvalue, #tenders-conditionpayment, #tenders-conditiondelivery').change(fieldsValidate);

	// отлавливаем изменение контейнера с выбранными категориями
	$('#selectedOkpd').bind("DOMSubtreeModified", fieldsValidate);

	// изменения в выбранных адресах
	$('#boxAddresses input, #tenders-fullpricefromitemprice, #tenders-pricefromfullprice, .tenders-isnotuseprice, .tenders-notvat').change(fieldsValidate); 

	
	if ($('#boxMessageDraft').length) {
		$('#boxMessageDraft').modal();
	}
	
	if ($('#boxMessageDraft').length==0 && $('#isNoUseDraft').length==0 && $('#mode').length) {
		if ($('#mode').val() == 'add') {
			setInterval(setDraft, 30000);
		}
	}
	
	if ($('.frmError').length) {
		var to = '';
		$('.frmError').each(function() {
			if (parseInt($(this).attr('ers')) > 0 && to == '') {
				//console.log($(this).attr('scroll'));
				to = $(this).attr('scroll');
			}
		});
		
		$.scrollTo('#'+to, {duration:1000});
	}
	
	/*	
	$("#tenders-stepvalue, #tenders-priceunitwithoutvat, #tenders-vatpercent, #tenders-priceunitvat, #tenders-pricefullunitvat, #tenders-vatfullpercent, #tenders-pricefullunitwithoutvat, #tenders-productcount").keypress(function(key) {
		var chs = [0, 46, 48, 49, 50, 51, 52, 53, 54, 55, 56, 57];
		if(!chs.in_array(key.charCode)) return false;
	});
	*/
	
	$("#tenders-stepvalue, #tenders-priceunitwithoutvat, #tenders-vatpercent, #tenders-priceunitvat, #tenders-pricefullunitvat, #tenders-vatfullpercent, #tenders-pricefullunitwithoutvat, #tenders-productcount").numberMask({
		type:'float',
		afterPoint:4,
		defaultValueInput:'15.1',
		decimalMark:'.'
	});

	
	//var dateNow = new Date();
    //date.setMonth(date.getMonth() + 1, 1);
    //$('#mydate').datepicker({defaultDate: date});
	
	$('.datepicker').datetimepicker({
		format:'Y-m-d H:i:s',
		inline: false,
		showApplyButton: true,
		step: 5,
		lang: $('#language').val(),
		defaultDate: new Date()
	});
	
	/*
	$('.datepicker').datetimepicker({
		format: 'YYYY-MM-DD HH:mm:ss',
		showMeridian: true,
		initialDate: new Date(),
		language: 'ru'
	});
	*/
	
	/*
	$('.datepicker').datepicker();
	$('.timepicker').timepicker({
		showInputs: false,
		showMeridian: false,
		minuteStep: 5,
	});
	*/
	
	$(".input_phone").inputmask("(999) 999-99-99", {"placeholder": "(999) 999-99-99"});
	
	$('#btnDopInfoToggle').click(function() {
		if ($('#boxDopInfo').is(":visible")) {
			$('#boxDopInfo').slideUp();
			$(this).find('.glyphicon').removeClass('glyphicon-minus');
			$(this).find('.glyphicon').addClass('glyphicon-plus');
			$(this).attr('title', 'Развернуть');
		} else {
			$('#boxDopInfo').slideDown();
			$(this).find('.glyphicon').removeClass('glyphicon-plus');
			$(this).find('.glyphicon').addClass('glyphicon-minus');
			$(this).attr('title', 'Свернуть');
		}
		
		return false;
	});
	
	$('#tenders-vatpercent,#tenders-priceunitvat').focusout(function() {
		if ($('#tenders-priceunitvat').val() != '') {
			if($('#tenders-vatpercent').val())    $('#tenders-priceunitwithoutvat').val(parseFloat((parseFloat($('#tenders-priceunitvat').val()) / (100 + parseFloat($('#tenders-vatpercent').val()))) * 100).toFixed(2));
            else if($('#tenders-priceunitwithoutvat').val())    $('#tenders-priceunitwithoutvat').val('');
        }
  	});
    
    $('#tenders-priceunitwithoutvat,#tenders-vatpercent').focusout(function() {
        if ($('#tenders-priceunitwithoutvat').val() != '' && $('#tenders-priceunitvat').val() == '') {
            if($('#tenders-vatpercent').val())  $('#tenders-priceunitvat').val(parseFloat((parseFloat($('#tenders-priceunitwithoutvat').val()) * (100 + parseFloat($('#tenders-vatpercent').val()))) / 100).toFixed(2));   
        }
    });
    
    /* ***************** */
	
    $('#tenders-vatfullpercent,#tenders-pricefullunitvat').focusout(function() {
		if ($('#tenders-pricefullunitvat').val() != '') {
			if($('#tenders-vatfullpercent').val())    $('#tenders-pricefullunitwithoutvat').val(parseFloat((parseFloat($('#tenders-pricefullunitvat').val()) / (100 + parseFloat($('#tenders-vatfullpercent').val()))) * 100).toFixed(2));
            else if($('#tenders-pricefullunitwithoutvat').val())    $('#tenders-pricefullunitwithoutvat').val('');
        }
  	});
    
    $('#tenders-pricefullunitwithoutvat,#tenders-vatfullpercent').focusout(function() {
        if ($('#tenders-pricefullunitwithoutvat').val() != '' && $('#tenders-pricefullunitvat').val() == '') {
            if($('#tenders-vatfullpercent').val())  $('#tenders-pricefullunitvat').val(parseFloat((parseFloat($('#tenders-pricefullunitwithoutvat').val()) * (100 + parseFloat($('#tenders-vatfullpercent').val()))) / 100).toFixed(2));   
        }
    });
	
	
	$('#btnRulesToggle').click(function() {
		if ($('#boxRules').is(":visible")) {
			$('#boxRules').slideUp();
			$(this).find('.glyphicon').removeClass('glyphicon-minus');
			$(this).find('.glyphicon').addClass('glyphicon-plus');
			$(this).attr('title', 'Развернуть');
		} else {
			$('#boxRules').slideDown();
			$(this).find('.glyphicon').removeClass('glyphicon-plus');
			$(this).find('.glyphicon').addClass('glyphicon-minus');
			$(this).attr('title', 'Свернуть');
		}
		
		return false;
	});
	
	
	$('#btnOkpd').click(function() {
		$('#boxOkpd').toggle();
		
		if ($('#boxOkpd').is(":visible")) {
			$(this).addClass('dropup');
			$(this).html('скрыть <span class="caret"></span>');
		} else { 
			$(this).removeClass('dropup');
			$(this).html('показать <span class="caret"></span>');
		}
		
		return false;
	})
	
	$('#tenders-guaranteebankrequire').change(function() {
		if ($(this).prop("checked")) {
			$('.guarantee-bank-fields').show();
		} else {
			$('.guarantee-bank-fields').hide();
		}
	});
	
	$('#tenderdefault-phonecodelist').change(function() {
		$('#tenderdefault-phonecode').val($(this).val());
	});
	
	$('#tenders-phonecodelist').change(function() {
		$('#tenders-phonecode').val($(this).val());
	});
	
	$('#tenders-pricefromfullprice').change(function() {
		var checkAll = $('#tenders-fullpricefromitemprice');
		var check = $(this);
		
		var priceNds = $('#tenders-pricefullunitvat');
		var priceNdsBox = $('.field-tenders-pricefullunitvat');
		
		var price = $('#tenders-pricefullunitwithoutvat');
		var priceBox = $('.field-tenders-pricefullunitwithoutvat');
		
		var isCheckedAll = checkAll.prop("checked");
		var isChecked = check.prop("checked");
		
		if (isChecked && isCheckedAll) checkAll.prop("checked", false);
		
		/*
		var isError = false;
		
		if (parseInt(priceNds.val())==0) {
			priceNdsBox.addClass('has-error');
			isError = true;
		}
		
		if (parseInt(priceNds.val())==0) {
			priceNdsBox.addClass('has-error');
			isError = true;
		}
		
		if (!isError) {
			
		}
		
		*/
	});
	
	$('#tenders-fullpricefromitemprice').change(function() {
		var check = $('#tenders-pricefromfullprice');
		var checkAll = $(this);

		var isChecked = check.prop("checked");
		var isCheckedAll = checkAll.prop("checked");
		
		if (isChecked && isCheckedAll) check.prop("checked", false);
	});
	
	
	$('#tenders-ispoposition').change(function() {
		if ($(this).prop("checked")) {
			$('.general-price').hide();
			$('#boxAPNP').show(300);
			$('#boxAPL').show(300);
			$('#boxCats').hide(300);
			$('#boxDopValues').hide(300);
			
			if ($('#tenders-isallowpositionsnoprice').prop("checked")) {
				$('#boxSP').show(300);
			}
		} else {
			$('.general-price').show();
			$('#boxAPNP').hide(300);
			$('#boxAPL').hide(300);
			$('#boxSP').hide(300);
			$('#boxCats').show(300);
			$('#boxDopValues').show(300);
		} 
	});
	
	$('#tenders-isallowpositionsnoprice').change(function() {
		if ($(this).prop("checked")) {
			$('#boxSP').show(300);
		} else {
			$('#boxSP').hide(300);
		}
	});
	
	$('.tenders-isnotuseprice').change(function() {
		var prop = $(this).prop("checked");		
		$('.tenders-isnotuseprice').prop("checked", prop);
		
		if (prop) {
			$('#boxPFU').hide();
			$('#boxPUA').hide();
			$('#boxPriceAll').hide();
			$('#boxPU').hide();
			$('.boxNdsBx').hide();
		} else {
			$('#boxPFU').show();
			$('#boxPUA').show();
			$('#boxPriceAll').show();
			$('#boxPU').show();
			$('.boxNdsBx').show();
		}
	});
	
	$('#tenders-pricefromfullprice').change(function() {
		if ($(this).prop("checked")) {
			$('#boxPriceItem').hide();
			$('#boxPriceAll').show();
		} else {
			$('#boxPriceItem').show();
			//$('#boxPriceAll').hide();
		}
	});
	
	$('#tenders-fullpricefromitemprice').change(function() {
		if ($(this).prop("checked")) {
			$('#boxPriceAll').hide();
			$('#boxPriceItem').show();
		} else {
			$('#boxPriceAll').show();
			//$('#boxPriceItem').hide();
		}
	});
	
	$('.tenders-notvat').each(function() {
		var prop = $(this).prop("checked");
		
		$('.tenders-notvat').prop("checked", prop);
		
		if (prop) {
			$('.field-tenders-priceunitvat .control-label:first').html('Цена');
			$('.field-tenders-pricefullunitvat .control-label:first').html('Цена');
			$('#boxPUV').hide();
			$('#boxFPU').hide();
		} else {
			$('.field-tenders-priceunitvat .control-label:first').html('Цена с НДС');
			$('.field-tenders-pricefullunitvat .control-label:first').html('Цена с НДС');
			$('#boxPUV').show();
			$('#boxFPU').show();
		}
	});
	
	$('.tenders-notvat').change(function() {
		var prop = $(this).prop("checked");
		
		$('.tenders-notvat').prop("checked", prop);
		
		if (prop) {
			$('.field-tenders-priceunitvat .control-label:first').html('Цена');
			$('.field-tenders-pricefullunitvat .control-label:first').html('Цена');
			$('#boxPUV').hide();
			$('#boxFPU').hide();
			$('.boxPriceNdsHint').hide();
		} else {
			$('.field-tenders-priceunitvat .control-label:first').html('Цена с НДС');
			$('.field-tenders-pricefullunitvat .control-label:first').html('Цена с НДС');
			$('#boxPUV').show();
			$('#boxFPU').show();
			$('.boxPriceNdsHint').show();
		}
	});
	
	$('#tenders-twosteps').change(function() {
		var prop = $(this).prop("checked");
		
		if (prop) {
			$('#boxDOU').show();
			$('#boxDOS').show();
		} else {
			$('#boxDOU').hide();
			$('#boxDOS').hide();
		}
	});
	
	$('#tenders-iscoincidesdateoffersstop').change(function() {
		var prop = $(this).prop("checked");
		
		if (prop) {
			$('#boxDEU').hide();
		} else {
			$('#boxDEU').show();
		}
	});

});


function fieldsValidate() {
	setTimeout(function() {
		// простые текстовые поля и текстареа
		var textFields = ['tenders-productdesc', 'tenders-conditionpayment', 'tenders-conditiondelivery', 'tenders-dateoffersstop'];
		
		$(textFields).each(function() {
			if ($('#boxIconStatus_'+this).length) {
				var element = $('#'+this);
				if (element.val() == '') {
					$('#boxIconStatus_'+this).html(tplIconNotFilled);
				} else {
					$('#boxIconStatus_'+this).html(tplIconFilled);
				}
			}
		});
		
		// значения с плавающей точкой
		var floatFields = ['tenders-productcount', 'tenders-stepvalue'];
		
		$(floatFields).each(function() {		
			if ($('#boxIconStatus_'+this).length) {
				var element = $('#'+this);
				if (	
					element.val() == '' ||
					!(parseFloat(element.val()) > 0)
				) {
					$('#boxIconStatus_'+this).html(tplIconNotFilled);
				} else {
					$('#boxIconStatus_'+this).html(tplIconFilled);
				}
			}
		});
		
		//классификатор
		if ($('#boxIconStatus_okpdsId').length) {
			if ($('#selectedOkpd li').length == 0) {
				$('#boxIconStatus_okpdsId').html(tplIconNotFilled);
			} else {
				$('#boxIconStatus_okpdsId').html(tplIconFilled);
			}
		}
		
		// адреса
		if ($('#boxIconStatus_addressIds').length) {
			if ($('#boxAddresses').find('input:checked').length == 0) {
				$('#boxIconStatus_addressIds').html(tplIconNotFilled);
			} else {
				$('#boxIconStatus_addressIds').html(tplIconFilled);
			}
		}

		// если не используем цену то все ок
		if ($('#tenders-isnotuseprice').prop('checked')) {
			$('#boxIconStatus_price').html(tplIconFilled);
			$('#boxIconStatus_priceAll').html(tplIconFilled);
		} else {
			// если цена расчитывается из общей, то все ок
			if ($('#tenders-pricefromfullprice').prop('checked')) {
				$('#boxIconStatus_price').html(tplIconFilled);
			} else {
				
				if ($('#tenders-notvat').prop('checked')) {
					if (
						$('#tenders-priceunitvat').val() == '' ||
						!(parseFloat($('#tenders-priceunitvat').val()) > 0)
					) {
						$('#boxIconStatus_price').html(tplIconNotFilled);
					} else {
						$('#boxIconStatus_price').html(tplIconFilled);
					}
				} else {
					if ((
						$('#tenders-priceunitwithoutvat').val() == '' || 
						!(parseFloat($('#tenders-priceunitwithoutvat').val()) > 0)
					) || (
						$('#tenders-priceunitvat').val() == '' ||
						!(parseFloat($('#tenders-priceunitvat').val()) > 0)
					)) {
						$('#boxIconStatus_price').html(tplIconNotFilled);
					} else {
						$('#boxIconStatus_price').html(tplIconFilled);
					}
				}
			} 

			if ($('#tenders-fullpricefromitemprice').prop('checked')) {
				$('#boxIconStatus_priceAll').html(tplIconFilled);
			} else {
				if ($('#tenders-notvat').prop('checked')) {
					if (
						$('#tenders-pricefullunitvat').val() == '' ||
						!(parseFloat($('#tenders-pricefullunitvat').val()) > 0)
					) {
						$('#boxIconStatus_priceAll').html(tplIconNotFilled);
					} else {
						$('#boxIconStatus_priceAll').html(tplIconFilled);
					}
				} else {
					if ((
						$('#tenders-pricefullunitwithoutvat').val() == '' || 
						!(parseFloat($('#tenders-pricefullunitwithoutvat').val()) > 0)
					) || (
						$('#tenders-pricefullunitvat').val() == '' ||
						!(parseFloat($('#tenders-pricefullunitvat').val()) > 0)
					)) {
						$('#boxIconStatus_priceAll').html(tplIconNotFilled);
					} else {
						$('#boxIconStatus_priceAll').html(tplIconFilled);
					}
				}
			}
		}
	}, 100);
}


function setDraft() {
	$.ajax({
		type:'POST',
		cache: false,
		url: '/ajax/set-draft',
		data: $("#w0").serialize(),
		success: function(response) {
			if (response) {
				$('#draftId').val(response);
				showMessage('Черновик сохранён', 'body', 'success');
			}
		}
	});
}


function addAddress() 
{

		var n = $('#address option[value="' + $('#address').val() + '"]').html();
		//console.log($('#address option:selected'), n);
		var html = ('<div><span>' + n + '</span><input type="hidden" name="addressIds[]" value="' + $('#address').val() 
					+ '" /> <a href="#" onclick="removeAddress(this);return false;">&times;</a>');
		
		$('.address-list').append(html);
}

function removeAddress(to)
{
	$(to).parent().remove();
	return false;
}

function showOkpdPopup()
{
	var c = getPopupCoords($('.okpd-popup'));
	
}

function refreshTitlteFeature() {
	if ($('#boxFeatures .feature-row').length == 0) {
		$('#titleFeature').hide();
	} else {
		$('#titleFeature').show();
	}
}

function deleteFeature(e) {
	$(e).closest('.feature-row	').remove();
	refreshTitlteFeature();	
}

function changeFeatureType(el) {
	var row = $(el).closest('.feature-row');
	
	if ($(el).val()==1) {
		row.find('.feature-type2').hide();
		row.find('.feature-type1').show();
	} else {
		row.find('.feature-type2').show();
		row.find('.feature-type1').hide();
	}
}

function changeFeatureValues(el) {
	var values = $(el).val().split("\n");
	var row = $(el).closest('.feature-row');
	var select = row.find('.feature-type2 .feature-default');
	
	select.html('');
	values = unique(values);
	
	$.each(values, function(i, val){
		if (val != '') {
			select.append($("<option></option>")
			.attr("value", val)
			.text(val)); 
		}
	});
}

function unique(arr) {
  var result = [];

  nextInput:
    for (var i = 0; i < arr.length; i++) {
      var str = arr[i]; // для каждого элемента
      for (var j = 0; j < result.length; j++) { // ищем, был ли он уже?
        if (result[j] == str) continue nextInput; // если да, то следующий
      }
      result.push(str);
    }

  return result;
}