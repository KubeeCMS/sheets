(function($) {
	"use strict";
	$(document).ready(function(){
		// Enable/Disable Header field input
		$('#woocommerce_spreadsheet').on('change', function() {
			var newrequest = $('#woocommerce_spreadsheet').val();
			if(newrequest == 'new'){
				$('#header_fields').prop('disabled', false);
				$("#productwise").removeClass('disabled');
				$("#orderwise").removeClass('disabled');
				$('.manage-row').prop('disabled', false);
				$('#selectall').show();
				$('#selectnone').show();
				$('#prdselectall').show();
				$('#prdselectnone').show();
				$('#newsheet').show();
				$('#synctr').hide();
				$('#view_spreadsheet').hide();
				$('#clear_spreadsheet').hide();
				$( "#sortable" ).sortable({
				  disabled: false
				});
				$(".headers_chk").attr("disabled", false);
				$('#woosheets-headers-notice').hide();
				$('#prdassheetheaders').prop('disabled', false);
				$( "#product-sortable" ).sortable({
				  disabled: false
				});
			}else{
				$('#header_fields').prop('disabled', 'disabled');
				$("#productwise").addClass('disabled');
				$("#orderwise").addClass('disabled');
				$('.manage-row').prop('disabled', 'disabled');
				$('#synctr').show();
				$('#selectall').hide();
				$('#selectnone').hide();
				$('#prdselectall').hide();
				$('#prdselectnone').hide();
				$('#newsheet').hide();
				$('#view_spreadsheet').show();
				$('#clear_spreadsheet').show();
				$( "#sortable" ).sortable({
				  disabled: true
				});
				$( "#product-sortable" ).sortable({
				  disabled: true
				});
				$('#woosheets-headers-notice').show();
				$('#prdassheetheaders').prop('disabled', true);
			}
		});
		$('input[type=radio][name=header_format]').on('change', function() {
			$('#sortable li').remove();
			var selectedopt = this.value;
			if( selectedopt == 'productwise'){
				$('#header_fields option').remove();	
					var prdwise = $('#prdwise').val();
					var productwise = prdwise.split(',');
					for(var p in productwise ) {
							var labelid =productwise[p].replace(/ /g,"_").toLowerCase();
							$("#sortable").append('<li class="ui-state-default ui-sortable-handle"><label for="'+labelid+'"><span class="ui-icon ui-icon-caret-2-n-s"></span><span class="wootextfield">'+productwise[p]+'</span><span class="ui-icon ui-icon-pencil"></span><input type="checkbox" name="header_fields_custom[]" value="'+productwise[p]+'" class="headers_chk1" hidden="true" checked><input type="checkbox" name="header_fields[]" id="'+labelid+'" class="headers_chk" value="'+productwise[p]+'" checked><span class="checkbox-switch-new"></span></label></li>');
						}
					$('.repeat_checkbox').show();
			}else if( selectedopt == 'orderwise' ){
				$('#header_fields option').remove();
				var ordwise = $('#ordwise').val();
				var orderwise = ordwise.split(',');
				for(var p in orderwise ) {
					var labelid =orderwise[p].replace(/ /g,"_").toLowerCase();
					$("#sortable").append('<li class="ui-state-default ui-sortable-handle"><label for="'+labelid+'"><span class="ui-icon ui-icon-caret-2-n-s"></span><span class="wootextfield">'+orderwise[p]+'</span><span class="ui-icon ui-icon-pencil"></span><input type="checkbox" name="header_fields_custom[]" value="'+orderwise[p]+'" class="headers_chk1" hidden="true" checked><input type="checkbox" name="header_fields[]" id="'+labelid+'" class="headers_chk" value="'+orderwise[p]+'" checked><span class="checkbox-switch-new"></span></label></li>');
				}
				$('.repeat_checkbox').hide();
			}
		});
		//Select all headers
		$("#selectall").on('click', function(){
			$(".headers_chk").prop('checked', true);
			$(".headers_chk1").prop('checked', true);
		});		
		//Select all headers
		$("#selectnone").on('click', function(){
			$(".headers_chk").prop('checked', false);
			$(".headers_chk1").prop('checked', false);
		});	
		//Select all headers
		$("#prdselectall").on('click', function(){
			$(".prdheaders_chk").prop('checked', true);
			$(".prdheaders_chk1").prop('checked', true);
		});	
			
		//Select all headers
		$("#prdselectnone").on('click', function(){
			$(".prdheaders_chk").prop('checked', false);
			$(".prdheaders_chk1").prop('checked', false);
		});	
		
		//Select all category
		$("#prdcatselectall").on('click', function(){
			$(".prdcatheaders_chk").prop('checked', true);
		});	
			
		
		$("#prdcatselectnone").on('click', function(){
			$(".prdcatheaders_chk").prop('checked', false);
		});	
		

		$(document).on('click','.ui-icon-pencil',function(e) {
			e.preventDefault();
			var $_this = $(this);
			var headername = $(this).siblings('.wootextfield').html();
			$(this).siblings('.wootextfield').html('<input type="text" style="width: 145px;" class="editheader" value="'+headername+'">');
			$(this).parent().find(".editheader").focus();
			$(this).parent().find(".editheader").val('');
			$(this).parent().find(".editheader").val(headername);
			$(this).parent().parent('li').addClass('custom');
			setTimeout(function(){ $_this.removeClass('ui-icon-pencil').addClass('ui-icon-check'); }, 10);
		});
		$(document).on('click','.ui-icon-check',function(e) {
			e.preventDefault();
			var $_this = $(this);
			var input = $(this).parent().find(".editheader").val();
			if( !$.trim(input) ){
				alert('Please enter Header Name');
				$(this).parent().find(".editheader").focus();
				return false;
			}
			$(this).siblings('.wootextfield').html(input);
			$(this).parent().parent('li').removeClass('custom');
			setTimeout(function(){ 
			var input = $_this.siblings("input").val();
			$_this.siblings('.wootextfield').html(input);
			$_this.removeClass('ui-icon-check').addClass('ui-icon-pencil'); }, 10);
		});
		$(document).on('focusout','.editheader',function(e) {
			e.preventDefault();
			if( !$.trim($(this).val())){
				return false;
			}
			var $_this = $(this);
			$(this).parent().parent().find('.headers_chk1').attr('value',$(this).val());
			$(this).parent().parent().find('.prdheaders_chk1').attr('value',$(this).val());
		});
		//Product Header
		$(document).on('click','.ui-icon-pencil-prd',function(e) {
			e.preventDefault();
			var $_this = $(this);
			var headername = $(this).siblings('.wootextfield').html();
			$(this).siblings('.wootextfield').html('<input type="text" style="width: 145px;" class="prdeditheader" value="'+headername+'">');
			$(this).parent().find(".prdeditheader").focus();
			$(this).parent().find(".prdeditheader").val('');
			$(this).parent().find(".prdeditheader").val(headername);
			$(this).parent().parent('li').addClass('custom');
			setTimeout(function(){ 
				$_this.removeClass('ui-icon-pencil').addClass('ui-icon-check'); 
				$_this.removeClass('ui-icon-pencil-prd').addClass('ui-icon-check-prd'); 
			}, 10);
		});
		$(document).on('click','.ui-icon-check-prd',function(e) {
			e.preventDefault();
			var $_this = $(this);
			var input = $(this).parent().find(".editheader").val();
			if( !$.trim(input) ){
				alert('Please enter Header Name');
				$(this).parent().find(".prdeditheader").focus();
				return false;
			}
			$(this).siblings('.wootextfield').html(input);
			$(this).parent().parent('li').removeClass('custom');
			setTimeout(function(){ 
			var input = $_this.siblings("input").val();
			$_this.siblings('.wootextfield').html(input);
			$_this.removeClass('ui-icon-check').addClass('ui-icon-pencil'); 
			$_this.removeClass('ui-icon-check-prd').addClass('ui-icon-pencil-prd'); 
			}, 10);
		});
		$(document).on('focusout','.prdeditheader',function(e) {
			e.preventDefault();
			if( !$.trim($(this).val())){
				return false;
			}
			var $_this = $(this);
			$(this).parent().parent().find('.prdheaders_chk1').attr('value',$(this).val());
		});
		
		$(document).on('change','.headers_chk',function(e) {
			if($(this).is(":checked")){
				$(this).siblings(':checkbox').attr('checked',true);
			}else if($(this).is(":not(:checked)")){
				$(this).siblings(':checkbox').attr('checked',false);
			}
		});
		
		$(document).on('change','.prdheaders_chk',function(e) {
			if($(this).is(":checked")){
				$(this).siblings(':checkbox').attr('checked',true);
			}else if($(this).is(":not(:checked)")){
				$(this).siblings(':checkbox').attr('checked',false);
			}
		});
		
		$('#category_select').on('change', function() {
			if(this.checked) {
				$('.td-prdcat-woosheets').fadeIn();
			}else{
				$('.td-prdcat-woosheets').fadeOut();	
			}
		});														
		// Validate newsheet name
		$('#mainform').on( 'submit', function(){
				var isFormValid = true;
				var newrequest = $('#woocommerce_spreadsheet').val();
				if( newrequest == '' ){
					alert('Please Select Spreadsheet.');
					$('html, body').animate({
						scrollTop:0
					}, 1200);	
					$( '#woocommerce_spreadsheet' ).focus();
					return false;
				}
				
				if($('.woosheets-section-2').find('input[type=checkbox]:checked').length == 0){
					alert('Please select at least one order status to get it work in spreadsheet');
					$('html, body').animate({
						scrollTop:$(".woosheets-section-2").first().offset().top - 140
					}, 1200);
					setTimeout(function(){ $(".woosheets-section-2").first().css("border", "1px solid #ff5859"); }, 1000);
					$( '.woosheets-section-2' ).first().focus();
					return false;	
				}
				
				if($('#sortable').find('input[type=checkbox]:checked').length == 0){
					alert('Please select at least one sheet headers to get it work in spreadsheet');
					$('html, body').animate({
						scrollTop:$("#sortable").offset().top - 140
					}, 1200);
					setTimeout(function(){ $("#sortable").css("border", "1px solid #ff5859"); }, 1000);
					$( '#sortable' ).focus();
					return false;
				}

				
				
				if(newrequest == 'new'){
					if ($('#spreadsheetname').val().length == 0){
						$('#newsheet').addClass('highlight');
						isFormValid = false;
					}
					else{
						$(this).removeClass('highlight');
					}
				
				if (!isFormValid){
					 alert('Please enter Spreadsheet Name');
					 $('html, body').animate({
						scrollTop:0
					}, 1200);
					$( '#spreadsheetname' ).focus();
				}
				return isFormValid;
			}
		});
	});
	//Synchronize all order details
	$(document).ready(function(){
		$("#sync").on('click', function(e){  
			$( '#syncloader' ).show();
			$( '#synctext' ).show();
			$(this).hide();
			$.ajax({
				url : admin_ajax_object.ajaxurl,
				type : 'post',
				data :"action=sync_all_orders",
				success : function( response ) {
					if(response =='successful'){
						alert('All Orders are synchronize successfully');
						$( '#syncloader' ).hide();
						$( '#synctext' ).hide();
						 document.getElementById("sync").style.display="inline-block";
					}else{
						alert('Your Google Sheets API limit has been reached. Please take a look at our FAQ.');
						$( '#syncloader' ).hide();
						$( '#synctext' ).hide();
						document.getElementById("sync").style.display="inline-block";	
					}
				}
				
			})
			.fail(function() {
				alert('Error');
				$( '#syncloader' ).hide();
				$( '#synctext' ).hide();
				document.getElementById("sync").style.display="inline-block";
			  });
		});
		$(document).on('click',"#clear_spreadsheet",function(e) {
			e.preventDefault();
			$.ajax({
				url : admin_ajax_object.ajaxurl,
				type : 'post',
				data :"action=clear_all_sheet",
				beforeSend:function(){
		         	if(confirm("Are you sure?")){
            			$( '#clearloader' ).attr('src',$( '#syncloader' ).attr('src'));
						$( '#clearloader' ).show();            
                    } else { 
                        return false;
                    }
		      	},
				success : function( response ) {
					if(response =='successful'){
						alert('Spreadsheet Cleared successfully');
						$( '#clearloader' ).hide();
					}
				},
				error: function (s) {
			    	alert('Error');
					$( '#clearloader' ).hide();  
			    }

			});
		});
	});	
	//Check for existing sheets
	$(document).ready(function(){
		$('#synctr').hide();
		$('#woosheets-headers-notice').hide();
		var prevsheetid = $('#woocommerce_spreadsheet').val();
		var rowdata = $('input[type=radio][name=header_format]:checked').val();
		if( rowdata == 'productwise'){
			$('.repeat_checkbox').show();
		}else{
			$('.repeat_checkbox').hide();	
		}
		if( prevsheetid != '' ){
			$('#synctr').show();
			$('#woosheets-headers-notice').show();
		}
		$("#woocommerce_spreadsheet").on('change', function() {
			var sheetid = $(this).val();
			if(sheetid == prevsheetid)
				return true;
			
			if (sheetid != null && sheetid != '' && sheetid != 'new'){
				$.ajax({
					url : admin_ajax_object.ajaxurl,
					type : 'post',
					data :{action:'check_existing_sheet',id:sheetid },
					success : function( response ) {
						if(response =='successful'){
							alert('Selected spreadsheet will be mismatch match your order data with respect to the sheet headers so please create new spreadsheet or select different spreadsheet.');
							$('#woocommerce_spreadsheet').val(prevsheetid);
						}else{	
							$("#header_fields").prop('disabled', false);
							$("#productwise").removeClass('disabled');
							$("#orderwise").removeClass('disabled');
							$("#synctr").css('display', 'none');
						}
					}
				});
			}
		});
	});	
	$(document).ready(function(){
		$("#authlink").on('click', function(e){ 
			$( '#authbtn' ).hide();
			document.getElementById("authtext").style.display="inline-block";				
		});
		$("#revoke").on('click', function(e){ 
			document.getElementById("authtext").style.display="none";
			document.getElementById("client_token").style.display="none";				
		});
	});
	$(document).ready(function(){
		var activetab = getParameterByName('tab');
		if( activetab != null){
			woosheetstab(event, activetab);
			var classnm = "button."+activetab;	
			$(classnm).addClass('active');
		}else{
			var classnm = "button.googleapi-settings";	
			$(classnm).addClass('active');
		}
	});
	$(window).load(function() {
		$( "#sortable" ).sortable({
		  disabled: false
		});
		$( "#product-sortable" ).sortable({
		  disabled: false
		});
		if ($("#prdassheetheaders").is(':checked')){
			$('.td-prd-woosheets-headers').show();	
		}else{
			$('.td-prd-woosheets-headers').hide();	
		}
		var i = 1;
		var tblcount = $("#mainform > table").length;
		$( "#mainform table" ).each(function() {
		  if( tblcount == i){
				$( this ).addClass( "woosheets-section-last" ); 	
		  }else{
			  if( tblcount > 4 && i == 3 ){
				$( this ).addClass( "woosheets-section-2" ); 
			  }
			  else{
				 $( this ).addClass( "woosheets-section-"+i ); 
			  }
		  }
		  i++;
		});
		$( ".woosheets-section-2 label input[type='checkbox'],.woosheets-section-last label input[type='checkbox']" ).after( "<span class='checkbox-switch'></span>" );
	});
	
	$( '#licence_submit' ).on( 'click', function (e) {
		e.preventDefault();
		$( '.woosheets-license-result' ).html( '' );
		$( '#licence_submit' ).hide();
		$( '#licenceloader' ).show();
		$( '#licencetext' ).show();
		woosheets_license_check( 'activate' ); 
	} );
	
	$( '.tm-deactivate-license' ).on( 'click', function ( e ) {
		e.preventDefault();
		woosheets_license_check( 'deactivate' );
	} );
	
	$( '#add_ctm_val' ).on( 'click', function ( e ) {
		e.preventDefault();
		var cst_val = $("#custom_headers_val").val();
		if( cst_val == '' ){
			alert('Please enter header name.');
			$( '#custom_headers_val' ).focus();
			return false;
		}
		var labelid = cst_val.replace(/ /g,"_").toLowerCase();
		$("#sortable").append('<li class="ui-state-default ui-sortable-handle"><label for="'+labelid+'"><span class="ui-icon ui-icon-caret-2-n-s"></span><span class="wootextfield">'+cst_val+'</span><span class="ui-icon ui-icon-pencil"></span><input type="checkbox" name="header_fields_custom[]" value="'+cst_val+'" class="headers_chk1" hidden="true" checked><input type="checkbox" name="header_fields[]" id="'+labelid+'" class="headers_chk" value="'+cst_val+'" checked><span class="checkbox-switch-new"></span></label></li>');
		$( '#custom_headers_val' ).val('');
	} );
	
	$(document).ready(function(){
		$('.custom-input-div').hide();
		$('#custom_header_action').on('change', function() {
			if(this.checked) {
				$('.custom-input-div').fadeIn();
			}else{
				$('.custom-input-div').fadeOut();	
			}
		});
		$('#prdassheetheaders').on('change', function() {
			if(this.checked) {
				$('.td-prd-woosheets-headers').fadeIn();
			}else{
				$('.td-prd-woosheets-headers').fadeOut();	
			}
		});
	});
	$(document).ready(function(){
		$( '#expsyncloader' ).hide();
		$( '#expsynctext' ).hide();
		$('#spreadsheet_url').hide();
		$('#exportform').on( 'submit', function(){
			$('#spreadsheet_url').hide();
			var ordfromdate = $('#ordfromdate').val();
			var ordtodate = $('#ordtodate').val();
			var spreadsheetname = $('#expspreadsheetname').val();
			
			var chkArray = [];
	
			/* look for all checkboes that have a class 'chk' attached to it and check if it was checked */
			$(".prdcatheaders_chk:checked").each(function() {
				chkArray.push($(this).val());
			});
								
			if(ordfromdate > ordtodate){
				alert('From Date should not be greater than To Date.');
			}else{
				$( '#expsyncloader' ).show();
				$( '#expsynctext' ).show();
				$('#exportsubmit').attr('disabled',true);
				if ($('#exportall').is(":checked")){
					var exportall = 'yes';
				}else{
					 var exportall = 'no';
				}
				if ($('#category_select').is(":checked")){
					var category_select = 'yes';
				}else{
					 var category_select = 'no';
				}
				$.ajax({
					url : admin_ajax_object.ajaxurl,
					type : 'post',
					data :"action=woosheets_export_order",
					data :{action:"woosheets_export_order",from_date:ordfromdate,to_date:ordtodate,spreadsheetname:spreadsheetname,exportall:exportall,category_select:category_select,category_ids:chkArray },
					success : function( response ) {
						var res = $.parseJSON(response);
						if(res.result =='successful'){
							
							var sheetid = 'https://docs.google.com/spreadsheets/d/'+res.spreadsheetid;
							var xlsxurl = "https://docs.google.com/spreadsheets/u/0/d/"+res.spreadsheetid+"/export?exportFormat=xlsx";
							$('#spreadsheet_url').attr("href", sheetid);
							$('#spreadsheet_xslxurl').attr("href", xlsxurl);
							$( '#expsyncloader' ).hide();
							$( '#expsynctext' ).hide();
							$( '#exportsubmit' ).attr('disabled',false);
							alert('Export All Orders Successfully');
							$('#spreadsheet_url').show();
							$('#spreadsheet_xslxurl').show();
							$('#spreadsheet_csvurl').show();
						}else{
							$( '#expsyncloader' ).hide();
							$( '#expsynctext' ).hide();
							$( '#exportsubmit').attr('disabled',false);	
							alert('Your Google Sheets API limit has been reached. Please take a look at our FAQ.');
						}
					}
				})
				.fail(function() {
					$( '#expsyncloader' ).hide();
					$( '#expsynctext' ).hide();
					$( '#exportsubmit' ).attr('disabled',false);
					alert('Error');
				  });		
			}
			return false;
		});
		$('#exportall').on('change', function() {
			if(this.checked) {
				$( '#ordtodate' ).attr('disabled',true);
				$( '#ordfromdate' ).attr('disabled',true);
			}else{
				$( '#ordtodate' ).attr('disabled',false);
				$( '#ordfromdate' ).attr('disabled',false);	
			}
		});
	});
	$(document).ready(function(){
		var newrequest = $('#woocommerce_spreadsheet').val();
			if(newrequest != 'new' && newrequest != '' ){
				var slink = '<a id="view_spreadsheet" target="_blank" href="https://docs.google.com/spreadsheets/d/'+newrequest+'" class="woosheets-button">View Spreadsheet</a> <a id="clear_spreadsheet" href="" class="woosheets-button">Clear Spreadsheet</a>   <img src="" id="clearloader">';
				$( "#woocommerce_spreadsheet" ).after( slink );
			}
	});
})(jQuery);

