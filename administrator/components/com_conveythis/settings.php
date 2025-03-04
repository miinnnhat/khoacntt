<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_conveythis
 *
 * @copyright   Copyright (C) 2018 www.conveythis.com, All rights reserved.
 * @license     ConveyThis Translate is licensed under GPLv2 license.
 */
defined('_JEXEC') or die('Restricted access');

//~ ini_set( 'error_reporting', E_ALL );
//~ ini_set( 'display_errors', 1 );
//~ ini_set( 'display_startup_errors', 1 );
?>

<?php if( !empty( $error ) ): ?>

<div class="ui negative message">
	<div><?php echo $error['message']; ?></div>
</div>

<?php endif;?>


<div class="wrap">
	<form class="conveythis-widget-option-form" style="max-width: 60%; min-width: 1024px; position: relative;" method="post" action="<?php echo JRoute::_('index.php?option=com_conveythis'); ?>">

		<h3><?php echo 'Main configuration'; ?></h3>

        <?php if (!$allow_url_rewrite){
            $message = 'Please ensure that you have enabled "URL Rewriting" in your Global Configuration. Without this option enabled, you may encounter 404 errors when attempting to translate your page.';
            $app->enqueueMessage($message, 'warning');
        }?>

        <?php if ($remainingDays > 0 && intval($data[0]['is_trial_expired']) == 0): ?>
            <?php $message = $remainingDays . ' days left in the trial.<br> Your free trial is coming to an end. Click <a href="http://app.conveythis.com/dashboard/pricing/">here</a> to upgrade your plan.'; ?>
            <?php $app->enqueueMessage($message, 'warning'); ?>
        <?php else:?>
            <?php $message = 'Your PRO trial has ended, and our widget on your site is currently inactive.<br> To republish the widget, please visit your <a href="http://app.conveythis.com/dashboard/">dashboard</a> and select a plan'; ?>
            <?php $app->enqueueMessage($message, 'warning'); ?>
        <?php endif;?>

		<div id="customize-preview" style="display: none;">
			<div style="font-weight: 700; padding: 1em 1em; font-size: 1.3em;">
				<?php echo 'Preview'; ?>
			</div>
			<div style="margin: 0; padding: 1em 1em; border: none; border-top: 1px solid rgba(34,36,38,.1);height: 70px;">
				<div id="customize-view-button" style="z-index: 1;"></div>
			</div>
		</div>

		
		<!-- -->

		<h4><?php echo 'API Key'; ?></h4>
		<div style="margin: 10px 0 28px 0; width: 550px;">
			<div style="float: left; width: 190px;">
				<?php
					$message = 'Log in to %2$sConveyThis%1$s to get your API key.';
					echo sprintf( $message, '</a>', '<a target="_blank" href="https://app.conveythis.com/account/register/?utm_source=widget&utm_medium=joomla">' );
				?>
			</div>
			<div style="margin-left: 220px;">

				<input type="text" class="conveythis-input-text" name="api_key" value="<?php echo $api_key; ?>" placeholder="pub_XXXXXXXX" />

			</div>
		</div>
		<div style="clear:both"></div>

		<!-- -->

		<h4><?php echo 'Source Language'; ?></h4>
		<div style="margin: 10px 0 28px 0; width: 550px;">
			<div style="float: left; width: 190px;">
				<p>
					<?php echo 'What is the source (current) language of your website?'; ?>
				</p>
			</div>
			<div style="margin-left: 220px;">

					<!-- Semantic -->
					<div class="ui fluid search selection dropdown">
						<input type="hidden" name="source_language" value="<?php echo $source_language; ?>">
						<i class="dropdown icon"></i>
						<div class="default text"><?php echo 'Select source language'; ?></div>
						<div class="menu">

							<?php foreach( $languages as $language ): ?>

								<div class="item" data-value="<?php echo $language['code2']; ?>">
									<?php echo $language['title']; ?>
								</div>

							<?php endforeach; ?>

						</div>
					</div>
			
			</div>
		</div>
		<div style="clear:both"></div>

		<!-- -->

		<h4><?php echo 'Target Languages'; ?></h4>
		<div style="margin: 10px 0 28px 0; width: 550px;">
			<div style="float: left; width: 190px;">
				<p>
					<?php echo 'Choose languages you want to translate into.'; ?>
				</p>
			</div>
			<div style="margin-left: 220px;">

					<!-- Semantic -->
					<div class="ui fluid multiple search selection dropdown">
						<input type="hidden" name="target_languages" value="<?php echo implode( ',', $target_languages ); ?>">
						<i class="dropdown icon"></i>
						<div class="default text">French, German, Italian, Portuguese …</div>
						<div class="menu">

						<?php foreach( $languages as $language ): ?>

							<div class="item" data-value="<?php echo $language['code2']; ?>">
								<?php echo $language['title']; ?>
							</div>

						<?php endforeach; ?>

						</div>
					</div>

			</div>
		</div>
		<div style="margin: 20px 0; width: 565px;">
			<div style="float: left; width: 170px;"></div>
			<div style="margin-left: 220px;">
				<?php
					$message = 'On the free plan, you can only choose one target language. If you want to use more than 1 language, please %2$supgrade your plan%1$s.';
					echo sprintf( $message, '</a>', '<a target="_blank" href="https://www.conveythis.com/pricing/">' );
				?>
			</div>
		</div>
		<div style="clear:both"></div>
		
		<!-- -->

		<?php if( !empty( $api_key ) ): ?>

			<h4><?php echo 'Where are my translations?'; ?></h4>

			<div style="margin: 10px 0 28px 0; width: 550px;">
				<div style="float: left; width: 190px;">
					<p>
						<?php echo 'You can find all your translations in your account'; ?>
					</p>
				</div>
				<div style="margin-left: 220px;">

					<a target="_blank" href="https://www.conveythis.com/domains/"><?php echo 'Edit My Translations'; ?></a>
				
				</div>
			</div>

		<?php endif; ?>

		<br>
		<br>

		<div style="border-bottom: 1px solid rgba(34,36,38,.15); padding-bottom: 10px;">
			<a href="#" id="customize-tab-toogle"><?php echo 'Show more options'; ?></a>
		</div>

		<div id="customize-tab" style="display: none">

			<h4><?php echo 'Automatic Language Redirection'; ?></h4>
			<div style="margin: 10px 0 45px 0; width: 550px;">
				<div style="float: left; width: 220px;">
					<p style="padding: 0; margin: 0;">
						<?php echo "Redirect visitors to translated pages automatically based on user browser's settings."; ?>
					</p>
				</div>
				<div style="margin-left: 300px;">
				
					<br>
					<?php if(!empty($auto_translate) && $auto_translate == 1): ?>
						<input type="radio" id="auto_translate_yes" name="auto_translate" value="1" checked style="margin-left: 20px;"> Yes
						<input type="radio" id="auto_translate_no"  name="auto_translate" value="0" style="margin-left: 20px;"> No
					<?php else: ?>
						<input type="radio" id="auto_translate_yes" name="auto_translate" value="1" style="margin-left: 20px;"> Yes
						<input type="radio" id="auto_translate_no"  name="auto_translate" value="0" checked style="margin-left: 20px;"> No
					<?php endif; ?>
				</div>
			</div>
			<div style="clear:both"></div>
			
			<div style="margin: 20px 0; width: 565px;">
				<div style="float: left; width: 170px;"></div>
				<div style="margin-left: 220px;">
					<?php
						$message = 'This feature is not available on Free and Starter plans. If you want to use this feature, please %supgrade your plan%s.';
						echo sprintf( $message, '<a target="_blank" href="https://www.conveythis.com/pricing/">', '</a>' );
					?>
				</div>
			</div>
			<div style="clear:both"></div>
			
			
			
			<div style="margin: 10px 0 30px 0; width: 550px;">
				<div style="float: left; width: 220px;">
					<p style="padding: 0; margin: 0;">
						<?php echo "Hide ConveyThis logo."; ?>
					</p>
				</div>
				<div style="margin-left: 300px;">
				
					<?php if(!empty($hide_conveythis_logo) && $hide_conveythis_logo == 1): ?>
						<input type="radio" id="hide_conveythis_logo_yes" name="hide_conveythis_logo" value="1" checked style="margin-left: 20px;"> Yes
						<input type="radio" id="hide_conveythis_logo_no"  name="hide_conveythis_logo" value="0" style="margin-left: 20px;"> No
					<?php else: ?>
						<input type="radio" id="hide_conveythis_logo_yes" name="hide_conveythis_logo" value="1" style="margin-left: 20px;"> Yes
						<input type="radio" id="hide_conveythis_logo_no"  name="hide_conveythis_logo" value="0" checked style="margin-left: 20px;"> No
					<?php endif; ?>
				</div>
			</div>
			<div style="clear:both"></div>
			
			<div style="margin: 20px 0; width: 565px;">
				<div style="float: left; width: 170px;"></div>
				<div style="margin-left: 220px;">
					<?php
						$message = 'This feature is not available on Free and Starter plans. If you want to use this feature, please %supgrade your plan%s.';
						echo sprintf( $message, '<a target="_blank" href="https://www.conveythis.com/pricing/">', '</a>' );
					?>
				</div>
			</div>
			<div style="clear:both"></div>
			
			<h3><?php echo 'Customize button'; ?></h3>

			<!-- -->

			<h4><?php echo 'Picture'; ?></h4>

			<div style="margin: 10px 0 28px 0; width: 550px;">
				<div style="float: left; width: 190px;">
					<p>
						<?php echo 'Select the display style for flags'; ?>
					</p>
				</div>
				<div style="margin-left: 220px;">

							<div class="grouped fields">
								<div class="field">
									<div class="ui radio checkbox">
										<?php if( !empty( $style_flag ) && $style_flag == 'rect' ): ?>

											<input type="radio" name="style_flag" value="rect" checked="checked">

										<?php else: ?>

											<input type="radio" name="style_flag" value="rect">

										<?php endif; ?>
										<label><?php echo 'Rectangle flag'; ?></label>
									</div>
								</div>
								<div class="field">
									<div class="ui radio checkbox">
										<?php if( !empty( $style_flag ) && $style_flag == 'sqr' ): ?>

											<input type="radio" name="style_flag" value="sqr" checked="checked">

										<?php else: ?>

											<input type="radio" name="style_flag" value="sqr">

										<?php endif; ?>
										<label><?php echo 'Square flag'; ?></label>
									</div>
								</div>
								<div class="field">
									<div class="ui radio checkbox">
										<?php if( !empty( $style_flag ) && $style_flag == 'cir' ): ?>

											<input type="radio" name="style_flag" value="cir" checked="checked">

										<?php else: ?>

											<input type="radio" name="style_flag" value="cir">

										<?php endif; ?>
										<label><?php echo 'Circle flag'; ?></label>
									</div>
								</div>
							</div>
				
				</div>
			</div>
			<div style="clear:both"></div>
		
			<!-- -->

			<h4><?php echo 'Text'; ?></h4>

			<div style="margin: 10px 0 28px 0; width: 550px;">
				<div style="float: left; width: 190px;">
					<p>
						<?php echo 'Display the text name of the language'; ?>
					</p>
				</div>
				<div style="margin-left: 220px;">

							<div class="grouped fields">
								<div class="field">
									<div class="ui radio checkbox">
										<?php if( !empty( $style_text ) && $style_text == 'full-text' ): ?>

											<input type="radio" name="style_text" value="full-text" checked="checked">

										<?php else: ?>

											<input type="radio" name="style_text" value="full-text">

										<?php endif; ?>
										<label><?php echo 'Full text, in English (e.g., Spanish)'; ?></label>
									</div>
								</div>
                                <div class="field">
                                    <div class="ui radio checkbox">
                                        <input type="radio" name="style_text" value="full-text-native" id="full-text-native" <?php echo  $style_text == 'full-text-native' ? 'checked' : '' ?> />
                                        <label for=""><?php echo 'Full text, in native language (e.g., Español)'; ?></label>
                                    </div>
                                </div>
								<div class="field">
									<div class="ui radio checkbox">
										<?php if( !empty( $style_text ) && $style_text == 'short-text' ): ?>

											<input type="radio" name="style_text" value="short-text" checked="checked">

										<?php else: ?>

											<input type="radio" name="style_text" value="short-text">

										<?php endif; ?>
										<label><?php echo 'Short text'; ?></label>
									</div>
								</div>
								<div class="field">
									<div class="ui radio checkbox">
										<?php if( !empty( $style_text ) && $style_text == 'without-text' ): ?>

											<input type="radio" name="style_text" value="without-text" checked="checked">

										<?php else: ?>

											<input type="radio" name="style_text" value="without-text">

										<?php endif; ?>
										<label><?php echo 'Without text'; ?></label>
									</div>
								</div>
							</div>
				
				</div>
			</div>
			<div style="clear:both"></div>
		
			<!-- -->

			<h4><?php echo 'Position'; ?></h4>

			<div style="margin: 10px 0 28px 0; width: 600px;">
				<div style="float: left; width: 190px;">
					Position type
				</div>
				<div style="margin-left: 220px;">

					<div class="ui radio checkbox">
						<input type="radio" name="style_position_type" value="fixed" <?php echo $style_position_type == 'fixed' ? 'checked="checked"' : '' ?> >
						<label>Fixed (fixed in certain position of screen)</label>
					</div>
					<div class="ui radio checkbox">
						<input type="radio" name="style_position_type" value="custom" <?php echo $style_position_type == 'custom' ? 'checked="checked"' : '' ?> >
						<label>Custom (placed inside of chosen element)</label>
					</div>

				</div>
			</div>
			
			<div id="position-fixed" <?php echo $style_position_type != 'fixed' ? 'style="display:none;"' : '' ?> >
				<div style="margin: 10px 0 28px 0; width: 550px;">
					<div style="float: left; width: 190px;">
						<p>
							<?php echo 'Vertical location of the language selection button on the site'; ?>
						</p>
					</div>
					<div style="margin-left: 220px;">

								<div class="grouped fields">
									<div class="field">
										<div class="ui radio checkbox">
											<?php if( !empty( $style_position_vertical ) && $style_position_vertical == 'top' ): ?>

												<input type="radio" name="style_position_vertical" value="top" checked="checked">

											<?php else: ?>

												<input type="radio" name="style_position_vertical" value="top">

											<?php endif; ?>
											<label><?php echo 'Top'; ?></label>
										</div>
									</div>
									<div class="field">
										<div class="ui radio checkbox">
											<?php if( !empty( $style_position_vertical ) && $style_position_vertical == 'bottom' ): ?>

												<input type="radio" name="style_position_vertical" value="bottom" checked="checked">

											<?php else: ?>

												<input type="radio" name="style_position_vertical" value="bottom">

											<?php endif; ?>
											<label><?php echo 'Bottom'; ?></label>
										</div>
									</div>
								</div>
					
					</div>
				</div>
				<div style="clear:both"></div>
				
				<div style="margin: 10px 0 28px 0; width: 550px;">
					<div style="float: left; width: 190px;">
						<p>
							<?php echo 'Horizontal location of the language selection button on the site'; ?>
						</p>
					</div>
					<div style="margin-left: 220px;">

								<div class="grouped fields">
									<div class="field">
										<div class="ui radio checkbox">
											<?php if( !empty( $style_position_horizontal ) && $style_position_horizontal == 'left' ): ?>

												<input type="radio" name="style_position_horizontal" value="left" checked="checked">

											<?php else: ?>

												<input type="radio" name="style_position_horizontal" value="left">

											<?php endif; ?>
											<label><?php echo 'Left'; ?></label>
										</div>
									</div>
									<div class="field">
										<div class="ui radio checkbox">
											<?php if( !empty( $style_position_horizontal ) && $style_position_horizontal == 'right' ): ?>

												<input type="radio" name="style_position_horizontal" value="right" checked="checked">

											<?php else: ?>

												<input type="radio" name="style_position_horizontal" value="right">

											<?php endif; ?>
											<label><?php echo 'Right'; ?></label>
										</div>
									</div>
								</div>
					
					</div>
				</div>
				<div style="clear:both"></div>
				<!-- -->

				<h4><?php echo 'Indenting'; ?></h4>

				<div style="margin: 10px 0 28px 0; width: 800px;">
					<div style="float: left; width: 190px;">
						<?php echo 'Vertical spacing from the top or bottom of the browser'; ?>
					</div>
					<div style="margin-left: 220px;">

						<div style="float: left;">
							<input type="hidden" name="style_indenting_vertical" value="<?php echo $style_indenting_vertical; ?>">
							<span id="display-indenting-vertical"><?php echo $style_indenting_vertical; ?></span>px
						</div>
						<!-- Semantic -->
						<div class="ui grey range" style="margin-left: 36px;" id="range-indenting-vertical"></div>

					</div>
					<div style="clear:both"></div>
				</div>
				<div style="clear:both"></div>
				
				<div style="margin: 10px 0 28px 0; width: 800px;">
					<div style="float: left; width: 190px;">
						<?php echo 'Horizontal spacing from the top or bottom of the browser'; ?>
					</div>
					<div style="margin-left: 220px;">

						<div style="float: left;">
							<input type="hidden" name="style_indenting_horizontal" value="<?php echo $style_indenting_horizontal; ?>">
							<span id="display-indenting-horizontal"><?php echo $style_indenting_horizontal; ?></span>px
						</div>
						<!-- Semantic -->
						<div class="ui grey range" style="margin-left: 36px;" id="range-indenting-horizontal"></div>

					</div>
					<div style="clear:both"></div>
				</div>
			
			</div>
			
			<div id="position-custom" <?php echo $style_position_type == 'fixed' ? 'style="display:none;"' : '' ?> >
				<div style="margin: 10px 0 28px 0; width: 520px;">
					<div style="float: left; width: 190px;">
						<?php echo 'Enter id of element, where button will be placed'; ?>						
					</div>
					<div style="margin-left: 220px;">

						<div>
							<input type="text" name="style_selector_id" class="form-control" value="<?php echo $style_selector_id ?>" style="width: 100%;">
							<label><?php echo '* If id of element will not be found on the page, default position will be used'; ?></label>
						</div>

					</div>
				</div>
				<div style="margin: 10px 0 28px 0; width: 520px;">
					<div style="float: left; width: 190px;">
						<?php 'Select dropdown menu direction'; ?>	
					</div>
					<div style="margin-left: 220px;">
						<div class="ui radio checkbox">
							<input type="radio" name="style_position_vertical_custom" value="bottom" <?php echo $style_position_vertical_custom == 'bottom' ? 'checked="checked"' : '' ?> >
							<label><?php echo 'Up'; ?></label>
						</div>
						<div class="ui radio checkbox">
							<input type="radio" name="style_position_vertical_custom" value="top" <?php echo $style_position_vertical_custom == 'top' ? 'checked="checked"' : '' ?> >
							<label><?php echo 'Down'; ?></label>
						</div>
					</div>
				</div>
				
			</div>
			<div style="clear:both"></div>

			<!-- -->

			<h4><?php echo 'SEO'; ?></h4>

			<div style="margin: 10px 0 28px 0; width: 800px;">
				<div style="float: left; width: 190px;">
					<?php echo 'Hreflang tags'; ?>
				</div>
				<div style="margin-left: 220px;">

					<?php if( $alternate == 'on' ): ?>

						<input type="checkbox" name="alternate" value="1" checked="checked">

					<?php else: ?>

						<input type="checkbox" name="alternate" value="1">

					<?php endif; ?>

					<label><?php echo 'Add to all pages'; ?></label>

				</div>
			</div>
			<div style="clear:both"></div>

			<!-- -->

			<br>
			<h4><?php echo 'Change flag'; ?></h4>
			<div style="margin: 10px 0 28px 0; width: 800px;">
				<p>
					<?php echo 'By default all the languages have their flags in accordance with ISO standards. If you want to change the flag for one or several languages here you can customize this.'; ?>
				</p>

                <?php
                if (count($style_change_language) > 0) {
                    $i = 0;
                    while ($i < 5) {
                ?>

				<div style="margin: 28px 0; width: 520px;">
					<div style="float: left; width: 250px;">

						<!-- Semantic -->
						<div class="ui fluid search selection dropdown">
							<input type="hidden" name="style_change_language[]" value="<?php echo $style_change_language[$i]; ?>">
							<i class="dropdown icon"></i>
							<div class="default text"><?php echo 'Select language'; ?></div>
							<div class="menu">

								<?php foreach( $languages as $id => $language ): ?>

									<div class="item" data-value="<?php echo $id; ?>">
										<?php echo $language['title']; ?>
									</div>

								<?php endforeach; ?>

							</div>
						</div>

					</div>
					<div style="float: left; width: 250px; margin-left: 20px;">

						<!-- Semantic -->
						<div class="ui fluid search selection dropdown">
							<input type="hidden" name="style_change_flag[]"  value="<?php echo $style_change_flag[$i]; ?>">
							<i class="dropdown icon"></i>
							<div class="default text"><?php echo 'Select Flag'; ?></div>
							<div class="menu">

								<?php foreach( $flags as $flag ): ?>

									<div class="item" data-value="<?php echo $flag['code']; ?>">
										<div class="ui image" style="height: 28px; width: 30px; background-position: 50% 50%; background-size: contain; background-repeat: no-repeat; background-image: url('//cdn.conveythis.com/images/flags/v3/rectangular/<?php echo $flag['code']; ?>.png')"></div>
										<?php echo $flag['title']; ?>
									</div>

								<?php endforeach; ?>

							</div>
						</div>

					</div>
					<div style="margin-left: 560px;">
						<button class="conveythis-reset">X</button>
					</div>
				</div>

                <?php
                        $i++;
                    }
                }
                ?>

			</div>

		</div>

		<button type="submit" id="save-button">Save configuration</button>

	</form>

	<p>
	<?php
		$message = 'If you need any help, you can contact us via our live chat at %2$swww.ConveyThis.com%1$s or email us at support@conveythis.com. You can also check our %3$sFAQ%1$s';
		echo sprintf( $message, '</a>', '<a href="https://www.conveythis.com" target="_blank">', '<a href="https://www.conveythis.com/support/faq/" target="_blank">' );
	?>
	</p>
