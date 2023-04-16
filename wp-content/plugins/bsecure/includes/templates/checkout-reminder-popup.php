<?php


$fast_checkout_btn = "";

if(class_exists('WC_Bsecure')){

	$wc_bsecure = new WC_Bsecure;

	$fast_checkout_btn = $wc_bsecure->getBsecureBtnHtml('reminder_popup', true, ['redirect_url' => $redirect_url]);

}

?>



  <!-- Modal content -->
  <div class="bsecure-modal-content completeCheckoutModal">
    <div class="bsecure-modal-header">
      <span class="bsecure-modal-close">
	  		<img src="<?php echo plugin_dir_url( __FILE__ ); ?>../../assets/images/popup-close.png" alt="" />
	  	</span>
    </div>
    <div class="bsecure-modal-body">
      <div class="completeCheckoutLogo">				

				<img src="<?php echo plugin_dir_url( __FILE__ ); ?>../../assets/images/complete-checkout-img.png" alt="" />

		  </div>

	  	<p class="modalBigHeading"><?php echo __('Complete your checkout', 'wc-bsecure'); ?></p>
		  
		  <div class="examplePayBox errorBox">

		  	<div class="leftExamplePayBox"><?php echo __('You are almost done, click below to continue your checkout', 'wc-bsecure'); ?></div>				
				
		  </div>
		  
		  <div class="bSecureBtnDiv">
		  	<?php  echo $fast_checkout_btn; ?>
		  </div>

		  

    </div>

    <div class="bsecure-modal-footer">
			<div class="modalFooterPara">
					<span><a href="#_" class="view-hide-cart-reminder-popup"><?php echo __('View your cart', 'wc-bsecure'); ?></a></span>				
			</div>
    </div>
    <div class="bottomBlueBar">
    	<div class="insideBottomBlueBarContainer" style="display: none;">
				
					<?php 
					include(plugin_dir_path( dirname( __FILE__ ) ) . '/templates/mini-cart.php');		 
				?>

			</div>
		</div>
  </div>
