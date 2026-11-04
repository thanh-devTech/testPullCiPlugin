<?php if (! defined('ABSPATH')) {
    exit; // Exit if accessed directly
}
$title = apply_filters('otpa_otp_widget_identifier_placeholder', __('Identifier', 'otpa'));
$max_length = $this->code_length;
?>
<label class="otpa-label">Phone Number</label>
<div class="otpa-button-field">
    <input id="otpa_id_widget" type="text" placeholder="Enter your phone number" />
</div>
<label class="otpa-label otpa-hide otpa-code-field">Verification Code</label>
<input id="otpa_code_widget" class="otpa-hide otpa-code-field" type="text" maxlength="<?php echo $max_length ?>" placeholder="<?php echo esc_html(apply_filters('otpa_otp_widget_code_placeholder', __('Enter verification code', 'otpa'))); ?>" />

<label class="otpa-label otpa-hide otpa-first_name-field">First Name:</label>
<input id="otpa_first_name_widget" class="otpa-hide otpa-first_name-field" type="text" placeholder="Enter your first name" />

<label class="otpa-label otpa-hide otpa-last_name-field">Last Name:</label>
<input id="otpa_last_name_widget" class="otpa-hide otpa-last_name-field" type="text" placeholder="Enter your last name" />

<label class="otpa-label otpa-hide otpa-dob-field">Date of Birth:</label>
<input id="otpa_date_widget" class="otpa-hide otpa-dob-field" type="date" />

<label class="otpa-label otpa-hide otpa-dob-field">Date of Birth:</label>
<input id="otpa_date_widget" class="otpa-hide otpa-dob-field" type="date" />

<button id="otpa_send_code" disabled="disabled" data-slot="button" class="inline-flex items-center justify-center gap-2 whitespace-nowrap text-sm font-medium disabled:pointer-events-none disabled:opacity-50 [&amp;_svg]:pointer-events-none [&amp;_svg:not([class*='size-'])]:size-4 shrink-0 [&amp;_svg]:shrink-0 outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] aria-invalid:ring-destructive/20 dark:aria-invalid:ring-destructive/40 aria-invalid:border-destructive h-9 px-4 has-[&gt;svg]:px-3 w-full bg-[#D83D00] hover:bg-[#c03600] text-white py-2.5 rounded-lg transition-all" type="button">Send Verification Code</button>
<!--<button id="btn_singin" data-slot="button" class="inline-flex items-center justify-center gap-2 whitespace-nowrap text-sm font-medium disabled:pointer-events-none disabled:opacity-50 [&amp;_svg]:pointer-events-none [&amp;_svg:not([class*='size-'])]:size-4 shrink-0 [&amp;_svg]:shrink-0 outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] aria-invalid:ring-destructive/20 dark:aria-invalid:ring-destructive/40 aria-invalid:border-destructive h-9 px-4 has-[&gt;svg]:px-3 w-full bg-[#D83D00] hover:bg-[#c03600] text-white py-2.5 rounded-lg transition-all" type="submit">Sign In</button>!-->

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