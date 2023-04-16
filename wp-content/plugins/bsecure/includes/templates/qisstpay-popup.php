<?php

$cart_total_amount = 0;

if (!empty(WC()->cart)) { 

	$cart_total_amount = floatval( preg_replace( '#[^\d.]#', '', WC()->cart->get_cart_total() ) );

	$cart_totals =  WC()->cart->get_totals();

	$cart_total_amount =   !empty($cart_totals['total']) ? floatval($cart_totals['total']) : $cart_total_amount;

}

$woocommerce_currency = get_woocommerce_currency_symbol();

$qisstpay_per_month = 2;
$amount_max_limit = 10000;
$amount_min_limit = 1500;
$returning_customer_max_limit = 35000;
$fast_checkout_btn = "";

if(class_exists('WC_Bsecure')){

	$wc_bsecure = new WC_Bsecure;
	$qisstpay_per_month = WC_Bsecure::QISSTPAY_PER_MONTH;
	$amount_max_limit = WC_Bsecure::QISSTPAY_AMOUNT_MAX_LIMIT;
	$amount_min_limit = WC_Bsecure::QISSTPAY_AMOUNT_MIN_LIMIT;
	$returning_customer_max_limit = WC_Bsecure::RETURNING_CUSTOMER_MAX_LIMIT;
	$fast_checkout_btn = $wc_bsecure->getBsecureBtnHtml('cart', true);

}

?>

<!-- The Modal -->
<div id="qisstpay-modal" class="bsecure-modal">
  <!-- Modal content -->
  <div class="bsecure-modal-content">
    <div class="bsecure-modal-header">
      <span class="bsecure-modal-close">
	  		<img src="<?php echo plugin_dir_url( __FILE__ ); ?>../../assets/images/popup-close.png" alt="" />
	  	</span>
    </div>
    <div class="bsecure-modal-body">
      <div class="qistpay-logo">
		  	<img src="<?php echo plugin_dir_url( __FILE__ ); ?>../../assets/images/qpLogo-2.png" alt="" />

				<img src="<?php echo plugin_dir_url( __FILE__ ); ?>../../assets/images/add-icon.png" alt="" />

				<img src="<?php echo plugin_dir_url( __FILE__ ); ?>../../assets/images/bsecure-small-logo.png" alt="" />

		  </div>

	  	<p class="modalBigHeading"><?php echo __('Make easy monthly payments', 'wc-bsecure'); ?></p>
		  <?php /* <div class="tabBox">
		  	<a href="javascript:;" class="tab1 selectedTab" data-months="<?php echo $qisstpay_per_month; ?>"><?php echo __('Split Pay (Upto '. $qisstpay_per_month . ' Months)'); ?></a>
		  	<a href="javascript:;" class="tab2" data-months="4"><?php echo __('Pay In 4 Months'); ?></a>
		  	<input type="hidden" id="qisstpayNumOfMonths" value="<?php echo $qisstpay_per_month; ?>">
		  </div> */ ?>
		  <div class="examplePayBox errorBox">

		  	<div class="leftExamplePayBox"><?php echo __('Your cart value is ', 'wc-bsecure'); ?></div>

				<div class="middleExamplePayBox">

					<span class="currencyValue"><?php echo $woocommerce_currency; ?>. </span>

					<input type="text" class="editablePrice" value="<?php echo number_format($cart_total_amount); ?>"  data-numeric-amount="<?php echo ($cart_total_amount); ?>" readonly />

					<?php /* <span class="exampleEditIcon"><img src="<?php //echo plugin_dir_url( __FILE__ ); ?>../../assets/images/edit-icon.png" alt="" /></span> */ ?>
				</div>
				<div class="rightExamplePayBox"><?php echo __('and your monthly payment will be ', 'wc-bsecure'); ?></div>

				<?php /* <span class="modalErrorText"><?php echo __('Price Range should be between ', 'wc-bsecure'); 

					 echo $woocommerce_currency; ?>. 1,500 <?php echo __('and', 'wc-bsecure'); ?> <?php echo $woocommerce_currency; ?>. 35,000</span> */ ?>

		  </div>
		  <div class="monthlyPayBox">

		 		<div class="actualMonthlyAmount"><?php echo $woocommerce_currency; ?>. <span class="disstpay-popup-monthly-amount"><?php echo number_format($cart_total_amount / $qisstpay_per_month, 2); ?></span></div>

				<div class="byMonthly"> / <?php echo __('month', 'wc-bsecure'); ?></div>

				<div class="noOfMonth"> <?php echo $qisstpay_per_month.' '.__('months', 'wc-bsecure'); ?></div>

		  </div>

		  <div class="bSecureBtnDiv">
		  	<?php  echo $fast_checkout_btn; ?>
		  </div>

		  <div class="footerQistPayLogoBox">

			 	<span><?php echo __('Just select', 'wc-bsecure'); ?></span>

				 <img src="<?php echo plugin_dir_url( __FILE__ ); ?>../../assets/images/qpLogo-1.png" alt="" />

				 <img src="<?php echo plugin_dir_url( __FILE__ ); ?>../../assets/images/qisst-pay.png" alt="" />

				<span> <?php echo __('at checkout', 'wc-bsecure'); ?></span> 

		  </div>

    </div>

    <div class="bsecure-modal-footer">
			<div class="modalFooterPara">
				<p><?php echo __('The order limit for using QisstPay is between Rs. '.number_format($amount_min_limit).' and Rs. '.number_format($returning_customer_max_limit), 'wc-bsecure'); ?></p>

				<span><a href="https://help.bsecure.pk/payment-gateways/qisstpay" target="_blank"><?php echo __('Learn more about QisstPay here', 'wc-bsecure'); ?></a></span>				
			</div>
    </div>
    <div class="bottomBlueBar">
    	<div class="insideBottomBlueBarContainer">
				<img src="<?php echo plugin_dir_url( __FILE__ ); ?>../../assets/images/qisst-pay-popup-bottom-logo.svg" alt="" />
				<img src="<?php echo plugin_dir_url( __FILE__ ); ?>../../assets/images/bsecure-small-line.svg" class="sepratorLine" alt="" />
				<a href="https://bsecure.pk/" target="_blank"><?php echo __('Learn more about bSecure here', 'wc-bsecure'); ?><img src="<?php echo plugin_dir_url( __FILE__ ); ?>../../assets/images/bsecure-small-arrow.svg" class="bsecureSmallArrow" /></a>
			</div>
		</div>
  </div>
</div>