<?php if (! defined('ABSPATH')) {
    exit; // Exit if accessed directly
}
$title = apply_filters('otpa_otp_widget_identifier_placeholder', __('Identifier', 'otpa'));
$max_length = $this->code_length;
$pa_options = get_option('trpatient_settings', array());
$show_dob = !empty($pa_options['enable_check_dob']) || !empty($pa_options['enable_acheck_dob']);
?>
<div class="otpa-button-field">
    <input id="otpa_id_widget" type="text" placeholder="<?php echo esc_html($title); ?>" />
    <button disabled="disabled" id="otpa_send_code"><span class="">Send Code</span></button>
</div>
<input id="otpa_code_widget" type="text" maxlength="<?php echo $max_length ?>" placeholder="<?php echo esc_html(apply_filters('otpa_otp_widget_code_placeholder', __('Code', 'otpa'))); ?>" />
<?php if ($show_dob) { ?>
    <label class="otpa-label" for="otpa_date_widget">Date of Birth:</label>
    <input id="otpa_date_widget" type="date" />
<?php } ?>
<?php if (stripos($title, 'phone') !== false) { ?>
    <script>
        const isNumericInput = (event) => {
            const key = event.keyCode;
            return ((key >= 48 && key <= 57) || // Allow number line
                (key >= 96 && key <= 105) // Allow number pad
            );
        };

        const isModifierKey = (event) => {
            const key = event.keyCode;
            return (event.shiftKey === true || key === 35 || key === 36) || // Allow Shift, Home, End
                (key === 8 || key === 9 || key === 13 || key === 46) || // Allow Backspace, Tab, Enter, Delete
                (key > 36 && key < 41) || // Allow left, up, right, down
                (
                    // Allow Ctrl/Command + A,C,V,X,Z
                    (event.ctrlKey === true || event.metaKey === true) &&
                    (key === 65 || key === 67 || key === 86 || key === 88 || key === 90)
                )
        };
        jQuery(function($) {
            $('#otpa_id_widget').on('keyup', function(event) {
                if (isModifierKey(event)) {
                    return;
                }

                var value1 = $(this).val().replace('+1', '');
                var input = value1.replace(/\D/g, '');
                if (input.length == 11 && input.substring(0, 1) === '1') {
                    input = input.substring(1, 11);
                }
                var zip = input.substring(0, 3);
                var middle = input.substring(3, 6);
                var last = input.substring(6, 10);

                if (input.length > 6) {
                    $(this).val('(' + zip + ') ' + middle + ' - ' + last)
                } else if (input.length > 3) {
                    $(this).val('(' + zip + ') ' + middle);
                } else if (input.length > 0) {
                    $(this).val('(' + zip + ') ');
                }
                $(this).change();
            }).on('keydown', function(event) {
                if (!isNumericInput(event) && !isModifierKey(event)) {
                    event.preventDefault();
                }
            })
        })
    </script>
<?php } ?>
