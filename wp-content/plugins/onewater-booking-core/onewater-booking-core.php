<?php
/**
 * Plugin Name: One Water Booking Core
 * Description: Booking, availability, admin controls, and REST API foundation for One Water West Stay.
 * Version: 0.1.0
 * Requires at least: 6.4
 * Requires PHP: 8.1
 * Author: One Water West Stay
 * Text Domain: onewater-booking-core
 */

if (!defined('ABSPATH')) {
    exit;
}

final class OneWater_Booking_Core
{
    private const OPTION_KEY = 'onewater_booking_settings';
    private const RESERVATION_TYPE = 'ows_reservation';
    private const BLOCK_TYPE = 'ows_block';
    private const API_NAMESPACE = 'onewater/v1';

    public static function boot(): void
    {
        add_action('init', [self::class, 'register_post_types']);
        add_action('admin_menu', [self::class, 'register_admin_menu']);
        add_action('admin_init', [self::class, 'register_settings']);
        add_action('add_meta_boxes', [self::class, 'register_meta_boxes']);
        add_action('save_post_' . self::RESERVATION_TYPE, [self::class, 'save_reservation_meta']);
        add_action('save_post_' . self::BLOCK_TYPE, [self::class, 'save_block_meta']);
        add_action('rest_api_init', [self::class, 'register_rest_routes']);
        add_action('wp_enqueue_scripts', [self::class, 'register_assets']);
        add_shortcode('onewater_booking_calendar', [self::class, 'render_booking_calendar']);
        register_activation_hook(__FILE__, [self::class, 'activate']);
    }

    public static function activate(): void
    {
        self::register_post_types();
        if (!get_option(self::OPTION_KEY)) {
            add_option(self::OPTION_KEY, [
                'minimum_notice_days' => 14,
                'allow_any_start_day' => '1',
                'deposit_amount' => '2500',
                'monthly_rate' => '12000',
                'chat_provider' => 'Intercom, Crisp, Zendesk, or Twilio Conversations',
            ]);
        }
        flush_rewrite_rules();
    }

    public static function register_post_types(): void
    {
        register_post_type(self::RESERVATION_TYPE, [
            'labels' => [
                'name' => __('Reservations', 'onewater-booking-core'),
                'singular_name' => __('Reservation', 'onewater-booking-core'),
            ],
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => true,
            'supports' => ['title'],
            'menu_icon' => 'dashicons-calendar-alt',
        ]);

        register_post_type(self::BLOCK_TYPE, [
            'labels' => [
                'name' => __('Blocked Dates', 'onewater-booking-core'),
                'singular_name' => __('Blocked Date Range', 'onewater-booking-core'),
            ],
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => 'edit.php?post_type=' . self::RESERVATION_TYPE,
            'supports' => ['title'],
            'menu_icon' => 'dashicons-dismiss',
        ]);
    }

    public static function register_admin_menu(): void
    {
        add_submenu_page(
            'edit.php?post_type=' . self::RESERVATION_TYPE,
            __('Booking Controls', 'onewater-booking-core'),
            __('Booking Controls', 'onewater-booking-core'),
            'manage_options',
            'onewater-booking-controls',
            [self::class, 'render_settings_page']
        );
    }

    public static function register_settings(): void
    {
        register_setting('onewater_booking_settings', self::OPTION_KEY, [
            'type' => 'array',
            'sanitize_callback' => [self::class, 'sanitize_settings'],
            'default' => [],
        ]);
    }

    public static function sanitize_settings($settings): array
    {
        $settings = is_array($settings) ? $settings : [];

        return [
            'minimum_notice_days' => max(0, absint($settings['minimum_notice_days'] ?? 14)),
            'allow_any_start_day' => !empty($settings['allow_any_start_day']) ? '1' : '0',
            'deposit_amount' => sanitize_text_field((string) ($settings['deposit_amount'] ?? '2500')),
            'monthly_rate' => sanitize_text_field((string) ($settings['monthly_rate'] ?? '12000')),
            'chat_provider' => sanitize_text_field((string) ($settings['chat_provider'] ?? '')),
        ];
    }