</div>

<style>

	.ui.fluid.dropdown {
		width: initial;
	}

	.ui.dropdown .menu>.item {
		font-size: initial;
	}

	.ui.selection.dropdown {
		min-height: initial;
	}

	.ui.dropdown .delete.icon:before {
		cursor: pointer;
		content: 'x';
		font-style: initial;
		margin-left: 6px;
		color: #f7b4b4;
		line-height: 1.2em;
	}

	.conveythis-reset {
		border: none;
		color: red;
		padding: 2px 8px;
		border-radius: 3px;
		margin-top: 8px;
	}

	#save-button {
		padding: 14px 35px;
		margin-top: 56px;
		text-transform: uppercase;
		font-weight: bold;
		color: #fff;
		background-color: steelblue;
		border: none;
		border-radius: 3px;
		cursor: pointer;
	}

	#customize-preview {
		position: absolute;
		right: 0;
		z-index: 1;
		width: 290px;
		background: #fff;
		padding: 0;
		border: none;
		border-radius: .28571429rem;
		-webkit-box-shadow: 0 1px 3px 0 #d4d4d5, 0 0 0 1px #d4d4d5;
		box-shadow: 0 1px 3px 0 #d4d4d5, 0 0 0 1px #d4d4d5;
		-webkit-transition: -webkit-box-shadow .1s ease,-webkit-transform .1s ease;
		transition: -webkit-box-shadow .1s ease,-webkit-transform .1s ease;
		transition: box-shadow .1s ease,transform .1s ease;
		transition: box-shadow .1s ease,transform .1s ease,-webkit-box-shadow .1s ease,-webkit-transform .1s ease;
	}

	.conveythis-input-text {
		padding : 8px;
		vertical-align: middle;
		width: 300px;
	}

