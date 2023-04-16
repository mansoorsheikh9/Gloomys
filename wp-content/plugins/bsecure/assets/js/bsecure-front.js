jQuery(function(){ 

	if(document.location.hash == '#bsecure-auto-checkout'){		

			jQuery("body").addClass("bsecure-popup-handle-loader");
			jQuery(".bsecure-popup-loader").show();  		
		
	}

	jQuery(document).on('click', '.bsecure-checkout-button', function(){		

		if(bsecure_js_object.wc_is_hosted_checkout !== 'no' && isWebView() ){			
			localStorage.setItem("isFastCheckoutClicked",  0);
			openBsecureWindow(bsecure_js_object.wc_cart_url+"?bsecure_hosted#bsecure-auto-checkout");			
		}

		var btn = jQuery(this);
		var btnDefaultText = btn.data("btn-text");
		var msgArea = jQuery(".woocommerce-notices-wrapper");
		var nonce = bsecure_js_object.nonce;
		block(jQuery(".woocommerce-cart-form, div.cart_totals, .widget_shopping_cart_content, body.woocommerce-checkout"));	

		jQuery.post(bsecure_js_object.ajax_url,{"action":"ajax_order_to_bsecure","wp_nonce":nonce},function(res){ 
		},"json").done(function(res) {
		
			if(res.status){

				if(res.redirect){	
					
					if(bsecure_js_object.wc_is_hosted_checkout !== 'no' && isWebView()){				

						bsecure_js_object.bsecureWindow.location.href = res.redirect;

					}else{

						document.location.href = res.redirect;
					}

					if(bsecure_js_object.wc_bsecure_reminder_popup == 'yes'){

						setReminderPopupCheck(res.order_reference, res.redirect, 0);
					}
													
					
				}else{

					closeBsecureWindow();				
					displayWcMessage(msgArea, res.msg, 'error');
					scrollToMessageArea();				
					
				}
				
			}else{

				closeBsecureWindow();			
				displayWcMessage(msgArea, res.msg, 'error');
				scrollToMessageArea()

				if(res.redirect === 'reload'){
					
					setTimeout(function() { location.reload(); }, 2000);
				}
			
			}
		})
		.fail(function(res) {

			closeBsecureWindow();			
			displayWcMessage(msgArea, 'An error occurred while sending your request. Please try again.', 'error');		
			scrollToMessageArea();
		})
		.always(function(res) {
			
			unblock(jQuery(".woocommerce-cart-form, div.cart_totals, .widget_shopping_cart_content, body.woocommerce-checkout"));
				
		});

	

	});

	// Override Proceed to checkout button //
	if(typeof bsecure_js_object.wc_bsecure_is_active !== 'undefined'){

		var bsecure_is_active = bsecure_js_object.wc_bsecure_is_active;
		var show_checkout_btn = bsecure_js_object.wc_show_checkout_btn;
		var bsecure_checkout_btn_url = bsecure_js_object.wc_bsecure_checkout_btn_url;
		var bsecure_title = bsecure_js_object.wc_bsecure_title;
		//var buyer_protection_enabled = bsecure_js_object.buyer_protection_enabled;
		//var buyer_protection_tooltip_text = buyer_protection_enabled == 1 ? '<span class="tooltiptext">'+bsecure_js_object.buyer_protection_tooltip_text+'</span>' : "";
		var wc_class_proceed_to_checkout = jQuery('.woocommerce .wc-proceed-to-checkout');
		var bsecure_checkout_btn = '';

		if(bsecure_is_active == 'yes'){
			if(show_checkout_btn != 'bsecure_wc_only'){
				if(wc_class_proceed_to_checkout.find(".bsecure-checkout-button").length < 1){

					bsecure_checkout_btn = '<p ><a href="javascript:;" class="bsecure-checkout-button" data-btn-text="bSecure Checkout" title="" style="outline-color: transparent;outline: none;"><span class="bsecure-loader-outer"><span class="bsecure-loader-span"></span><div class="bsecure-tooltip"><img src="'+bsecure_checkout_btn_url+'" alt="'+bsecure_title+'"></div></span></a></p>';
					wc_class_proceed_to_checkout.prepend(bsecure_checkout_btn);


					// WC Callback after cart update //
					jQuery( document.body ).on( 'updated_cart_totals', function(){

					    jQuery('.woocommerce .wc-proceed-to-checkout').prepend(bsecure_checkout_btn);		
					    handleBsecureCheckoutBtnCart(bsecure_checkout_btn);
					    
					});

					// On Page Refresh
					handleBsecureCheckoutBtnCart(bsecure_checkout_btn);

				}		
			}				

		}

	}
	
});