    public static function register_meta_boxes(): void
    {
        add_meta_box(
            'ows_reservation_details',
            __('Reservation Details', 'onewater-booking-core'),
            [self::class, 'render_reservation_meta_box'],
            self::RESERVATION_TYPE,
            'normal',
            'high'
        );

        add_meta_box(
            'ows_block_details',
            __('Blocked Date Details', 'onewater-booking-core'),
            [self::class, 'render_block_meta_box'],
            self::BLOCK_TYPE,
            'normal',
            'high'
        );
    }

    public static function render_reservation_meta_box(WP_Post $post): void
    {
        wp_nonce_field('ows_save_reservation', 'ows_reservation_nonce');
        $fields = self::reservation_fields($post->ID);
        ?>
        <div class="ows-admin-grid">
            <?php foreach ($fields as $key => $field) : ?>
                <p>
                    <label for="<?php echo esc_attr($key); ?>"><strong><?php echo esc_html($field['label']); ?></strong></label><br>
                    <?php if (($field['type'] ?? 'text') === 'select') : ?>
                        <select id="<?php echo esc_attr($key); ?>" name="<?php echo esc_attr($key); ?>">
                            <?php foreach ($field['options'] as $value => $label) : ?>
                                <option value="<?php echo esc_attr($value); ?>" <?php selected($field['value'], $value); ?>><?php echo esc_html($label); ?></option>
                            <?php endforeach; ?>
                        </select>
                    <?php else : ?>
                        <input id="<?php echo esc_attr($key); ?>" name="<?php echo esc_attr($key); ?>" type="<?php echo esc_attr($field['type'] ?? 'text'); ?>" value="<?php echo esc_attr($field['value']); ?>" class="widefat">
                    <?php endif; ?>
                </p>
            <?php endforeach; ?>
        </div>
        <?php
    }

    public static function render_block_meta_box(WP_Post $post): void
    {
        wp_nonce_field('ows_save_block', 'ows_block_nonce');
        $start = get_post_meta($post->ID, '_ows_start_date', true);
        $end = get_post_meta($post->ID, '_ows_end_date', true);
        $reason = get_post_meta($post->ID, '_ows_reason', true);
        ?>
        <p><label><strong><?php esc_html_e('Start date', 'onewater-booking-core'); ?></strong><br><input type="date" name="ows_start_date" value="<?php echo esc_attr($start); ?>"></label></p>
        <p><label><strong><?php esc_html_e('End date', 'onewater-booking-core'); ?></strong><br><input type="date" name="ows_end_date" value="<?php echo esc_attr($end); ?>"></label></p>
        <p><label><strong><?php esc_html_e('Reason', 'onewater-booking-core'); ?></strong><br><input class="widefat" type="text" name="ows_reason" value="<?php echo esc_attr($reason); ?>"></label></p>
        <?php
    }

