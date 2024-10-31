<?php
/**
 * RW Elephant Rental Inventory - Submit Wishlist Template
 *
 * @author RW Elephant <support@rwelephant.com>
 */
?>

<?php wishlist_errors(); ?>

<div class="rwe-wishlist-submit <?php wishlist_additional_info_class(); ?>">

	<div class="rwe-wishlist-submit__form">

		<form class="rwe-wishlist-form" method="post">

			<?php

			/**
			 * Filter the list of available wishlist fields.
			 *
			 * @var array
			 */
			$form_fields = (array) apply_filters( 'rw_elephant_wishlist_form_fields', RWEG\Options::$options['wishlist-form-fields'] );

			if ( ! empty( $form_fields ) ) {

				foreach ( $form_fields as $key => $field ) {

					$required = ( isset( $field['required'] ) && $field['required'] ) ? 'required' : '';

					?>

					<div class="form-field">
						<label class="form-label" for="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $field['label'] ); ?></label>

						<?php

						switch ( $field['type'] ) {

							case 'text':
								?>
								<input type="text" class="text-input" name="<?php echo esc_attr( $key ); ?>" id="<?php echo esc_attr( $key ); ?>" <?php echo esc_attr( $required ); ?> value="<?php echo isset( $_POST[ $key ] ) ? esc_attr( $_POST[ $key ] ) : ''; ?>" />
								<?php
								break;

							case 'email':
								?>
								<input type="email" class="text-input" name="<?php echo esc_attr( $key ); ?>" id="<?php echo esc_attr( $key ); ?>" <?php echo esc_attr( $required ); ?> value="<?php echo isset( $_POST[ $key ] ) ? esc_attr( $_POST[ $key ] ) : ''; ?>" />
								<?php
								break;

							case 'date':
								?>
								<input type="text" class="text-input js-date" name="<?php echo esc_attr( $key ); ?>" id="<?php echo esc_attr( $key ); ?>" aria-label="<?php esc_attr_e( 'Use the arrow keys to pick a date', 'rw-elephant-rental-inventory' ); ?>" <?php echo esc_attr( $required ); ?> value="<?php echo isset( $_POST[ $key ] ) ? esc_attr( $_POST[ $key ] ) : ''; ?>" autocomplete="off" onkeydown="return false" />
								<?php
								break;

							case 'checkbox':
							case 'radio':
								if ( ! isset( $field['options'] ) || empty( $field['options'] ) ) {
									return;
								}
								$x = 0;
								foreach ( $field['options'] as $value => $label ) {
									$checked = ( 'radio' === $field['type'] && 0 === $x ) ? 'checked="checked"' : '';
									?>
									<label for="<?php echo esc_attr( $key . '-' . $x ); ?>"><?php echo esc_html( $label ); ?></label>
									<input type="<?php echo esc_attr( $field['type'] ); ?>" name="<?php echo esc_attr( $key ); ?>[]" id="<?php echo esc_attr( $key . '-' . $x ); ?>" value="<?php echo esc_attr( $value ); ?>" <?php echo esc_attr( $checked ); ?> />&nbsp;
									<?php
									$x++;
								}
								break;

							case 'select':
								?>
								<select name="<?php echo esc_attr( $key ); ?>" id="<?php echo esc_attr( $key ); ?>" <?php echo esc_attr( $required ); ?>>
									<?php
									if ( isset( $field['options'] ) && ! empty( $field['options'] ) ) {
										$x = 1;
										foreach ( $field['options'] as $value => $label ) {
											$selected = ( 1 === $x ) ? 'selected="selected' : '';
											?>
											<option value="<?php echo esc_attr( $value ); ?>" <?php echo esc_attr( $selected ); ?>><?php echo esc_html( $label ); ?></option>
											<?php
											$x++;
										}
									}
									?>
								</select>
								<?php
								break;

							/**
							 * Custom Fields
							 */
							default:
							case 'custom':
								if ( ! isset( $field['callback'] ) || ! is_callable( $field['callback'] ) ) {
									break;
								}
								// Call the field callback method.
								call_user_func( $field['callback'] );
								break;

						}

						?>

					</div>
					<!-- /form-field -->

					<?php

				}

				?>

				<div class="rwe-circle-preloader wishlist hidden">
					<div class="rwe-circle-preloader1 rwe-circle-preloader-child"></div>
					<div class="rwe-circle-preloader2 rwe-circle-preloader-child"></div>
					<div class="rwe-circle-preloader3 rwe-circle-preloader-child"></div>
					<div class="rwe-circle-preloader4 rwe-circle-preloader-child"></div>
					<div class="rwe-circle-preloader5 rwe-circle-preloader-child"></div>
					<div class="rwe-circle-preloader6 rwe-circle-preloader-child"></div>
					<div class="rwe-circle-preloader7 rwe-circle-preloader-child"></div>
					<div class="rwe-circle-preloader8 rwe-circle-preloader-child"></div>
					<div class="rwe-circle-preloader9 rwe-circle-preloader-child"></div>
					<div class="rwe-circle-preloader10 rwe-circle-preloader-child"></div>
					<div class="rwe-circle-preloader11 rwe-circle-preloader-child"></div>
					<div class="rwe-circle-preloader12 rwe-circle-preloader-child"></div>
				</div>

				<?php

			}

			wp_nonce_field( 'rw_elephant_submit_wishlist', 'submit_wishlist' );

			?>

			<div class="submit-field">

				<input type="submit" class="rwe-button rwe-button--primary" value="<?php _e( 'Submit Wishlist', 'rw-elephant-rental-inventory' ); ?>" />

			</div>

		</form>

	</div>

	<?php

	$additional_info = RWEG\Options::$options['wishlist-additional-info'];

	if ( ! empty( $additional_info['heading'] ) || ! empty( $additional_info['text'] ) ) {

		?>

		<div class="rwe-wishlist-submit__info">
			<div class="rwe-wishlist-info">
				<h3 class="rwe-wishlist-info__heading"><?php echo esc_html( $additional_info['heading'] ); ?></h3>
				<div class="rwe-wishlist-info__content">
					<p><?php echo wpautop( wp_kses_post( $additional_info['text'] ) ); ?></p>
				</div>
			</div>
		</div>

		<?php

	}

	?>

</div>