jQuery(document).on("click", "button#place_order", function(e){
	
	var msgArea = jQuery(".woocommerce-notices-wrapper");
	var loadingSelectors = jQuery("body.woocommerce-checkout, .woocommerce #payment");
	var place_order_btn = jQuery(this);

    if(jQuery("#payment_method_bsecures").is(":checked")){

    	place_order_btn.attr("disabled", "disabled");
    	e.preventDefault();
    	block(loadingSelectors);
    	if(bsecure_js_object.wc_is_hosted_checkout !== 'no' && isWebView()){

    		//localStorage.setItem("isFastCheckoutClicked",  0);
				openBsecureWindow(bsecure_js_object.wc_checkout_url+"?bsecure_hosted#bsecure-auto-checkout");
			}
			

    	jQuery.ajax({
			url: bsecure_js_object.site_url+'/?wc-ajax=checkout',
			type: "POST",
			data: jQuery(".woocommerce-checkout").serialize(),
			success: function(res) {						
				
				if(res.result == 'success' && typeof res.redirect_bsecure !== 'undefined' ){					

					var cnt = 0;
					if(bsecure_js_object.wc_is_hosted_checkout !== 'no' && isWebView()){

						bsecure_js_object.bsecureWindow.location.href = res.redirect_bsecure;
						
					}else{

						document.location.href = res.redirect_bsecure;
					}

					if(bsecure_js_object.wc_bsecure_reminder_popup == 'yes'){						
						//setReminderPopupCheck(res.order_reference, res.redirect, 0);
					}										
										
				}else{
					
					unblock(loadingSelectors);
					place_order_btn.removeAttr("disabled");					
					closeBsecureWindow();
					printErrorMessageAtCheckout(res.messages);
				}				
				
			},
			error: function() {
				place_order_btn.removeAttr("disabled");
				unblock(loadingSelectors);
				scrollToMessageArea();
				closeBsecureWindow();
				displayWcMessage(msgArea, 'An error occurred while sending your request. Please try again.', 'error');
		    }
		}); 
        
    }    
});


function displayWcMessage(msgArea, msg, msgType){

	if(msgArea.length > 0){

		msgArea.html('<div class="woocommerce-'+ msgType +'" role="alert">'+msg+'</div>');

	}else{
		

		if(jQuery('.bsecure-custom-msg-area').length > 0){

			jQuery('.bsecure-custom-msg-area').remove();

		}

		if(jQuery("#content").length == 1){

			jQuery("#content").prepend('<div class="woocommerce-'+ msgType +' bsecure-custom-msg-area" role="alert">'+msg+'</div>');				

		}else if(jQuery("#page").length == 1){

			jQuery("#page").prepend('<div class="woocommerce-'+ msgType +' bsecure-custom-msg-area" role="alert">'+msg+'</div>');

		}			

	}

}


function handleBsecureCheckoutBtnCart(bsecure_checkout_btn){

	var show_checkout_btn = bsecure_js_object.wc_show_checkout_btn;
	var wc_class_proceed_to_checkout = jQuery('.woocommerce .wc-proceed-to-checkout');

	if(show_checkout_btn != 'bsecure_wc_both' && show_checkout_btn != 'bsecure_wc_only'){

		if(wc_class_proceed_to_checkout.find(".checkout-button").length > 0 && wc_class_proceed_to_checkout.find(".bsecure-checkout-button").length > 0){

			wc_class_proceed_to_checkout.find(".checkout-button").remove();

		}else{

            wc_class_proceed_to_checkout.html(bsecure_checkout_btn);

        }
	}

}