function woosheetstab(evt, tabName) {
"use strict";
  var i, tabcontent, tablinks;
  tabcontent = document.getElementsByClassName("tabcontent");
  for (i = 0; i < tabcontent.length; i++) {
    tabcontent[i].style.display = "none";
  }
  tablinks = document.getElementsByClassName("tablinks");
  for (i = 0; i < tablinks.length; i++) {
    tablinks[i].className = tablinks[i].className.replace(" active", "");
  }
  document.getElementById(tabName).style.display = "block";
  evt.currentTarget.className += " active";
}
function getParameterByName(name, url) {
	"use strict";
    if (!url) url = window.location.href;
    name = name.replace(/[\[\]]/g, '\\jQuery&');
    var regex = new RegExp('[?&]' + name + '(=([^&#]*)|&|#|jQuery)'),
        results = regex.exec(url);
    if (!results) return null;
    if (!results[2]) return '';
    return decodeURIComponent(results[2].replace(/\+/g, ' '));
}
function woosheets_license_check( action ) {
	"use strict";
		if( jQuery( '#ws_envato' ).val() == '' ){
			jQuery( '.woosheets-license-result' ).html( '<div class="error"><p>Please enter Envato API Token</p></div>' );
			jQuery( '#licenceloader' ).hide();
			jQuery( '#licencetext' ).hide();
			jQuery( '#licence_submit' ).show();
			return false;		
		}
		var data = {
			action: 'woosheets_' + action + '_license',
			username: jQuery( '#ws_username' ).val(),
			key: jQuery( '#ws_purchase' ).val(),
			api_key: jQuery( '#ws_envato' ).val(),
			agree_transmit: jQuery( '#agree_transmit:checked' ).val(),
			wpnonce	: jQuery( '#_wpnonce' ).val()
		};
		jQuery.post( admin_ajax_object.ajaxurl, data, function ( response ) {
			var html;
			if ( ! response || response == - 1 ) {
				html = '<div class="error"><p>Please enter valid Envato API Token</p></div>';
			} else if ( response && response.message && response.result
				&& (response.result == '-3' || response.result == '-2'
				|| response.result == 'wp_error' || response.result == 'server_error') ) {
				html = response.message;
			} else if ( response && response.message && response.result && (response.result == '4') ) {
				html = response.message;
			} else {
				html = '';
			}
			jQuery( '.woosheets-license-result' ).html( html );
			jQuery( '#licenceloader' ).hide();
			jQuery( '#licencetext' ).hide();
			jQuery( '#licence_submit' ).show();
			}, 'json' )
			.always( function ( response ) {});
}
function woosheets_copy( id, targetid ) {
    var copyText = document.getElementById(id);
    var textArea = document.createElement("textarea");
	textArea.value = copyText.textContent;
	document.body.appendChild(textArea);
	textArea.select();
	document.execCommand("Copy");
	textArea.remove();
}