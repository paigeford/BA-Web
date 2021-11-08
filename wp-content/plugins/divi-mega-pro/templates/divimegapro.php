<?php

	$divibuilder = false;
	
	// is divi theme builder ?
	if ( is_singular() && 'on' === get_post_meta( $post_data->ID, '_et_pb_use_builder', true ) ) {
		$divibuilder = true;
	}
?>
<div id="divimegapro-container-<?php print et_core_esc_previously( $divimegapro_id );?>" class="divimegapro-container" data-animation="<?php print et_core_esc_previously( $dmp_animation ) ?>"
	data-bgcolor="<?php print et_core_esc_previously( $dmp_bg_color ) ?>" data-fontcolor="<?php print et_core_esc_previously( $dmp_font_color ) ?>" data-placement="<?php print et_core_esc_previously( $dmp_placement ) ?>" 
	data-margintopbottom="<?php print et_core_esc_previously( $dmp_margintopbottom ) ?>" data-megaprowidth="<?php print et_core_esc_previously( $dmp_megaprowidth ) ?>" data-megaprowidthcustom="<?php print et_core_esc_previously( $dmp_megaprowidth_custom ) ?>" data-megaprofixedheight="<?php print et_core_esc_previously( $dmp_megaprofixedheight ) ?>" 
	data-triggertype="<?php print et_core_esc_previously( $dmp_triggertype ) ?>" data-exittype="<?php print et_core_esc_previously( $dmp_exittype ) ?>" data-exitdelay="<?php print et_core_esc_previously( $dmp_exitdelay ) ?>"
	data-enable_arrow="<?php print et_core_esc_previously( $dmp_enable_arrow ) ?>" data-arrowfeature_type="<?php print et_core_esc_previously( $dmp_arrowfeature_type ) ?>">
	<div id="divimegapro-<?php print et_core_esc_previously( $post_data->ID ) ?>" class="divimegapro divimegapro-flexheight">
		<div class="divimegapro-pre-body">
			<div class="divimegapro-body">
				<?php print et_core_esc_previously( $body ); ?>
			</div>
		</div>
		<?php if ( $dmp_enabledesktop == 1 || $dmp_enablemobile == 1 ) { ?>
		<div class="divimegapro-close-container<?php if ( $dmp_enabledesktop ) { print ' dmp_enabledesktop'; } ?><?php if ( $dmp_enablemobile ) { print ' dmp_enablemobile'; } ?>">
			<button type="button" class="divimegapro-close divimegapro-customclose-btn-<?php print et_core_esc_previously( $post_data->ID ) ?>" data-dmpid="<?php print et_core_esc_previously( $post_data->ID ) ?>">
				<span class="<?php if ( $dmp_customizeclosebtn[0] == 1 ) { ?>dmm-custom-btn<?php } ?>">&times;</span>
			</button>
		</div>
		<?php } ?>
	</div>
</div>