function openBsecureWindow(url) {

		jQuery(".bsecure-popup-overlay").show();
		var h = 700;
		var w = 400;
		var left = (screen.width/2)-(w/2);
	  	var top = (screen.height/2)-(h/2);  	

	  	bsecure_js_object.bsecureWindow = window.open(url, "_blank", 'toolbar=no,scrollbars=yes,resizable=yes,width='+w+', height='+h+', top='+top+', left='+left);

	  	var timer = setInterval(function() { 
			    if(!bsecure_js_object.bsecureWindow || bsecure_js_object.bsecureWindow.closed || typeof bsecure_js_object.bsecureWindow.closed == 'undefined') {
			        clearInterval(timer);
			        jQuery(".bsecure-popup-overlay").hide();
			        localStorage.setItem("isFastCheckoutClicked",  1);
			        clearLoaders();
			        loadReminderPopup();
			    }

			}, 500);



	try {
		   
		   bsecure_js_object.bsecureWindow.focus();
	  	 

  } catch(e){
  	//url = url.replace("?bsecure_hosted#bsecure-auto-checkout","");  	
  	//document.location.href = url;
  }
  	
}


function closeBsecureWindow(){
	
	if( bsecure_js_object.bsecureWindow !== ''){
		bsecure_js_object.bsecureWindow.close();
		jQuery(".bsecure-popup-overlay").hide();
    }
	
}


// Receive message from bsecure server //
window.addEventListener("message", (event)=>{

    if (event.origin == "https://order-dev.bsecure.app" || 
    	event.origin == "https://checkout-stage.bsecure.app" || 
    	event.origin == "https://order.bsecure.pk" || 
    	event.origin == "https://login-dev.bsecure.app" || 
    	event.origin == "https://login-stage.bsecure.app" || 
    	event.origin == "https://login.bsecure.pk" ||
    	event.origin == "https://order-stage-v2.bsecure.app" ||
    	event.origin == "https://order-dev-v2.bsecure.app" ||
    	event.origin == "https://fast.bsecure.pk"
    	){
        	
	   	if(typeof event.data.hrf !== 'undefined'){

	   		if(typeof bsecure_js_object.bsecureWindow !== 'undefined'){
	    		bsecure_js_object.bsecureWindow.close();
	    	}

	    	if(jQuery(".woocommerce-cart-form").length > 0 || 
	    		jQuery("div.cart_totals").length > 0  ){

	    		block(jQuery(".woocommerce-cart-form, div.cart_totals, .widget_shopping_cart_content"));

	    	} else if(jQuery("form.checkout.woocommerce-checkout").length > 0) {

	   			block(jQuery("form.checkout.woocommerce-checkout"));

	   		} else {

	   			block(jQuery("body"));

	   		}
	   		
	   		window.location.href=event.data.hrf;
	   }else{
	   	//child window closed directly
	   	
	   }


    }

    return; 
	  
}); 

function focusBsecureWindow() {  	
  	bsecure_js_object.bsecureWindow.focus();
}


jQuery(document).on('click', '.bsecure-popup-overlay', function(){

	if(bsecure_js_object.bsecureWindow.closed) {        
        jQuery(".bsecure-popup-overlay").hide();
    }

});

function scrollToMessageArea(){

	if(jQuery("#content").length == 1){

		jQuery('html, body').animate({
	        scrollTop: jQuery("#content").offset().top
	    }, 1000);

	}else if(jQuery("#page").length == 1){

		jQuery('html, body').animate({
	        scrollTop: jQuery("#page").offset().top
	    }, 1000);

	}else if(jQuery(".woocommerce").length == 1){

		jQuery('html, body').animate({
	        scrollTop: jQuery(".woocommerce").offset().top - 200
	    }, 1000);

	}
	
}

// -------- This code taken from woocommerce/assests/js/frontend/cart.js -------- //

var is_blocked = function( $node ) {
	return $node.is( '.processing' ) || $node.parents( '.processing' ).length;
};

/**
 * Block a node visually for processing.
 *
 * @param {JQuery Object} $node
 */
var block = function( $node ) {

	if($node.length < 1){
		jQuery(".bsecure-loader-span").addClass("bsecure-ajax-loader");
	}else{

		if ( ! is_blocked( $node ) ) {
			if (jQuery.isFunction(jQuery.fn.block) ) {
			    $node.addClass( 'processing' ).block( {
					message: null,
					overlayCSS: {
						background: '#fff',
						opacity: 0.6
					}
				} );
			}else{

				jQuery(".bsecure-loader-span").addClass("bsecure-ajax-loader");
			}			
		}
	}

	
};