</style>


<script src="<?php echo CONVEYTHIS_JAVASCRIPT_PLUGIN_URL; ?>/conveythis.js?api_key=<?php echo $api_key; ?>&preview=1" defer></script>
<script defer>
    $(document).ready(function() {

        var bootsrtap = $.fn.dropdown.noConflict();
        var cardOffset;
        var cardTop;

        $("#customize-preview").show("fast", function(){
            cardOffset = $("#customize-preview").offset();
            cardTop = $("#customize-preview").css( "top" );
        });

        $(document).scroll(function() {

            if( cardTop == $("#customize-preview").css( "top" ) ) {
                cardOffset = $("#customize-preview").offset();
            }

            if( ( cardOffset.top - 90 ) < $(this).scrollTop() ) {

                var top = $(this).scrollTop() - cardOffset.top + 90 + parseInt( cardTop, 10 );

                $("#customize-preview").css( "top", top + "px" );

            } else {
                $("#customize-preview").css( "top", "" );
            }
        });

        $("#customize-tab-toogle").click(function(e){
            e.preventDefault();
            $("#customize-tab-toogle").parent().hide();
            $("#customize-tab").slideToggle("slow");
        });

        $("#range-indenting-vertical").range({
            min: 0,
            max: 300,
            start: $("#display-indenting-vertical").text(),
            onChange: function(value) {
                $("#display-indenting-vertical").html( value );
                $("[name=style_indenting_vertical]").val( value );
            }
        });

        $("#range-indenting-horizontal").range({
            min: 0,
            max: 300,
            start: $("#display-indenting-horizontal").text(),
            onChange: function(value) {
                $("#display-indenting-horizontal").html( value );
                $("[name=style_indenting_horizontal]").val( value );
            }
        });

        console.log("window", window.conveythisSettings)

        $(document).ready(function() {
            function checkTools() {
                if (window.conveythisSettings.effect && window.conveythisSettings.view)
                {
                    window.conveythisSettings.effect(function(){
                        $('#customize-view-button').transition('pulse');
                    });
                    window.conveythisSettings.view()

                    $('.ui.dropdown').dropdown({
                        onChange: function() {
                            window.conveythisSettings.view();
                        }
                    });

                }
                else
                {
                    setTimeout(checkTools, 100);
                }
            }
            checkTools();
        });





        $('.conveythis-reset').on('click', function(e) {
            e.preventDefault();
            $(this).parent().parent().find('.ui.dropdown').dropdown('clear');
        });

        function showPositionType(type){

            if(type == 'custom'){
                $('#position-fixed').fadeOut();
                $('#position-custom').fadeIn();
            }else{
                $('#position-custom').fadeOut();
                $('#position-fixed').fadeIn();
            }
        }

        $('input[name=style_position_type]').change(function(){
            // console.log(this.value);
            showPositionType(this.value);
        });

    });
</script>