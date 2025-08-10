<?php
/*
Plugin Name: wp_Custom_faq
Description: A custom WordPress FAQ plugin by 3rroronly1 that creates an accordion-style FAQ section matching the provided HTML design exactly. Use shortcode [wp_custom_faq] in a Shortcode block to display. Customize FAQs and colors in the settings page.
Version: 1.5
Author: 3rroronly1
*/

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Enqueue assets with unique handles to avoid conflicts
function wp_custom_faq_enqueue_assets() {
    global $post;
    if (is_a($post, 'WP_Post') && has_shortcode($post->post_content, 'wp_custom_faq')) {
        // Enqueue Bootstrap CSS
        wp_enqueue_style('wp-custom-faq-bootstrap', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css', [], '5.3.0');

        // Enqueue custom CSS
        $css_handle = 'wp-custom-faq-styles-' . wp_generate_uuid4();
        wp_enqueue_style($css_handle, false);
        wp_add_inline_style($css_handle, '
            .wp-custom-faq-wrapper, .wp-custom-faq-wrapper .wp-custom-faq-container, 
            .wp-custom-faq-wrapper .container, .wp-block-preformatted .wp-custom-faq-wrapper {
                background-color: transparent !important;
                margin: 0 !important;
                padding: 0 !important;
            }
            .wp-custom-faq-container .accordion {
                margin: 0 !important;
                padding: 0 !important;
            }
            .wp-custom-faq-container .accordion-item {
                border: 1px solid #dee2e6;
                border-radius: 0.25rem;
                margin-bottom: 2px !important;
                padding: 0 !important;
            }
            .wp-custom-faq-container .accordion-button {
                background-color: ' . esc_attr(get_option('wp_custom_faq_collapsed_bg', '#ffffff')) . ' !important;
                color: ' . esc_attr(get_option('wp_custom_faq_collapsed_text', '#9bc329')) . ' !important;
                font-weight: bold;
                position: relative;
                padding: 0.75rem 1.25rem !important;
            }
            .wp-custom-faq-container .accordion-button::after {
                content: "+";
                font-size: 1.5rem;
                color: ' . esc_attr(get_option('wp_custom_faq_icon_color', '#000000')) . ' !important;
                position: absolute;
                right: 1.25rem;
                top: 50%;
                transform: translateY(-50%);
                background-image: none !important;
            }
            .wp-custom-faq-container .accordion-button:not(.collapsed) {
                background-color: ' . esc_attr(get_option('wp_custom_faq_expanded_bg', '#9bc329')) . ' !important;
                color: ' . esc_attr(get_option('wp_custom_faq_expanded_text', '#ffffff')) . ' !important;
            }
            .wp-custom-faq-container .accordion-button:not(.collapsed)::after {
                content: "−";
                color: ' . esc_attr(get_option('wp_custom_faq_icon_color', '#000000')) . ' !important;
            }
            .wp-custom-faq-container .accordion-button:focus {
                box-shadow: none !important;
                border-color: ' . esc_attr(get_option('wp_custom_faq_collapsed_text', '#9bc329')) . ' !important;
            }
            .wp-custom-faq-container .accordion-body {
                color: #000000 !important;
                padding: 0.75rem 1.25rem !important;
            }
        ');

        // Enqueue Bootstrap JS and custom initialization script
        wp_enqueue_script('wp-custom-faq-bootstrap', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js', ['jquery'], '5.3.0', true);
        wp_enqueue_script('wp-custom-faq-init', false, ['wp-custom-faq-bootstrap'], false, true);
        wp_add_inline_script('wp-custom-faq-init', '
            (function($) {
                $(document).ready(function() {
                    try {
                        $(".wp-custom-faq-container .accordion-collapse").each(function() {
                            $(this).removeClass("show").attr("aria-expanded", "false");
                        });
                        $(".wp-custom-faq-container .accordion-button").each(function() {
                            $(this).addClass("collapsed").attr("aria-expanded", "false");
                        });
                        if (typeof bootstrap === "undefined") {
                            console.error("wp_Custom_faq: Bootstrap 5.3 is not loaded. Check for conflicts with other plugins or themes.");
                        }
                    } catch (e) {
                        console.error("wp_Custom_faq: Error initializing accordion - " + e.message);
                    }
                });
            })(jQuery);
        ');
    }
}
add_action('wp_enqueue_scripts', 'wp_custom_faq_enqueue_assets');

// Register settings page
function wp_custom_faq_register_settings() {
    add_options_page(
        'wp Custom FAQ Settings',
        'wp Custom FAQ',
        'manage_options',
        'wp-custom-faq',
        'wp_custom_faq_settings_page'
    );
}
add_action('admin_menu', 'wp_custom_faq_register_settings');

// Register settings
function wp_custom_faq_register_options() {
    register_setting('wp_custom_faq_settings_group', 'wp_custom_faq_items', [
        'sanitize_callback' => 'wp_custom_faq_sanitize_items'
    ]);
    register_setting('wp_custom_faq_settings_group', 'wp_custom_faq_collapsed_bg', [
        'default' => '#ffffff',
        'sanitize_callback' => 'sanitize_hex_color'
    ]);
    register_setting('wp_custom_faq_settings_group', 'wp_custom_faq_collapsed_text', [
        'default' => '#9bc329',
        'sanitize_callback' => 'sanitize_hex_color'
    ]);
    register_setting('wp_custom_faq_settings_group', 'wp_custom_faq_expanded_bg', [
        'default' => '#9bc329',
        'sanitize_callback' => 'sanitize_hex_color'
    ]);
    register_setting('wp_custom_faq_settings_group', 'wp_custom_faq_expanded_text', [
        'default' => '#ffffff',
        'sanitize_callback' => 'sanitize_hex_color'
    ]);
    register_setting('wp_custom_faq_settings_group', 'wp_custom_faq_icon_color', [
        'default' => '#000000',
        'sanitize_callback' => 'sanitize_hex_color'
    ]);
}
add_action('admin_init', 'wp_custom_faq_register_options');

// Sanitize FAQ items
function wp_custom_faq_sanitize_items($input) {
    $sanitized = [];
    if (is_array($input)) {
        foreach ($input as $item) {
            if (!empty($item['question']) && !empty($item['answer'])) {
                $sanitized[] = [
                    'question' => sanitize_text_field($item['question']),
                    'answer' => wp_kses_post($item['answer'])
                ];
            }
        }
    }
    return $sanitized;
}

// Settings page
function wp_custom_faq_settings_page() {
    ?>
    <div class="wrap">
        <h1>wp Custom FAQ Settings</h1>
        <form method="post" action="options.php">
            <?php
            settings_fields('wp_custom_faq_settings_group');
            do_settings_sections('wp_custom_faq_settings_group');
            $faq_items = get_option('wp_custom_faq_items', []);
            $collapsed_bg = get_option('wp_custom_faq_collapsed_bg', '#ffffff');
            $collapsed_text = get_option('wp_custom_faq_collapsed_text', '#9bc329');
            $expanded_bg = get_option('wp_custom_faq_expanded_bg', '#9bc329');
            $expanded_text = get_option('wp_custom_faq_expanded_text', '#ffffff');
            $icon_color = get_option('wp_custom_faq_icon_color', '#000000');
            ?>
            <h2>FAQ Items</h2>
            <div id="faq-items">
                <?php
                if (!empty($faq_items)) {
                    foreach ($faq_items as $index => $item) {
                        ?>
                        <div class="faq-item">
                            <p>
                                <label>Question:</label><br>
                                <input type="text" name="wp_custom_faq_items[<?php echo $index; ?>][question]" value="<?php echo esc_attr($item['question']); ?>" style="width: 100%;">
                            </p>
                            <p>
                                <label>Answer:</label><br>
                                <textarea name="wp_custom_faq_items[<?php echo $index; ?>][answer]" style="width: 100%; height: 100px;"><?php echo esc_textarea($item['answer']); ?></textarea>
                            </p>
                            <button type="button" class="remove-faq button">Remove</button>
                            <hr>
                        </div>
                        <?php
                    }
                }
                ?>
            </div>
            <button type="button" id="add-faq" class="button">Add New FAQ</button>
            
            <h2>Color Settings</h2>
            <p>
                <label>Collapsed Background Color:</label>
                <input type="text" name="wp_custom_faq_collapsed_bg" value="<?php echo esc_attr($collapsed_bg); ?>" class="color-field">
            </p>
            <p>
                <label>Collapsed Text Color:</label>
                <input type="text" name="wp_custom_faq_collapsed_text" value="<?php echo esc_attr($collapsed_text); ?>" class="color-field">
            </p>
            <p>
                <label>Expanded Background Color:</label>
                <input type="text" name="wp_custom_faq_expanded_bg" value="<?php echo esc_attr($expanded_bg); ?>" class="color-field">
            </p>
            <p>
                <label>Expanded Text Color:</label>
                <input type="text" name="wp_custom_faq_expanded_text" value="<?php echo esc_attr($expanded_text); ?>" class="color-field">
            </p>
            <p>
                <label>Icon Color:</label>
                <input type="text" name="wp_custom_faq_icon_color" value="<?php echo esc_attr($icon_color); ?>" class="color-field">
            </p>
            
            <?php submit_button(); ?>
        </form>
        <p><strong>Important:</strong> Use the shortcode <code>[wp_custom_faq]</code> in a <strong>Shortcode block</strong> (not Preformatted or Code block) to display the FAQ accordion on any page, post, or widget to avoid styling or functionality issues.</p>
        <h2>Support the Developer</h2>
        <p>If you enjoy wp Custom FAQ, consider supporting the developer!</p>
        <a href="https://www.buymeacoffee.com/3rroronly1" target="_blank" class="button button-primary">Buy Me a Coffee</a>
    </div>

    <script>
        jQuery(document).ready(function($) {
            // Add new FAQ item
            $('#add-faq').click(function() {
                var index = $('.faq-item').length;
                var html = '<div class="faq-item">' +
                    '<p><label>Question:</label><br><input type="text" name="wp_custom_faq_items[' + index + '][question]" style="width: 100%;"></p>' +
                    '<p><label>Answer:</label><br><textarea name="wp_custom_faq_items[' + index + '][answer]" style="width: 100%; height: 100px;"></textarea></p>' +
                    '<button type="button" class="remove-faq button">Remove</button><hr></div>';
                $('#faq-items').append(html);
            });

            // Remove FAQ item
            $(document).on('click', '.remove-faq', function() {
                $(this).closest('.faq-item').remove();
            });

            // Initialize color picker
            $('.color-field').wpColorPicker();
        });
    </script>
    <?php
}

// Enqueue color picker for admin
function wp_custom_faq_admin_assets($hook) {
    if ($hook === 'settings_page_wp-custom-faq') {
        wp_enqueue_style('wp-color-picker');
        wp_enqueue_script('wp-color-picker', admin_url('js/color-picker.min.js'), ['jquery'], null, true);
    }
}
add_action('admin_enqueue_scripts', 'wp_custom_faq_admin_assets');

// Shortcode to display FAQs
function wp_custom_faq_shortcode() {
    $faq_items = get_option('wp_custom_faq_items', []);
    $accordion_id = 'wpCustomFaqAccordion' . wp_generate_uuid4();
    ob_start();
    ?>
    <div class="wp-custom-faq-wrapper">
        <div class="wp-custom-faq-container">
            <div class="container">
                <div class="accordion" id="<?php echo esc_attr($accordion_id); ?>">
                    <?php
                    foreach ($faq_items as $index => $item) {
                        $unique_id = wp_generate_uuid4();
                        ?>
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="heading<?php echo esc_attr($unique_id); ?>">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                    data-bs-target="#collapse<?php echo esc_attr($unique_id); ?>" aria-expanded="false"
                                    aria-controls="collapse<?php echo esc_attr($unique_id); ?>">
                                    <?php echo esc_html($item['question']); ?>
                                </button>
                            </h2>
                            <div id="collapse<?php echo esc_attr($unique_id); ?>" class="accordion-collapse collapse"
                                aria-labelledby="heading<?php echo esc_attr($unique_id); ?>" data-bs-parent="#<?php echo esc_attr($accordion_id); ?>">
                                <div class="accordion-body">
                                    <?php echo wp_kses_post($item['answer']); ?>
                                </div>
                            </div>
                        </div>
                        <?php
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('wp_custom_faq', 'wp_custom_faq_shortcode');
?>