/**
 * Unblock a node after processing is complete.
 *
 * @param {JQuery Object} $node
 */
var unblock = function( $node ) {

	if($node.length < 1){
		jQuery(".bsecure-loader-span").removeClass("bsecure-ajax-loader");
	}else{

		if (jQuery.isFunction(jQuery.fn.unblock)) {
			$node.removeClass( 'processing' ).unblock();
		}else{

			jQuery(".bsecure-loader-span").removeClass("bsecure-ajax-loader");
		}
	}
};

// ----------------------------------------------------------- //

function loadMiniCartBtn(){

	if(bsecure_js_object.wc_bsecure_is_active == 'yes' && bsecure_js_object.wc_show_checkout_btn == 'bsecure_only') {

		setTimeout(function(){
			var bsecure_checkout_btn = '<p class="bsecure-checkout-mini-cart-widget"><a href="javascript:;" class="bsecure-checkout-button" data-btn-text="'+ bsecure_js_object.wc_bsecure_title +'" title="'+ bsecure_js_object.wc_bsecure_title +'" style="outline-color: transparent;outline: none;"><span class="bsecure-loader-outer"><span class="bsecure-loader-span"></span><img src="'+ bsecure_js_object.wc_bsecure_checkout_btn_url +'" alt="'+ bsecure_js_object.wc_bsecure_title +'" class="wc-bsecure-checkout-btn-mini-cart"></span></a></p>';
		    jQuery("p.woocommerce-mini-cart__buttons.buttons a.checkout.wc-forward").remove();
		    //jQuery("p.woocommerce-mini-cart__buttons.buttons a.wc-forward").after(bsecure_checkout_btn);
		}, 1500);
	}
}


function printErrorMessageAtCheckout(error_message){

	jQuery( '.woocommerce-NoticeGroup-checkout, .woocommerce-error, .woocommerce-message' ).remove();

	var $checkout_form = jQuery("form.checkout");

	$checkout_form.prepend( '<div class="woocommerce-NoticeGroup woocommerce-NoticeGroup-checkout">' + error_message + '</div>' );

	$checkout_form.removeClass( 'processing' ).unblock();
	$checkout_form.find( '.input-text, select, input:checkbox' ).trigger( 'validate' ).blur();

	jQuery('html, body').animate({
        scrollTop: ($checkout_form.offset().top  - 200 )
    }, 1000);
    jQuery( document.body ).trigger( 'checkout_error' , [ error_message ] );
	
}



(function($){

	$( 'form.checkout' ).on( 'change', 'input[name^="payment_method"]', function() {
	$('body').trigger('update_checkout');
	});

	if($("#country_calling_code_field").length > 0){
		var callingCodeHtml = $("#country_calling_code_field").html();
		$("#billing_phone_field .woocommerce-input-wrapper").prepend(callingCodeHtml);
		$("#billing_phone_field").addClass("billing_phone_wrapper");
		$("#country_calling_code_field").remove();
		$("#country_calling_code").select2();
	}
})(jQuery);

jQuery(document).on("click", "#btn-bsecure-new", function(e){
    e.preventDefault();
    var loginUrl = jQuery(this).attr("href");
    if(typeof bsecure_js_object !== 'undefined'){
    	if( bsecure_js_object.wc_is_hosted_checkout !== 'no' && isWebView()){
        	openBsecureWindow(loginUrl+"?bsecure_hosted#bsecure-auto-checkout");
	    }else{
	        document.location.href = loginUrl;
	    }
	}else{

		document.location.href = loginUrl;
	}
});


// ------------------------ Start Qisstpay Popup code ---------------------//

// Get the modal
var modal = document.getElementById("qisstpay-modal");

// Get the button that opens the modal
var btn = document.getElementById("qisstpay-modal-btn");

// Get the <span> element that closes the modal
var span = document.getElementsByClassName("bsecure-modal-close")[0];