    private static function reservation_fields(int $post_id): array
    {
        return [
            'ows_renter_name' => ['label' => 'Renter name', 'value' => get_post_meta($post_id, '_ows_renter_name', true)],
            'ows_renter_email' => ['label' => 'Renter email', 'type' => 'email', 'value' => get_post_meta($post_id, '_ows_renter_email', true)],
            'ows_renter_phone' => ['label' => 'Renter phone', 'value' => get_post_meta($post_id, '_ows_renter_phone', true)],
            'ows_start_date' => ['label' => 'Start date', 'type' => 'date', 'value' => get_post_meta($post_id, '_ows_start_date', true)],
            'ows_end_date' => ['label' => 'End date', 'type' => 'date', 'value' => get_post_meta($post_id, '_ows_end_date', true)],
            'ows_status' => [
                'label' => 'Reservation status',
                'type' => 'select',
                'value' => get_post_meta($post_id, '_ows_status', true) ?: 'pending_request',
                'options' => self::reservation_statuses(),
            ],
            'ows_payment_status' => [
                'label' => 'Payment status',
                'type' => 'select',
                'value' => get_post_meta($post_id, '_ows_payment_status', true) ?: 'not_started',
                'options' => [
                    'not_started' => 'Not started',
                    'deposit_due' => 'Deposit due',
                    'deposit_paid' => 'Deposit paid',
                    'paid' => 'Paid',
                    'refunded' => 'Refunded',
                ],
            ],
            'ows_lease_status' => [
                'label' => 'BC lease status',
                'type' => 'select',
                'value' => get_post_meta($post_id, '_ows_lease_status', true) ?: 'required',
                'options' => [
                    'required' => 'Required',
                    'sent' => 'Sent',
                    'signed' => 'Signed',
                    'waived' => 'Waived by manager',
                ],
            ],
        ];
    }

    private static function reservation_statuses(): array
    {
        return [
            'pending_request' => 'Pending request',
            'pending_payment' => 'Pending payment',
            'confirmed' => 'Confirmed',
            'owner_blocked' => 'Owner blocked',
            'cancelled' => 'Cancelled',
        ];
    }

