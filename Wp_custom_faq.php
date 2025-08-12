<?php
/*
Plugin Name: wp_Custom_faq
Description: A custom WordPress FAQ plugin by 3rroronly1 that creates an accordion-style FAQ section matching the provided HTML design exactly. Use shortcode [wp_custom_faq] in a Shortcode block to display. Customize FAQs and colors in the settings page.
Version: 1.7
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

        // Enqueue Tailwind (scoped, no preflight) with a safe prefix to avoid conflicts
        wp_enqueue_script('wp-custom-faq-tailwind', 'https://cdn.tailwindcss.com', [], null, true);
        // Configure Tailwind BEFORE it loads to avoid preflight and use a unique prefix
        wp_add_inline_script(
            'wp-custom-faq-tailwind',
            'window.tailwind = window.tailwind || {};\n' .
            'tailwind.config = {\n' .
            '  corePlugins: { preflight: false },\n' .
            '  prefix: "tw-"\n' .
            '};',
            'before'
        );

        // Register and enqueue custom CSS handle to attach inline styles reliably
        $css_handle = 'wp-custom-faq-styles';
        wp_register_style($css_handle, false, [], null);
        wp_enqueue_style($css_handle);
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
                border: 0 !important;
                outline: 0 !important;
                box-shadow: none !important;
                background: transparent !important;
                /* Bootstrap 5 accordion variables (scoped) */
                --bs-accordion-border-width: 0;
                --bs-accordion-border-color: transparent;
                --bs-accordion-inner-border-radius: 0;
                --bs-accordion-btn-focus-box-shadow: none;
                --bs-accordion-btn-focus-border-color: transparent;
            }
            .wp-custom-faq-container .accordion-item {
                border: 0 !important;
                border-radius: 0 !important;
                margin-bottom: 0 !important;
                padding: 0 !important;
                outline: 0 !important;
                box-shadow: none !important;
                background: transparent !important;
            }
            .wp-custom-faq-container .accordion-header { 
                margin: 0 !important; 
                border: 0 !important; 
                outline: 0 !important; 
                box-shadow: none !important; 
                background: transparent !important; 
            }
            .wp-custom-faq-container .accordion-button {
                background-color: ' . esc_attr(get_option('wp_custom_faq_collapsed_bg', '#ffffff')) . ' !important;
                color: ' . esc_attr(get_option('wp_custom_faq_collapsed_text', '#9bc329')) . ' !important;
                font-weight: bold;
                position: relative;
                padding: 0.75rem 1.25rem !important;
                border: 0 !important;
                box-shadow: none !important;
                outline: none !important;
                border-color: transparent !important;
                background-image: none !important;
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
            /* Preserve visual spacing around inline formatted elements in question */
            .wp-custom-faq-container .accordion-button b,
            .wp-custom-faq-container .accordion-button strong,
            .wp-custom-faq-container .accordion-button em,
            .wp-custom-faq-container .accordion-button i,
            .wp-custom-faq-container .accordion-button u,
            .wp-custom-faq-container .accordion-button span {
                margin-left: 0.15em;
                margin-right: 0.15em;
            }
            .wp-custom-faq-container .accordion-button:not(.collapsed) {
                background-color: ' . esc_attr(get_option('wp_custom_faq_expanded_bg', '#9bc329')) . ' !important;
                color: ' . esc_attr(get_option('wp_custom_faq_expanded_text', '#ffffff')) . ' !important;
                border: 0 !important;
                box-shadow: none !important;
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
                ' . ((($bg = get_option('wp_custom_faq_answer_bg', '')) !== '') ? ('background-color: ' . esc_attr($bg) . ' !important;') : '') . '
                visibility: visible !important;
            }
            .wp-custom-faq-container .accordion-collapse { 
                border: 0 !important; 
                outline: 0 !important; 
                box-shadow: none !important; 
                background: transparent !important; 
                /* Keep it simple and reliable across themes */
                display: none !important;
                overflow: hidden !important;
            }
            .wp-custom-faq-container .accordion-collapse.show { 
                display: block !important; 
                overflow: visible !important;
            }
            .wp-custom-faq-container .accordion-collapse.show .accordion-body {
                display: block !important;
            }
            .wp-custom-faq-container .accordion-button.collapsed { border-bottom: 0 !important; }
            .wp-custom-faq-container .accordion-body { 
                border: 0 !important; 
                outline: 0 !important; 
                box-shadow: none !important; 
                border-top: 0 !important; 
                border-bottom: 0 !important; 
            }
            /* Safety net: remove any residual borders inside the accordion scope */
            .wp-custom-faq-container .accordion *,
            .wp-custom-faq-container .container {
                border-color: transparent !important;
            }
        ');

        // Do NOT enqueue our own Bootstrap JS to avoid conflicts with themes/plugins.
        // Rely on existing Bootstrap if present; otherwise, use a small conflict-free toggler.
        // Properly register a dummy handle so inline script is printed reliably
        wp_register_script('wp-custom-faq-init', '', ['jquery'], null, true);
        wp_enqueue_script('wp-custom-faq-init');
        wp_add_inline_script('wp-custom-faq-init', '
            (function($) {
                $(document).ready(function() {
                    try {
                        // Reset state
                        var $container = $(".wp-custom-faq-container");
                        $container.find(".accordion-collapse").each(function() {
                            $(this)
                                .removeClass("show")
                                .attr("aria-expanded", "false")
                                .removeAttr("data-bs-parent")
                                .css({ display: "none" });
                        });
                        $container.find(".accordion-button").each(function() {
                            var $btn = $(this);
                            $btn.addClass("collapsed").attr("aria-expanded", "false");
                            var target = $btn.attr("data-bs-target") || $btn.attr("data-target") || $btn.attr("data-wpfaq-target");
                            if (target) {
                                $btn.attr("data-wpfaq-target", target);
                            }
                            // Remove Bootstrap toggler attributes to prevent double-handling
                            $btn.removeAttr("data-bs-toggle").removeAttr("data-toggle");
                        });

                        // Our conflict-free toggler using a capture-phase native listener to beat other handlers
                            var handleAccordionClick = function(e) {
                                var btn = e.target.closest(".accordion-button");
                                // Ensure the button exists and belongs to the specific container this handler is bound to
                                if (!btn || !this.contains(btn)) {
                                    return;
                                }
                                e.preventDefault();
                                e.stopImmediatePropagation();
                                e.stopPropagation();

                                var targetSelector = btn.getAttribute("data-wpfaq-target");
                                if (!targetSelector) return false;
                                var collapse = this.querySelector(targetSelector);
                                if (!collapse) return false;

                                var accordion = btn.closest(".accordion");
                                if (accordion) {
                                    accordion.querySelectorAll(".accordion-collapse.show").forEach(function(openEl) {
                                        if (openEl !== collapse) {
                                            openEl.classList.remove("show");
                                            openEl.setAttribute("aria-expanded", "false");
                                            var openId = openEl.id;
                                            if (openId) {
                                                var assocBtn = accordion.querySelector("[data-wpfaq-target=\"#" + openId + "\"]");
                                                if (assocBtn) {
                                                    assocBtn.classList.add("collapsed");
                                                    assocBtn.setAttribute("aria-expanded", "false");
                                                }
                                            }
                                            // Fallback display control when theme CSS overrides Bootstrap collapse
                                            if (getComputedStyle(openEl).display !== "none") {
                                                openEl.style.display = "none";
                                            }
                                        }
                                    });
                                }

                                if (collapse.classList.contains("show")) {
                                    collapse.classList.remove("show");
                                    collapse.setAttribute("aria-expanded", "false");
                                    btn.classList.add("collapsed");
                                    btn.setAttribute("aria-expanded", "false");
                                    if (getComputedStyle(collapse).display !== "none") {
                                        collapse.style.display = "none";
                                    }
                                } else {
                                    collapse.classList.add("show");
                                    collapse.setAttribute("aria-expanded", "true");
                                    btn.classList.remove("collapsed");
                                    btn.setAttribute("aria-expanded", "true");
                                    collapse.style.display = "block";
                                }
                                return false;
                            };
                        // Bind to each container instance to support multiple shortcodes on a page
                        $container.each(function() {
                            this.addEventListener("click", handleAccordionClick, true);
                        });
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
    register_setting('wp_custom_faq_settings_group', 'wp_custom_faq_answer_bg', [
        'default' => '',
        'sanitize_callback' => 'wp_custom_faq_sanitize_optional_hex'
    ]);
}
add_action('admin_init', 'wp_custom_faq_register_options');

// Allow empty string or valid hex color
function wp_custom_faq_sanitize_optional_hex($value) {
    $value = is_string($value) ? trim($value) : '';
    if ($value === '') {
        return '';
    }
    $hex = sanitize_hex_color($value);
    return $hex ? $hex : '';
}

// Sanitize FAQ items
function wp_custom_faq_sanitize_items($input) {
    $sanitized = [];
    if (is_array($input)) {
        foreach ($input as $item) {
            if (!empty($item['question']) && !empty($item['answer'])) {
                $question_raw = (string) $item['question'];
                $question_normalized = preg_replace("/[\r\n]+/", ' ', $question_raw);
                $question_normalized = preg_replace('/\s{2,}/', ' ', $question_normalized);
                $sanitized[] = [
                    'question' => wp_kses($question_normalized, wp_custom_faq_allowed_question_tags()),
                    'answer' => wp_kses_post((string) $item['answer'])
                ];
            }
        }
    }
    return $sanitized;
}

// Allowed inline tags for question content (inside the accordion button)
function wp_custom_faq_allowed_question_tags() {
    return [
        'br' => [],
        'em' => [],
        'strong' => [],
        'b' => [],
        'i' => [],
        'u' => [],
        'small' => [],
        'span' => [
            'style' => true,
            'class' => true,
        ],
    ];
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
            $answer_bg = get_option('wp_custom_faq_answer_bg', '');
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
                                <?php
                                $question_editor_id = 'wp_custom_faq_question_' . intval($index);
                                wp_editor(
                                    (string) $item['question'],
                                    $question_editor_id,
                                    [
                                        'textarea_name' => 'wp_custom_faq_items[' . intval($index) . '][question]',
                                        'textarea_rows' => 3,
                                        'media_buttons' => false,
                                        'quicktags' => [ 'buttons' => 'strong,em' ],
                                        'tinymce' => [
                                            'toolbar1' => 'bold,italic,undo,redo',
                                            'menubar' => false,
                                            'wpautop' => false,
                                        ],
                                        'teeny' => true,
                                    ]
                                );
                                ?>
                            </p>
                            <p>
                                <label>Answer:</label><br>
                                <?php
                                $answer_editor_id = 'wp_custom_faq_answer_' . intval($index);
                                wp_editor(
                                    (string) $item['answer'],
                                    $answer_editor_id,
                                    [
                                        'textarea_name' => 'wp_custom_faq_items[' . intval($index) . '][answer]',
                                        'textarea_rows' => 7,
                                        'media_buttons' => false,
                                        'quicktags' => true,
                                        'tinymce' => [
                                            'toolbar1' => 'bold,italic,bullist,numlist,undo,redo,link,unlink',
                                            'menubar' => false,
                                        ],
                                    ]
                                );
                                ?>
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
            <p>
                <label>Answer Background Color (optional):</label>
                <input type="text" name="wp_custom_faq_answer_bg" value="<?php echo esc_attr($answer_bg); ?>" class="color-field" placeholder="#ffffff or leave empty">
                <br><small>Leave empty to keep default background.</small>
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
                var qId = 'wp_custom_faq_question_' + index;
                var aId = 'wp_custom_faq_answer_' + index;
                var html = '<div class="faq-item">' +
                    '<p><label>Question:</label><br>' +
                    '<textarea id="' + qId + '" name="wp_custom_faq_items[' + index + '][question]"></textarea></p>' +
                    '<p><label>Answer:</label><br>' +
                    '<textarea id="' + aId + '" name="wp_custom_faq_items[' + index + '][answer]"></textarea></p>' +
                    '<button type="button" class="remove-faq button">Remove</button><hr></div>';
                $('#faq-items').append(html);
                if (window.wp && wp.editor && typeof wp.editor.initialize === 'function') {
                    wp.editor.initialize(qId, {
                        tinymce: { toolbar1: 'bold italic undo redo', menubar: false, wpautop: false },
                        quicktags: { buttons: 'strong,em' },
                        mediaButtons: false
                    });
                    wp.editor.initialize(aId, {
                        tinymce: { toolbar1: 'bold italic bullist numlist undo redo link unlink', menubar: false },
                        quicktags: true,
                        mediaButtons: false
                    });
                }
            });

            // Remove FAQ item
            $(document).on('click', '.remove-faq', function() {
                $(this).closest('.faq-item').remove();
            });

            // Initialize color picker (guarded to avoid conflicts)
            if ($.fn && typeof $.fn.wpColorPicker === 'function') {
                $('.color-field').wpColorPicker();
            }
        });
    </script>
    <?php
}

// Enqueue color picker for admin
function wp_custom_faq_admin_assets($hook) {
    if ($hook === 'settings_page_wp-custom-faq') {
        wp_enqueue_style('wp-color-picker');
        wp_enqueue_script('wp-color-picker');
    }
}
add_action('admin_enqueue_scripts', 'wp_custom_faq_admin_assets');

// Shortcode to display FAQs
function wp_custom_faq_shortcode() {
    $faq_items = get_option('wp_custom_faq_items', []);
    $accordion_id = 'wpCustomFaqAccordion' . wp_generate_uuid4();
    ob_start();
    ?>
    <div class="wp-custom-faq-wrapper tw-w-full">
        <div class="wp-custom-faq-container tw-max-w-3xl tw-mx-auto tw-space-y-2">
            <div class="container">
                <div class="accordion" id="<?php echo esc_attr($accordion_id); ?>">
                    <?php
                    foreach ($faq_items as $index => $item) {
                        $unique_id = wp_generate_uuid4();
                        ?>
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="heading<?php echo esc_attr($unique_id); ?>">
                                <button class="accordion-button collapsed tw-text-base tw-font-semibold tw-pr-10" type="button"
                                    data-wpfaq-target="#collapse<?php echo esc_attr($unique_id); ?>" aria-expanded="false"
                                    aria-controls="collapse<?php echo esc_attr($unique_id); ?>">
                                    <?php
                                        $q = (string) $item['question'];
                                        // Prevent whitespace collapse around inline tags
                                        $q = preg_replace('/\s*<\/(strong|b|em|i|u)>\s*/i', ' </$1> ', $q);
                                        $q = preg_replace('/\s*<(strong|b|em|i|u)([^>]*)>\s*/i', ' <$1$2> ', $q);
                                        echo wp_kses($q, wp_custom_faq_allowed_question_tags());
                                    ?>
                                </button>
                            </h2>
                            <div id="collapse<?php echo esc_attr($unique_id); ?>" class="accordion-collapse collapse"
                                aria-labelledby="heading<?php echo esc_attr($unique_id); ?>">
                                <div class="accordion-body tw-text-gray-900 tw-leading-relaxed">
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