// When the user clicks the button, open the modal 
jQuery(document).on('click', '#qisstpay-modal-btn', function(){
	block(jQuery("body"));
	//ajax_load_qisstpay("block");
});

// handle qistpay amount when ajax cart updated
jQuery(document.body).on('removed_from_cart updated_cart_totals', function () {
	localStorage.setItem("isFastCheckoutClicked",  0);
	//jQuery("#bsecure-reminder-modal").hide();
	//ajax_load_qisstpay("none");
});

// handle qistpay amount when ajax add to cart submitted
jQuery(document).on( 'added_to_cart', function(){
	localStorage.setItem("isFastCheckoutClicked",  0);
	//jQuery("#bsecure-reminder-modal").hide();
	//ajax_load_qisstpay("none");
});

// When the user clicks on <span> (x), close the modal
jQuery(document).on('click', '#qisstpay-modal .bsecure-modal-close', function(){
  modal.style.display = "none";
});

// When the user clicks anywhere outside of the modal, close it
window.onclick = function(event) {
  if (event.target == modal) {
    modal.style.display = "none";
  }
}

// Load cart amount via ajax for Qisstpay popup
function ajax_load_qisstpay(displayPopup){
	
	var qisstpayNumOfMonths = jQuery("#qisstpay-modal #qisstpayNumOfMonths").val();

	if(bsecure_js_object.bsecure_show_qisstpay == 'yes'){		

		jQuery.post(
			bsecure_js_object.ajax_url,
			{"action":"ajax_load_qisstpay_popup","wp_nonce":bsecure_js_object.nonce,"qisstpayNumOfMonths":qisstpayNumOfMonths},
			function(res){},"json").done(function(res) {
			
				if(res.success){
					
					jQuery("#qisstpay-modal .editablePrice").val(res.data.cart_amount); 										
					jQuery("#qisstpay-modal .editablePrice").attr("data-numeric-amount",res.data.cart_amount.replace(/,/g,"")); 										
					jQuery("#qisstpay-modal .disstpay-popup-monthly-amount").text(res.data.monthly_amount);

					jQuery(".bsecure-qisstpay-wrapper .firstWrapper .qisstpay-price-text").html(res.data.monthly_amount_formated);	
					
							jQuery("#qisstpay-modal .tabBox .selectedTab").trigger("click");
						
					
								
					
					modal.style.display = displayPopup; 
										
				} else {

					//jQuery("#qisstpay-modal .bsecure-modal-body").html('<p class="modalBigHeading">'+ res.data +'</p>');
					jQuery(".bsecure-qisstpay-wrapper").remove();
				}

			})
			.fail(function(res) {
				
		    })
		    .always(function(res) {
		    	unblock(jQuery("body"));	    	
		    })
	}
}

// Handle monthly feature at QisstPay popup
jQuery(document).on("click", "#qisstpay-modal .tabBox a", function(e){
		e.preventDefault();
	
		if(jQuery(this).hasClass("tab1")){
			jQuery("#qisstpay-modal .tabBox a.tab2").removeClass("selectedTab");
		}else{
			jQuery("#qisstpay-modal .tabBox a.tab1").removeClass("selectedTab");
		}
    
    jQuery(this).addClass("selectedTab");
    var cartVal = jQuery("#qisstpay-modal .editablePrice").attr("data-numeric-amount");
    var numOfMonths = parseInt(jQuery(this).attr("data-months"));
   
    jQuery("#qisstpayNumOfMonths").val(numOfMonths);
	if(cartVal > 0){
	    var perMonthtVal = cartVal/numOfMonths;
	    perMonthtVal = parseFloat(perMonthtVal.toFixed(2));
	    jQuery("#qisstpay-modal .disstpay-popup-monthly-amount").text(perMonthtVal.toLocaleString());
	    var uptoText = numOfMonths == 12 ? "Upto " : "";
	    jQuery("#qisstpay-modal .noOfMonth").text(uptoText+numOfMonths+" months");

	}

});
// ------------------------ End Qisstpay Popup code ---------------------//




// ------------------------ Start Reminder Popup code ---------------------//

var reminder_timer = 10000;
// Get the modal
var reminder_modal = document.getElementById("bsecure-reminder-modal");

// Get the button that opens the modal
var btn = document.getElementById("bsecure-reminder-modal-btn");