    public static function save_reservation_meta(int $post_id): void
    {
        if (!isset($_POST['ows_reservation_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['ows_reservation_nonce'])), 'ows_save_reservation')) {
            return;
        }

        foreach (array_keys(self::reservation_fields($post_id)) as $field) {
            if (isset($_POST[$field])) {
                update_post_meta($post_id, '_' . $field, sanitize_text_field(wp_unslash($_POST[$field])));
            }
        }
    }

    public static function save_block_meta(int $post_id): void
    {
        if (!isset($_POST['ows_block_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['ows_block_nonce'])), 'ows_save_block')) {
            return;
        }

        foreach (['ows_start_date', 'ows_end_date', 'ows_reason'] as $field) {
            if (isset($_POST[$field])) {
                update_post_meta($post_id, '_' . $field, sanitize_text_field(wp_unslash($_POST[$field])));
            }
        }
    }

    public static function render_settings_page(): void
    {
        $settings = self::settings();
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('One Water Booking Controls', 'onewater-booking-core'); ?></h1>
            <p><?php esc_html_e('Operational controls for exact 3-month seasonal rentals.', 'onewater-booking-core'); ?></p>
            <form method="post" action="options.php">
                <?php settings_fields('onewater_booking_settings'); ?>
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><label for="minimum_notice_days">Minimum notice days</label></th>
                        <td><input id="minimum_notice_days" type="number" min="0" name="<?php echo esc_attr(self::OPTION_KEY); ?>[minimum_notice_days]" value="<?php echo esc_attr($settings['minimum_notice_days']); ?>"></td>
                    </tr>
                    <tr>
                        <th scope="row">Allow any start day</th>
                        <td><label><input type="checkbox" name="<?php echo esc_attr(self::OPTION_KEY); ?>[allow_any_start_day]" value="1" <?php checked($settings['allow_any_start_day'], '1'); ?>> Default policy is any-day starts.</label></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="deposit_amount">Stripe deposit amount</label></th>
                        <td><input id="deposit_amount" type="text" name="<?php echo esc_attr(self::OPTION_KEY); ?>[deposit_amount]" value="<?php echo esc_attr($settings['deposit_amount']); ?>"></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="monthly_rate">Monthly rental rate</label></th>
                        <td><input id="monthly_rate" type="text" name="<?php echo esc_attr(self::OPTION_KEY); ?>[monthly_rate]" value="<?php echo esc_attr($settings['monthly_rate']); ?>"></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="chat_provider">Chat provider requirement</label></th>
                        <td><input id="chat_provider" class="regular-text" type="text" name="<?php echo esc_attr(self::OPTION_KEY); ?>[chat_provider]" value="<?php echo esc_attr($settings['chat_provider']); ?>"></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    public static function register_rest_routes(): void
    {
        register_rest_route(self::API_NAMESPACE, '/availability', [
            'methods' => WP_REST_Server::READABLE,
            'permission_callback' => '__return_true',
            'callback' => [self::class, 'rest_availability'],
        ]);

        register_rest_route(self::API_NAMESPACE, '/reservations', [
            'methods' => WP_REST_Server::CREATABLE,
            'permission_callback' => '__return_true',
            'callback' => [self::class, 'rest_create_reservation'],
        ]);

        register_rest_route(self::API_NAMESPACE, '/reservations/(?P<id>\d+)', [
            'methods' => WP_REST_Server::READABLE,
            'permission_callback' => [self::class, 'can_read_reservation'],
            'callback' => [self::class, 'rest_get_reservation'],
        ]);

        register_rest_route(self::API_NAMESPACE, '/chat-handoff', [
            'methods' => WP_REST_Server::CREATABLE,
            'permission_callback' => '__return_true',
            'callback' => [self::class, 'rest_chat_handoff'],
        ]);
    }

    public static function register_assets(): void
    {
        wp_register_script(
            'onewater-booking-calendar',
            plugins_url('assets/booking-calendar.js', __FILE__),
            [],
            '0.3.4',
            true
        );

        wp_register_style(
            'onewater-booking-calendar',
            plugins_url('assets/booking-calendar.css', __FILE__),
            [],
            '0.3.1'
        );
    }

    public static function render_booking_calendar(): string
    {
        // Block themes render `the_content` before `wp_enqueue_scripts` fires, so the
        // handles may not be registered yet. Register here to ensure localize succeeds.
        self::register_assets();

        wp_enqueue_script('onewater-booking-calendar');
        wp_enqueue_style('onewater-booking-calendar');

        wp_localize_script('onewater-booking-calendar', 'oneWaterBooking', [
            'apiBase' => esc_url_raw(rest_url(self::API_NAMESPACE)),
            'nonce' => wp_create_nonce('wp_rest'),
            'settings' => self::settings(),
        ]);

        ob_start();
        ?>
        <section class="ows-booking" data-onewater-booking>
            <p class="ows-kicker">Request-to-book</p>
            <p>Select any available start date. The checkout date is calculated exactly 3 months later.</p>
            <div class="ows-booking__toolbar">
                <button type="button" data-calendar-prev aria-label="Previous months">Previous</button>
                <strong data-calendar-label></strong>
                <button type="button" data-calendar-next aria-label="Next months">Next</button>
            </div>
            <div class="ows-booking__months" data-calendar-months></div>
            <p data-calendar-selection class="ows-booking__selection">Choose a start date to see the exact 3-month period.</p>
            <form class="ows-form" data-booking-form>
                <input type="hidden" name="start_date" data-start-date>
                <label>Name <input required name="name" autocomplete="name"></label>
                <label>Email <input required type="email" name="email" autocomplete="email"></label>
                <label>Phone <input name="phone" autocomplete="tel"></label>
                <label>Message <textarea name="message" rows="3" placeholder="Tell us about your seasonal stay."></textarea></label>
                <button type="submit">Submit Booking Request</button>
                <p class="ows-booking__notice">Final confirmation requires manager approval, Stripe deposit/payment, and a signed BC rental agreement.</p>
                <output data-booking-result></output>
            </form>
        </section>
        <?php
        return (string) ob_get_clean();
    }

    public static function rest_availability(WP_REST_Request $request): WP_REST_Response
    {
        $month = sanitize_text_field((string) $request->get_param('month'));
        $start = self::parse_date($month ? $month . '-01' : gmdate('Y-m-01'));
        if (!$start) {
            return new WP_REST_Response(['message' => 'Invalid month. Use YYYY-MM.'], 400);
        }

        $days = [];
        $cursor = $start->modify('first day of this month');
        $end = $cursor->modify('last day of this month');

        while ($cursor <= $end) {
            $start_date = $cursor->format('Y-m-d');
            $checkout = self::checkout_for_start($cursor);
            $availability = self::is_period_available($cursor, $checkout);
            $days[] = [
                'date' => $start_date,
                'checkout_date' => $checkout->format('Y-m-d'),
                'available' => $availability['available'],
                'reason' => $availability['reason'],
            ];
            $cursor = $cursor->modify('+1 day');
        }

        return new WP_REST_Response([
            'month' => $start->format('Y-m'),
            'minimum_notice_days' => self::settings()['minimum_notice_days'],
            'days' => $days,
        ]);
    }

    public static function rest_create_reservation(WP_REST_Request $request): WP_REST_Response
    {
        $start = self::parse_date((string) $request->get_param('start_date'));
        if (!$start) {
            return new WP_REST_Response(['message' => 'A valid start_date is required.'], 400);
        }

        $checkout = self::checkout_for_start($start);
        $availability = self::is_period_available($start, $checkout);
        if (!$availability['available']) {
            return new WP_REST_Response(['message' => 'Selected 3-month period is not available.', 'reason' => $availability['reason']], 409);
        }

        $name = sanitize_text_field((string) $request->get_param('name'));
        $email = sanitize_email((string) $request->get_param('email'));
        $phone = sanitize_text_field((string) $request->get_param('phone'));
        $message = sanitize_textarea_field((string) $request->get_param('message'));

        if (!$name || !$email) {
            return new WP_REST_Response(['message' => 'Name and email are required.'], 400);
        }

        $reservation_id = wp_insert_post([
            'post_type' => self::RESERVATION_TYPE,
            'post_status' => 'publish',
            'post_title' => sprintf('%s: %s to %s', $name, $start->format('Y-m-d'), $checkout->format('Y-m-d')),
        ], true);

        if (is_wp_error($reservation_id)) {
            return new WP_REST_Response(['message' => $reservation_id->get_error_message()], 500);
        }

        update_post_meta($reservation_id, '_ows_renter_name', $name);
        update_post_meta($reservation_id, '_ows_renter_email', $email);
        update_post_meta($reservation_id, '_ows_renter_phone', $phone);
        update_post_meta($reservation_id, '_ows_start_date', $start->format('Y-m-d'));
        update_post_meta($reservation_id, '_ows_end_date', $checkout->format('Y-m-d'));
        update_post_meta($reservation_id, '_ows_status', 'pending_request');
        update_post_meta($reservation_id, '_ows_payment_status', 'not_started');
        update_post_meta($reservation_id, '_ows_lease_status', 'required');
        update_post_meta($reservation_id, '_ows_message', $message);

        self::notify_manager($reservation_id);

        return new WP_REST_Response([
            'id' => $reservation_id,
            'status' => 'pending_request',
            'payment_status' => 'not_started',
            'lease_status' => 'required',
            'start_date' => $start->format('Y-m-d'),
            'checkout_date' => $checkout->format('Y-m-d'),
            'message' => 'Booking request received. A manager will review availability, payment, and BC lease requirements.',
        ], 201);
    }

    public static function rest_get_reservation(WP_REST_Request $request): WP_REST_Response
    {
        $id = absint($request['id']);
        return new WP_REST_Response(self::reservation_payload($id));
    }

    public static function rest_chat_handoff(WP_REST_Request $request): WP_REST_Response
    {
        return new WP_REST_Response([
            'provider_requirement' => self::settings()['chat_provider'],
            'status' => 'ready_for_provider_integration',
            'message' => 'Connect the selected provider web widget now and mobile SDK during the future app phase.',
            'visitor' => [
                'name' => sanitize_text_field((string) $request->get_param('name')),
                'email' => sanitize_email((string) $request->get_param('email')),
            ],
        ]);
    }

    public static function can_read_reservation(): bool
    {
        return current_user_can('edit_posts');
    }

    private static function settings(): array
    {
        return wp_parse_args((array) get_option(self::OPTION_KEY, []), [
            'minimum_notice_days' => 14,
            'allow_any_start_day' => '1',
            'deposit_amount' => '2500',
            'monthly_rate' => '12000',
            'chat_provider' => 'Web chat, mobile SDKs, manager assignment, persistent history, push notifications',
        ]);
    }

    private static function parse_date(string $date): ?DateTimeImmutable
    {
        $parsed = DateTimeImmutable::createFromFormat('!Y-m-d', $date, wp_timezone());
        return $parsed ?: null;
    }

    private static function checkout_for_start(DateTimeImmutable $start): DateTimeImmutable
    {
        return $start->modify('+3 months');
    }

    private static function is_period_available(DateTimeImmutable $start, DateTimeImmutable $checkout): array
    {
        $settings = self::settings();
        $notice_date = (new DateTimeImmutable('today', wp_timezone()))->modify('+' . absint($settings['minimum_notice_days']) . ' days');

        if ($start < $notice_date) {
            return ['available' => false, 'reason' => 'minimum_notice'];
        }

        foreach (self::occupied_ranges() as $range) {
            if (self::ranges_overlap($start, $checkout, $range['start'], $range['end'])) {
                return ['available' => false, 'reason' => $range['type']];
            }
        }

        return ['available' => true, 'reason' => null];
    }

    private static function ranges_overlap(DateTimeImmutable $start_a, DateTimeImmutable $end_a, DateTimeImmutable $start_b, DateTimeImmutable $end_b): bool
    {
        return $start_a < $end_b && $start_b < $end_a;
    }

    private static function occupied_ranges(): array
    {
        $ranges = [];
        $posts = get_posts([
            'post_type' => [self::RESERVATION_TYPE, self::BLOCK_TYPE],
            'post_status' => 'publish',
            'numberposts' => -1,
        ]);

        foreach ($posts as $post) {
            $status = get_post_meta($post->ID, '_ows_status', true);
            if ($post->post_type === self::RESERVATION_TYPE && in_array($status, ['cancelled'], true)) {
                continue;
            }

            $start = self::parse_date((string) get_post_meta($post->ID, '_ows_start_date', true));
            $end = self::parse_date((string) get_post_meta($post->ID, '_ows_end_date', true));
            if ($start && $end) {
                $ranges[] = [
                    'start' => $start,
                    'end' => $end,
                    'type' => $post->post_type === self::BLOCK_TYPE ? 'owner_blocked' : ($status ?: 'reservation'),
                ];
            }
        }

        return $ranges;
    }

    private static function reservation_payload(int $id): array
    {
        return [
            'id' => $id,
            'status' => get_post_meta($id, '_ows_status', true),
            'payment_status' => get_post_meta($id, '_ows_payment_status', true),
            'lease_status' => get_post_meta($id, '_ows_lease_status', true),
            'start_date' => get_post_meta($id, '_ows_start_date', true),
            'checkout_date' => get_post_meta($id, '_ows_end_date', true),
            'renter' => [
                'name' => get_post_meta($id, '_ows_renter_name', true),
                'email' => get_post_meta($id, '_ows_renter_email', true),
                'phone' => get_post_meta($id, '_ows_renter_phone', true),
            ],
        ];
    }

    private static function notify_manager(int $reservation_id): void
    {
        $admin_email = get_option('admin_email');
        if (!$admin_email) {
            return;
        }

        wp_mail(
            $admin_email,
            'New One Water West Stay booking request',
            'A new 3-month booking request is ready for manager review. Reservation ID: ' . $reservation_id
        );
    }
}

OneWater_Booking_Core::boot();