// Get the <span> element that closes the modal
var span = document.getElementsByClassName("bsecure-modal-close")[0];

// When the user clicks the button, open the modal 
jQuery(document).on('click', '#bsecure-reminder-modal-btn', function(){
	block(jQuery("body"));
	//ajax_load_qisstpay("block");
});


// When the user clicks on <span> (x), close the modal
jQuery(document).on('click', '#bsecure-reminder-modal .bsecure-modal-close', function(){
	
  reminder_modal.style.display = "none";
  localStorage.setItem("isFastCheckoutClicked",  1);
  reminder_timer = 15000;
  loadReminderPopup();
});

// When the user clicks anywhere outside of the modal, close it
window.onclick = function(event) {
  if (event.target == reminder_modal) {
  	
    //reminder_modal.style.display = "none";
    //localStorage.setItem("isFastCheckoutClicked",  1);
    //reminder_timer = 15000;
    //loadReminderPopup();
  }
}

function loadReminderPopup(){

	var order_ref = localStorage.getItem("order_ref");
	var redirect_url = localStorage.getItem("redirect_url");
	checkReminderPopup(order_ref, redirect_url);
}

loadReminderPopup();


function checkReminderPopup(order_ref, redirect_url){	

	if(bsecure_js_object.wc_bsecure_reminder_popup == 'yes'){
					

		var nonce = bsecure_js_object.nonce;
		var counter = 0;
		var timer = setInterval(function() {		
			counter++;
			var isFastCheckoutClicked = localStorage.getItem("isFastCheckoutClicked");
			
			if(isFastCheckoutClicked == 1){

				jQuery.post(bsecure_js_object.ajax_url,
					{"action":"ajax_reminder_popup","wp_nonce":nonce,"order_ref":order_ref,"redirect_url":redirect_url},
				function(res){

				},"json").done(function(res) {				
				
					if(res.status){	
													
							jQuery("#bsecure-reminder-modal").html(res.popup_html);
							setTimeout(function(){
								isFastCheckoutClicked = localStorage.getItem("isFastCheckoutClicked");								
								if(isFastCheckoutClicked == 1){
									jQuery("#bsecure-reminder-modal").show();
								}
							},500);
							
					
					}
					  clearInterval(timer);
				})
				.fail(function(res) {
				
				})
				.always(function(res) {	
					
				});
				
			}else{

				clearInterval(timer);
			}


		}, reminder_timer);
	
	}

}


jQuery(document).on("click", ".reminder-popup-checkout-btn", function(){

	jQuery("#bsecure-reminder-modal").hide();

});

jQuery(document).on("click", ".view-hide-cart-reminder-popup", function(){

	jQuery(".insideBottomBlueBarContainer").toggle("slide");

});

// ------------------------ End Reminder Popup code ---------------------//


function isFbWebView() {
    var ua = navigator.userAgent || navigator.vendor || window.opera;
    return (ua.indexOf("FBAN") > -1) || (ua.indexOf("FBAV") > -1);
}

function isWebView(){
	var standalone = window.navigator.standalone,
	  userAgent = window.navigator.userAgent.toLowerCase(),
	  safari = /safari/.test(userAgent),
	  ios = /iphone|ipod|ipad/.test(userAgent);

	if (ios) {

	  if (!standalone && safari) {
	    
	    return true;

	  } else if (!standalone && !safari) {	 

	    return false;
	  };

	} else {
	  if (userAgent.includes('wv')) {
	    
	    return false;
	  } else {
	    
	    return true;
	  }
	};
}


function setReminderPopupCheck(order_ref, redirect_url, isFastCheckoutClicked){

	localStorage.setItem("isFastCheckoutClicked",  isFastCheckoutClicked);	
	localStorage.setItem("order_ref",  order_ref);	
	localStorage.setItem("redirect_url",  redirect_url);	
	
}


function clearLoaders(){

  if(jQuery("body.woocommerce-checkout, .woocommerce #payment").length > 0){
    unblock(jQuery("body.woocommerce-checkout, .woocommerce #payment"));
    if(jQuery("button#place_order").length > 0){
    	jQuery("button#place_order").removeAttr("disabled");
    }      
  }

}