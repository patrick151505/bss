@if($errors->any())
<div class="mb-4 p-4 rounded-lg bg-danger/10 border border-danger/30">
    <ul class="text-sm text-danger list-disc pl-4 space-y-1">
        @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
    </ul>
</div>
@endif

@php
$colorList = [
    'primary'   => ['label' => 'Blue',   'hex' => '#4361ee'],
    'success'   => ['label' => 'Green',  'hex' => '#0acf97'],
    'warning'   => ['label' => 'Yellow', 'hex' => '#ffbc00'],
    'danger'    => ['label' => 'Red',    'hex' => '#fa5c7c'],
    'info'      => ['label' => 'Cyan',   'hex' => '#39afd1'],
    'secondary' => ['label' => 'Gray',   'hex' => '#6c757d'],
    'dark'      => ['label' => 'Dark',   'hex' => '#313a46'],
];
$currentIcon  = old('icon',  $category->icon  ?? 'mgc_box_line');
$savedColor   = old('color', $category->color ?? 'primary');
$isCustom     = !array_key_exists($savedColor, $colorList);
$currentColor = $isCustom ? 'custom' : $savedColor;
$customHex    = $isCustom ? $savedColor : '#4361ee';
@endphp

<div class="space-y-4">
    <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Name <span class="text-danger">*</span></label>
        <input type="text" name="name" id="cat-name-input" value="{{ old('name', $category->name ?? '') }}"
               class="form-input w-full @error('name') border-danger @enderror" required>
        @error('name')<p class="text-xs text-danger mt-1">{{ $message }}</p>@enderror
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Description</label>
        <textarea name="description" rows="2" class="form-input w-full">{{ old('description', $category->description ?? '') }}</textarea>
    </div>

    {{-- ── Icon Picker ─────────────────────────────────────────────── --}}
    <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Icon</label>
        <input type="hidden" name="icon" id="icon-value" value="{{ $currentIcon }}">

        {{-- Trigger --}}
        <button type="button" id="icon-trigger"
                class="flex items-center gap-3 w-full px-3 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 hover:border-primary/60 transition text-left">
            <span id="icon-preview-wrap" class="w-8 h-8 rounded-lg flex items-center justify-center shrink-0 bg-primary/10">
                <i id="icon-preview-icon" class="{{ $currentIcon }} text-primary text-lg"></i>
            </span>
            <span id="icon-preview-name" class="text-sm text-gray-700 dark:text-gray-300 flex-1 font-mono truncate">{{ $currentIcon }}</span>
            <i class="mgc_down_line text-gray-400 text-sm shrink-0"></i>
        </button>

        {{-- Dropdown panel --}}
        <div id="icon-dropdown"
             class="hidden mt-1 border border-gray-200 dark:border-gray-700 rounded-xl shadow-xl bg-white dark:bg-gray-800 p-3"
             style="z-index:999; position:relative;">
            <input type="text" id="icon-search" class="form-input w-full mb-3 text-sm" placeholder="Search 1106 icons…" autocomplete="off">
            <div id="icon-count" class="text-xs text-gray-400 mb-2"></div>
            <div id="icon-grid" class="grid grid-cols-10 gap-1 max-h-64 overflow-y-auto"></div>
            <p class="text-xs text-gray-400 mt-2 text-center" id="icon-more-hint"></p>
        </div>
    </div>

    {{-- ── Color Picker ────────────────────────────────────────────── --}}
    <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Color</label>
        <input type="hidden" name="color" id="color-value" value="{{ $isCustom ? $customHex : $savedColor }}">

        <div class="flex flex-wrap gap-2 items-center">
            {{-- Named swatches --}}
            @foreach($colorList as $key => $meta)
            <button type="button" data-color="{{ $key }}" data-hex="{{ $meta['hex'] }}" title="{{ $meta['label'] }}"
                    onclick="pickColor('{{ $key }}', '{{ $meta['hex'] }}')"
                    class="color-swatch w-8 h-8 rounded-full border-[3px] transition hover:scale-110 focus:outline-none
                           {{ !$isCustom && $savedColor === $key ? 'border-gray-800 dark:border-white scale-110' : 'border-transparent' }}"
                    style="background:{{ $meta['hex'] }}">
            </button>
            @endforeach

            {{-- Custom hex swatch --}}
            <label title="Custom color"
                   class="color-swatch relative w-8 h-8 rounded-full border-[3px] flex items-center justify-center cursor-pointer transition hover:scale-110
                          {{ $isCustom ? 'border-gray-800 dark:border-white scale-110' : 'border-dashed border-gray-400' }}"
                   id="custom-swatch-label"
                   style="{{ $isCustom ? 'background:'.$customHex : 'background: conic-gradient(red,yellow,lime,cyan,blue,magenta,red)' }}">
                <i class="mgc_color_picker_line text-white text-xs drop-shadow pointer-events-none"></i>
                <input type="color" id="custom-color-input" value="{{ $isCustom ? $customHex : '#4361ee' }}"
                       class="absolute inset-0 opacity-0 w-full h-full rounded-full cursor-pointer"
                       oninput="pickCustomColor(this.value)"
                       onchange="pickCustomColor(this.value)">
            </label>
        </div>
        <p class="text-xs text-gray-400 mt-1.5" id="color-label-text">
            {{ $isCustom ? 'Custom: '.$customHex : ucfirst($savedColor) }}
        </p>
    </div>

    {{-- ── Live preview ─────────────────────────────────────────────── --}}
    <div class="rounded-xl border border-gray-200 dark:border-gray-700 p-4">
        <p class="text-xs text-gray-400 uppercase tracking-wide mb-3">Preview</p>
        <div class="flex items-center gap-3">
            <div id="preview-badge" class="w-10 h-10 rounded-lg flex items-center justify-center shrink-0 bg-primary/10">
                <i id="preview-icon" class="{{ $currentIcon }} text-primary text-lg"></i>
            </div>
            <div>
                <p class="text-sm font-semibold text-gray-800 dark:text-gray-100" id="preview-name">{{ old('name', $category->name ?? 'Category Name') }}</p>
                <p class="text-xs text-gray-400" id="preview-color-text">{{ $isCustom ? 'Custom: '.$customHex : ucfirst($savedColor) }}</p>
            </div>
        </div>
    </div>

    <div class="flex items-center gap-2">
        <input type="checkbox" name="is_active" id="is_active" value="1"
               {{ old('is_active', ($category->is_active ?? true) ? '1' : '0') === '1' ? 'checked' : '' }}
               class="form-checkbox">
        <label for="is_active" class="text-sm text-gray-700 dark:text-gray-300">Active</label>
    </div>
</div>

@push('inline-scripts')
<script>
// ── Full MingCute icon list (1106 _line variants) ─────────────────────────
const ALL_ICONS = ["mgc_ABS_line","mgc_AZ_sort_ascending_letters_line","mgc_BNB_line","mgc_Heading_1_line","mgc_Heading_2_line","mgc_Heading_3_line","mgc_IDcard_line","mgc_TV_towe_line","mgc_UFO_line","mgc_VIP_1_line","mgc_VIP_2_line","mgc_VIP_3_line","mgc_VIP_4_line","mgc_XRP_line","mgc_ZA_sort_descending_letters_line","mgc_add_circle_line","mgc_add_line","mgc_aerial_lift_line","mgc_aiming_2_line","mgc_aiming_line","mgc_air_balloon_line","mgc_air_condition_line","mgc_air_condition_open_line","mgc_airbnb_line","mgc_airplane_line","mgc_airplay_line","mgc_airpods_2_line","mgc_airpods_line","mgc_alarm_1_line","mgc_alarm_2_line","mgc_album_2_line","mgc_album_line","mgc_alert_line","mgc_alert_octagon_line","mgc_align_arrow_down_line","mgc_align_arrow_left_line","mgc_align_arrow_right_line","mgc_align_arrow_up_line","mgc_align_bottom_line","mgc_align_center_line","mgc_align_horizontal_center_line","mgc_align_justify_line","mgc_align_left_2_line","mgc_align_left_line","mgc_align_right_2_line","mgc_align_right_line","mgc_align_top_line","mgc_align_vertical_center_line","mgc_alipay_line","mgc_american_football_line","mgc_anchor_line","mgc_and_line","mgc_android_2_line","mgc_android_line","mgc_anniversary_line","mgc_announcement_line","mgc_anticlockwise_alt_line","mgc_anticlockwise_line","mgc_apple_line","mgc_appstore_line","mgc_archive_line","mgc_arow_to_down_line","mgc_arow_to_left_line","mgc_arow_to_right_line","mgc_arow_to_up_line","mgc_arrow_down_circle_line","mgc_arrow_down_line","mgc_arrow_left_circle_line","mgc_arrow_left_down_circle_line","mgc_arrow_left_down_line","mgc_arrow_left_line","mgc_arrow_left_up_circle_line","mgc_arrow_left_up_line","mgc_arrow_right_circle_line","mgc_arrow_right_down_circle_line","mgc_arrow_right_down_line","mgc_arrow_right_line","mgc_arrow_right_up_circle_line","mgc_arrow_right_up_line","mgc_arrow_up_circle_line","mgc_arrow_up_line","mgc_artboard_line","mgc_asterisk_2_line","mgc_asterisk_line","mgc_at_line","mgc_attachment_2_line","mgc_attachment_line","mgc_auction_line","mgc_audio_tape_line","mgc_auto_hold_line","mgc_avalanche_AVAX_line","mgc_award_line","mgc_baby_carriage_line","mgc_baby_line","mgc_back_2_line","mgc_back_line","mgc_backboard_line","mgc_background_line","mgc_backpack_line","mgc_badge_line","mgc_badminton_line","mgc_balance_line","mgc_balloon_2_line","mgc_bank_card_line","mgc_bank_line","mgc_bank_of_china_tower_line","mgc_barbell_line","mgc_barcode_line","mgc_barcode_scan_line","mgc_base_station_2_line","mgc_base_station_line","mgc_baseball_line","mgc_basket_line","mgc_basketball_line","mgc_bath_line","mgc_battery_1_line","mgc_battery_2_line","mgc_battery_3_line","mgc_battery_4_line","mgc_battery_automotive_line","mgc_battery_charging_line","mgc_battery_line","mgc_beard_line","mgc_bed_2_line","mgc_bed_line","mgc_bell_ringing_line","mgc_big_ben_line","mgc_bike_line","mgc_bill_line","mgc_binance_USD_BUSD_line","mgc_binance_coin_BNB_line","mgc_bird_line","mgc_black_board_2_line","mgc_black_board_line","mgc_blessing_line","mgc_bling_line","mgc_bluetooth_line","mgc_bluetooth_off_line","mgc_board_line","mgc_body_line","mgc_bold_line","mgc_bone_line","mgc_book_2_line","mgc_book_3_line","mgc_book_4_line","mgc_book_5_line","mgc_book_6_line","mgc_book_line","mgc_bookmark_line","mgc_bookmarks_line","mgc_border_blank_line","mgc_border_bottom_line","mgc_border_horizontal_line","mgc_border_inner_line","mgc_border_left_line","mgc_border_outer_line","mgc_border_radius_line","mgc_border_top_line","mgc_border_vertical_line","mgc_bow_tie_line","mgc_bowknot_line","mgc_bowl_line","mgc_box_2_line","mgc_box_3_line","mgc_box_line","mgc_braces_line","mgc_brackets_angle_line","mgc_brackets_line","mgc_brain_line","mgc_brake_line","mgc_bread_line","mgc_bridge_2_line","mgc_bridge_line","mgc_brief_line","mgc_briefcase_line","mgc_brightness_line","mgc_broom_line","mgc_brush_2_line","mgc_brush_line","mgc_bug_line","mgc_building_1_line","mgc_building_2_line","mgc_building_3_line","mgc_building_4_line","mgc_building_5_line","mgc_building_6_line","mgc_bulb_line","mgc_burj_al_arab_line","mgc_burj_khalifa_tower_line","mgc_bus_2_line","mgc_bus_line","mgc_butterfly_line","mgc_cactus_2_line","mgc_cactus_line","mgc_cake_line","mgc_calendar_add_line","mgc_calendar_day_line","mgc_calendar_line","mgc_calendar_month_line","mgc_calendar_time_add_line","mgc_calendar_week_line","mgc_camcorder_2_line","mgc_camcorder_line","mgc_camera_2_line","mgc_camera_line","mgc_camera_rotate_line","mgc_campground_line","mgc_candle_line","mgc_candy_2_line","mgc_candy_line","mgc_canton_tower_line","mgc_car_2_line","mgc_car_3_line","mgc_car_door_line","mgc_car_line","mgc_car_window_line","mgc_cardano_ADA_line","mgc_cardboard_vr_line","mgc_carplay_line","mgc_carrot_line","mgc_cat_line","mgc_ceiling_lamp_line","mgc_celebrate_line","mgc_cellphone_line","mgc_cellphone_vibration_line","mgc_certificate_2_line","mgc_certificate_line","mgc_champagne_line","mgc_charging_pile_line","mgc_chart_bar_line","mgc_chart_line_line","mgc_chart_pie_line","mgc_chat_1_line","mgc_chat_2_line","mgc_chat_3_line","mgc_check_2_line","mgc_check_circle_line","mgc_check_line","mgc_chicken_line","mgc_chines_knot_line","mgc_chip_line","mgc_chopsticks_line","mgc_christ_the_redeemer_line","mgc_christmas_ball_line","mgc_christmas_hat_line","mgc_chrome_line","mgc_clapperboard_line","mgc_classify_2_line","mgc_classify_line","mgc_clipboard_line","mgc_clock_2_line","mgc_clock_line","mgc_clockwise_alt_line","mgc_clockwise_line","mgc_close_circle_line","mgc_close_line","mgc_cloud_lightning_line","mgc_cloud_line","mgc_cloud_snow_line","mgc_clubs_line","mgc_coat_line","mgc_coathanger_line","mgc_code_line","mgc_codepen_line","mgc_coin_2_line","mgc_coin_3_line","mgc_coin_line","mgc_color_filter_line","mgc_color_picker_line","mgc_column_line","mgc_columns_2_line","mgc_columns_3_line","mgc_command_line","mgc_comment_line","mgc_compass_2_line","mgc_compass_line","mgc_computer_camera_line","mgc_computer_camera_off_line","mgc_computer_line","mgc_contacts_line","mgc_cookie_line","mgc_cookie_man_line","mgc_copper_coin_line","mgc_copy_2_line","mgc_copy_3_line","mgc_copy_line","mgc_copyright_line","mgc_counter_2_line","mgc_counter_line","mgc_coupon_line","mgc_crutch_line","mgc_cube_3d_line","mgc_cupcake_line","mgc_currency_baht_2_line","mgc_currency_baht_line","mgc_currency_bitcoin_2_line","mgc_currency_bitcoin_line","mgc_currency_cny_2_line","mgc_currency_cny_line","mgc_currency_dollar_2_line","mgc_currency_dollar_line","mgc_currency_euro_2_line","mgc_currency_euro_line","mgc_currency_lira_2_line","mgc_currency_lira_line","mgc_currency_pound_2_line","mgc_currency_pound_line","mgc_currency_rubel_2_line","mgc_currency_rubel_line","mgc_currency_rupee_2_line","mgc_currency_rupee_line","mgc_currency_shekel_2_line","mgc_currency_shekel_line","mgc_currency_won_2_line","mgc_currency_won_line","mgc_cursor_line","mgc_cursor_text_line","mgc_dandelion_line","mgc_dashboard_2_line","mgc_dashboard_3_line","mgc_dashboard_4_line","mgc_dashboard_line","mgc_deer_line","mgc_delete_2_line","mgc_delete_back_line","mgc_delete_line","mgc_dental_line","mgc_department_line","mgc_desk_lamp_line","mgc_desk_line","mgc_device_line","mgc_diamond_2_line","mgc_diamond_line","mgc_diary_line","mgc_dingtalk_line","mgc_directions_2_line","mgc_directions_line","mgc_directory_line","mgc_disc_line","mgc_discord_line","mgc_display_line","mgc_distribute_spacing_horizontal_line","mgc_distribute_spacing_vertical_line","mgc_dividing_line_line","mgc_doc_line","mgc_document_2_line","mgc_document_3_line","mgc_document_line","mgc_documents_line","mgc_dog_line","mgc_dogecoin_DOGE_line","mgc_door_line","mgc_dot_grid_line","mgc_down_line","mgc_down_small_line","mgc_download_2_line","mgc_download_3_line","mgc_download_line","mgc_dragonfly_line","mgc_drawer_2_line","mgc_drawer_line","mgc_drawing_board_line","mgc_dress_line","mgc_dribbble_line","mgc_drink_line","mgc_drone_line","mgc_drop_line","mgc_drum_line","mgc_dutch_windmill_line","mgc_ear_line","mgc_earth_line","mgc_ebike_line","mgc_edit_2_line","mgc_edit_3_line","mgc_edit_line","mgc_egg_crack_line","mgc_egg_line","mgc_egyptian_pyramids_line","mgc_eiffel_tower_line","mgc_emergency_flashers_line","mgc_emoji_2_line","mgc_emoji_line","mgc_engine_line","mgc_enter_door_line","mgc_entrance_line","mgc_eraser_line","mgc_escalator_down_line","mgc_escalator_up_line","mgc_ethereum_line","mgc_exchange_baht_line","mgc_exchange_bitcoin_line","mgc_exchange_cny_line","mgc_exchange_dollar_line","mgc_exchange_euro_line","mgc_exit_door_line","mgc_exit_line","mgc_expand_player_line","mgc_external_link_line","mgc_eye_2_line","mgc_eye_close_line","mgc_eye_line","mgc_eyebrow_line","mgc_eyeglass_line","mgc_face_line","mgc_face_mask_line","mgc_facebook_line","mgc_faceid_line","mgc_factory_line","mgc_fan_2_line","mgc_fan_direction_down_line","mgc_fan_direction_front_line","mgc_fan_direction_up_line","mgc_fan_line","mgc_fast_forward_line","mgc_fast_rewind_line","mgc_father_christmas_line","mgc_female_line","mgc_figma_line","mgc_file_certificate_line","mgc_file_check_line","mgc_file_code_line","mgc_file_download_line","mgc_file_export_line","mgc_file_forbid_line","mgc_file_import_line","mgc_file_infor_line","mgc_file_line","mgc_file_locked_line","mgc_file_more_line","mgc_file_music_line","mgc_file_new_line","mgc_file_search_line","mgc_file_security_line","mgc_file_star_line","mgc_file_unknown_line","mgc_file_upload_line","mgc_file_warning_line","mgc_file_zip_line","mgc_film_line","mgc_filter_2_line","mgc_filter_3_line","mgc_filter_line","mgc_finger_press_line","mgc_finger_rock_line","mgc_finger_swipe_line","mgc_finger_tap_line","mgc_fingerprint_line","mgc_fire_line","mgc_firecracker_line","mgc_firefox_line","mgc_firework_line","mgc_fish_line","mgc_fitness_line","mgc_flag_1_line","mgc_flag_2_line","mgc_flag_3_line","mgc_flag_4_line","mgc_flash_line","mgc_flashlight_line","mgc_flask_2_line","mgc_flask_line","mgc_flight_inflight_line","mgc_flight_land_line","mgc_flight_takeoff_line","mgc_flip_horizontal_line","mgc_flip_vertical_line","mgc_flower_2_line","mgc_flower_3_line","mgc_flower_4_line","mgc_flower_line","mgc_flowerpot_line","mgc_folder_2_line","mgc_folder_3_line","mgc_folder_check_line","mgc_folder_delete_line","mgc_folder_download_line","mgc_folder_forbid_line","mgc_folder_infor_line","mgc_folder_line","mgc_folder_locked_2_line","mgc_folder_locked_line","mgc_folder_minus_line","mgc_folder_more_line","mgc_folder_open_2_line","mgc_folder_open_line","mgc_folder_security_line","mgc_folder_star_line","mgc_folder_upload_line","mgc_folder_warning_line","mgc_folders_line","mgc_folding_fan_line","mgc_font_line","mgc_font_size_line","mgc_foot_line","mgc_football_line","mgc_forbid_circle_line","mgc_fork_knife_line","mgc_fork_line","mgc_fork_spoon_line","mgc_formula_line","mgc_forward_2_line","mgc_forward_line","mgc_four_wheel_drive_line","mgc_frame_line","mgc_fridge_line","mgc_front_fog_lights_line","mgc_front_windshield_defroster_line","mgc_fullscreen_2_line","mgc_fullscreen_exit_2_line","mgc_fullscreen_exit_line","mgc_fullscreen_line","mgc_game_1_line","mgc_game_2_line","mgc_gas_station_line","mgc_gesture_unlock_line","mgc_ghost_line","mgc_gift_2_line","mgc_gift_card_line","mgc_gift_line","mgc_git_branch_line","mgc_git_commit_line","mgc_git_compare_line","mgc_git_lab_line","mgc_git_merge_line","mgc_git_pull_request_close_line","mgc_git_pull_request_line","mgc_github_line","mgc_glass_cup_line","mgc_globe_line","mgc_glove_line","mgc_google_line","mgc_google_play_line","mgc_government_line","mgc_grass_line","mgc_greatwall_line","mgc_grid_2_line","mgc_grid_line","mgc_group_line","mgc_hair_2_line","mgc_hair_line","mgc_hand_finger_2_line","mgc_hand_finger_line","mgc_hand_grab_line","mgc_hand_line","mgc_hand_two_fingers_line","mgc_hands_clapping_line","mgc_hashtag_line","mgc_hat_2_line","mgc_hat_line","mgc_head_line","mgc_headphone_2_line","mgc_headphone_line","mgc_heart_half_line","mgc_heart_line","mgc_heartbeat_line","mgc_hexagon_line","mgc_hight_beam_headlights_line","mgc_history_2_line","mgc_history_line","mgc_home_1_line","mgc_home_2_line","mgc_home_3_line","mgc_home_4_line","mgc_home_5_line","mgc_home_6_line","mgc_hood_line","mgc_horn_line","mgc_hospital_line","mgc_hotkey_line","mgc_iMac_line","mgc_ice_cream_2_line","mgc_ice_cream_line","mgc_inbox_2_line","mgc_inbox_line","mgc_incognito_mode_line","mgc_indent_decrease_line","mgc_indent_increase_line","mgc_information_line","mgc_ins_line","mgc_inventory_line","mgc_invite_line","mgc_italic_line","mgc_jeep_line","mgc_kakao_talk_line","mgc_key_1_line","mgc_key_2_line","mgc_keyboard_line","mgc_keyhole_line","mgc_kingkey_100_tower_line","mgc_knife_line","mgc_ladder_line","mgc_lantern_2_line","mgc_lantern_line","mgc_laptop_2_line","mgc_laptop_line","mgc_large_arrow_down_line","mgc_large_arrow_left_line","mgc_large_arrow_right_line","mgc_large_arrow_up_line","mgc_layout_10_line","mgc_layout_11_line","mgc_layout_2_line","mgc_layout_3_line","mgc_layout_4_line","mgc_layout_5_line","mgc_layout_6_line","mgc_layout_7_line","mgc_layout_8_line","mgc_layout_9_line","mgc_layout_bottom_line","mgc_layout_grid_line","mgc_layout_left_line","mgc_layout_line","mgc_layout_right_line","mgc_layout_top_line","mgc_leaf_2_line","mgc_leaf_3_line","mgc_leaf_line","mgc_left_line","mgc_left_small_line","mgc_letter_spacing_line","mgc_lifebuoy_line","mgc_light_line","mgc_lighthouse_line","mgc_lightning_line","mgc_line_app_line","mgc_line_height_line","mgc_link_2_line","mgc_link_3_line","mgc_link_line","mgc_linkedin_line","mgc_list_check_2_line","mgc_list_check_3_line","mgc_list_check_line","mgc_list_collapse_line","mgc_list_expansion_line","mgc_list_ordered_line","mgc_list_search_line","mgc_live_location_line","mgc_live_photo_line","mgc_loading_2_line","mgc_loading_3_line","mgc_loading_4_line","mgc_loading_line","mgc_location_2_line","mgc_location_3_line","mgc_location_line","mgc_lock_line","mgc_lollipop_line","mgc_lotus_line","mgc_low_beam_headlights_line","mgc_luggage_line","mgc_magic_1_line","mgc_magic_2_line","mgc_magnet_line","mgc_mail_line","mgc_mail_open_line","mgc_mail_send_line","mgc_mailbox_line","mgc_male_line","mgc_map_2_line","mgc_map_line","mgc_map_pin_line","mgc_maple_leaf_line","mgc_marina_bay_sand_line","mgc_mark_pen_line","mgc_markdown_line","mgc_markup_line","mgc_mastercard_line","mgc_mastodon_line","mgc_maya_pyramids_line","mgc_medal_line","mgc_medium_line","mgc_menu_line","mgc_message_1_line","mgc_message_2_line","mgc_message_3_line","mgc_message_4_line","mgc_messenger_line","mgc_meta_line","mgc_mic_2_line","mgc_mic_line","mgc_mic_off_line","mgc_microphone_line","mgc_microscope_line","mgc_middle_finger_line","mgc_midi_line","mgc_mingcute_line","mgc_minimize_line","mgc_miniplayer_line","mgc_minus_circle_line","mgc_miyajima_torii_line","mgc_moai_line","mgc_moment_line","mgc_monument_line","mgc_moon_cloudy_line","mgc_moon_line","mgc_moon_stars_line","mgc_more_1_line","mgc_more_2_line","mgc_more_3_line","mgc_more_4_line","mgc_mortarboard_line","mgc_mosaic_line","mgc_mouse_line","mgc_mouth_line","mgc_move_line","mgc_movie_line","mgc_mushroom_line","mgc_music_2_line","mgc_music_3_line","mgc_music_line","mgc_navigation_line","mgc_necktie_line","mgc_new_folder_line","mgc_newdot_line","mgc_news_line","mgc_nintendo_switch_line","mgc_nose_line","mgc_notebook_2_line","mgc_notebook_line","mgc_notification_line","mgc_notification_off_line","mgc_octagon_line","mgc_oil_line","mgc_omega_line","mgc_open_door_line","mgc_openai_line","mgc_package_2_line","mgc_package_line","mgc_pad_line","mgc_paint_2_line","mgc_paint_brush_line","mgc_paint_line","mgc_palace_line","mgc_palette_2_line","mgc_palette_line","mgc_paper_line","mgc_parachute_line","mgc_paragraph_line","mgc_parentheses_line","mgc_park_line","mgc_parking_lights_line","mgc_parking_line","mgc_passport_line","mgc_paster_line","mgc_pause_circle_line","mgc_pause_line","mgc_pavilion_line","mgc_paypal_line","mgc_pdf_line","mgc_pen_2_line","mgc_pen_line","mgc_pencil_2_line","mgc_pencil_line","mgc_pentagon_line","mgc_performance_line","mgc_phone_add_line","mgc_phone_block_line","mgc_phone_call_line","mgc_phone_incoming_line","mgc_phone_line","mgc_phone_off_line","mgc_phone_outgoing_line","mgc_phone_success_line","mgc_photo_album_2_line","mgc_photo_album_line","mgc_pic_2_line","mgc_pic_line","mgc_pig_line","mgc_pig_money_line","mgc_pin_2_line","mgc_pin_line","mgc_pingpong_line","mgc_pinterest_line","mgc_pinwheel_2_line","mgc_pinwheel_line","mgc_pisa_tower_line","mgc_planet_line","mgc_play_circle_line","mgc_play_line","mgc_playground_line","mgc_playlist_2_line","mgc_playlist_line","mgc_plugin_2_line","mgc_plugin_line","mgc_polkadot_DOT_line","mgc_polygon_line","mgc_power_line","mgc_ppt_line","mgc_presentation_1_line","mgc_presentation_2_line","mgc_print_line","mgc_profile_line","mgc_pumpkin_lantern_line","mgc_pumpkin_line","mgc_qq_line","mgc_qrcode_2_line","mgc_qrcode_line","mgc_question_line","mgc_quill_pen_line","mgc_quote_left_line","mgc_quote_right_line","mgc_radar_2_line","mgc_radar_line","mgc_radio_line","mgc_rain_line","mgc_rare_fog_lights_line","mgc_react_line","mgc_rear_windshield_defroster_line","mgc_receive_money_line","mgc_record_mail_line","mgc_recycle_line","mgc_red_packet_line","mgc_red_packet_open_line","mgc_reddit_line","mgc_refresh_1_line","mgc_refresh_2_line","mgc_refresh_3_line","mgc_refresh_4_line","mgc_registered_line","mgc_remote_line","mgc_repeat_line","mgc_repeat_one_line","mgc_report_line","mgc_rest_area_line","mgc_restore_line","mgc_riding_line","mgc_right_line","mgc_right_small_line","mgc_road_line","mgc_rocket_2_line","mgc_rocket_line","mgc_rotate_to_horizontal_line","mgc_rotate_to_vertical_line","mgc_rotate_x_line","mgc_rotate_y_line","mgc_round_line","mgc_route_line","mgc_router_modem_line","mgc_rows_2_line","mgc_rows_3_line","mgc_rows_4_line","mgc_rss_2_line","mgc_rss_line","mgc_rudder_line","mgc_ruler_line","mgc_run_line","mgc_safe_alert_line","mgc_safe_box_line","mgc_safe_flash_line","mgc_safe_lock_line","mgc_safe_shield_2_line","mgc_safe_shield_line","mgc_safety_certificate_line","mgc_sailboat_line","mgc_sale_line","mgc_sandglass_line","mgc_save_2_line","mgc_save_line","mgc_scale_line","mgc_scan_2_line","mgc_scan_line","mgc_scarf_line","mgc_schedule_line","mgc_school_line","mgc_science_line","mgc_scissors_2_line","mgc_scissors_3_line","mgc_scissors_line","mgc_scooter_line","mgc_screenshot_line","mgc_seal_line","mgc_search_2_line","mgc_search_3_line","mgc_search_line","mgc_seat_heated_line","mgc_seat_line","mgc_selector_horizontal_line","mgc_selector_vertical_line","mgc_send_line","mgc_send_plane_line","mgc_server_2_line","mgc_server_line","mgc_service_line","mgc_settings_1_line","mgc_settings_2_line","mgc_settings_3_line","mgc_settings_4_line","mgc_settings_5_line","mgc_settings_6_line","mgc_share_2_line","mgc_share_3_line","mgc_share_forward_line","mgc_shield_line","mgc_shield_shape_line","mgc_ship_line","mgc_shirt_line","mgc_shoe_2_line","mgc_shoe_line","mgc_shopping_bag_1_line","mgc_shopping_bag_2_line","mgc_shopping_bag_3_line","mgc_shopping_cart_1_line","mgc_shopping_cart_2_line","mgc_shorts_line","mgc_shuffle_2_line","mgc_shuffle_line","mgc_signature_line","mgc_sitemap_line","mgc_skateboard_line","mgc_skip_forward_line","mgc_skip_previous_line","mgc_skirt_line","mgc_skull_line","mgc_sleep_line","mgc_sleigh_line","mgc_snapchat_line","mgc_snow_line","mgc_snowman_line","mgc_sock_line","mgc_sofa_line","mgc_solana_SOL_line","mgc_sort_ascending_line","mgc_sort_descending_line","mgc_spacing_horizontal_line","mgc_spacing_vertical_line","mgc_spade_line","mgc_sparkles_line","mgc_speaker_line","mgc_speech_line","mgc_spoon_line","mgc_spotify_line","mgc_square_arrow_down_line","mgc_square_arrow_left_line","mgc_square_arrow_right_line","mgc_square_arrow_up_line","mgc_square_line","mgc_star_half_line","mgc_star_line","mgc_statue_of_liberty_line","mgc_steering_wheel_line","mgc_sticker_line","mgc_stock_line","mgc_stop_circle_line","mgc_stop_line","mgc_stopwatch_line","mgc_store_2_line","mgc_store_line","mgc_strikethrough_line","mgc_stripe_line","mgc_sugar_coated_haws_line","mgc_suitcase_2_line","mgc_suitcase_line","mgc_sun_cloudy_line","mgc_sun_line","mgc_sunrise_line","mgc_sunset_line","mgc_surfboard_line","mgc_swimming_pool_line","mgc_switch_line","mgc_sword_line","mgc_sydney_opera_house_line","mgc_t_shirt_2_line","mgc_t_shirt_line","mgc_table_2_line","mgc_table_3_line","mgc_table_line","mgc_tag_2_line","mgc_tag_chevron_line","mgc_tag_line","mgc_taipei101_line","mgc_taj_mahal_line","mgc_target_line","mgc_task_2_line","mgc_task_line","mgc_teacup_line","mgc_telegram_line","mgc_telescope_line","mgc_temple_of_heaven_line","mgc_tent_line","mgc_terminal_box_line","mgc_terminal_line","mgc_tether_USDT_line","mgc_text_color_line","mgc_text_line","mgc_textbox_line","mgc_thermometer_line","mgc_thumb_down_2_line","mgc_thumb_down_line","mgc_thumb_up_2_line","mgc_thumb_up_line","mgc_tiktok_line","mgc_time_line","mgc_to_do_line","mgc_toggle_left_2_line","mgc_toggle_left_line","mgc_toggle_right_2_line","mgc_toggle_right_line","mgc_toilet_paper_line","mgc_tool_line","mgc_tower_line","mgc_toxophily_line","mgc_traffic_cone_line","mgc_traffic_lights_line","mgc_train_2_line","mgc_train_3_line","mgc_train_4_line","mgc_train_line","mgc_transfer_2_line","mgc_transfer_3_line","mgc_transfer_4_line","mgc_transfer_line","mgc_transformation_line","mgc_translate_2_line","mgc_translate_line","mgc_tree_2_line","mgc_tree_3_line","mgc_tree_4_line","mgc_tree_line","mgc_trello_board_line","mgc_triangle_line","mgc_triumphal_arch_line","mgc_trophy_line","mgc_trouser_line","mgc_truck_line","mgc_trunk_line","mgc_tv_1_line","mgc_tv_2_line","mgc_twitter_line","mgc_typhoon_line","mgc_tyre_line","mgc_umbrella_2_line","mgc_umbrella_line","mgc_underline_line","mgc_unlink_2_line","mgc_unlink_line","mgc_unlock_line","mgc_up_line","mgc_up_small_line","mgc_upload_2_line","mgc_upload_3_line","mgc_upload_line","mgc_usb_flash_disk_line","mgc_usb_line","mgc_usd_coin_USDC_line","mgc_user_1_line","mgc_user_2_line","mgc_user_3_line","mgc_user_4_line","mgc_user_5_line","mgc_user_add_line","mgc_user_follow_line","mgc_user_remove_line","mgc_vector_bezier_2_line","mgc_vector_bezier_3_line","mgc_vector_bezier_line","mgc_vector_group_line","mgc_version_line","mgc_vest_line","mgc_video_line","mgc_visa_line","mgc_vkontakte_line","mgc_voice_line","mgc_volleyball_line","mgc_volume_line","mgc_volume_mute_line","mgc_volume_off_line","mgc_vue_line","mgc_walk_line","mgc_wallet_2_line","mgc_wallet_3_line","mgc_wallet_4_line","mgc_wallet_5_line","mgc_wallet_line","mgc_wardrobe_2_line","mgc_wardrobe_line","mgc_warning_line","mgc_wash_machine_line","mgc_wastebasket_line","mgc_watch_2_line","mgc_watch_line","mgc_wave_line","mgc_web_line","mgc_wechat_line","mgc_weibo_line","mgc_whatsapp_line","mgc_wheel_line","mgc_wifi_line","mgc_wifi_off_line","mgc_wind_line","mgc_windows_line","mgc_wine_line","mgc_wineglass_2_line","mgc_wineglass_line","mgc_wiper_line","mgc_world_2_line","mgc_world_line","mgc_wreath_line","mgc_xbox_line","mgc_xls_line","mgc_yinyang_line","mgc_youtube_line","mgc_yuanbao_line","mgc_zoom_in_line","mgc_zoom_out_line"];

const PALETTE = {
    primary:   '#4361ee',
    success:   '#0acf97',
    warning:   '#ffbc00',
    danger:    '#fa5c7c',
    info:      '#39afd1',
    secondary: '#6c757d',
    dark:      '#313a46',
};

let currentIcon  = '{{ $currentIcon }}';
let activeColorKey = '{{ $currentColor }}';
let activeHex      = '{{ $isCustom ? $customHex : ($colorList[$savedColor]['hex'] ?? '#4361ee') }}';

const PAGE = 80;
let filtered   = ALL_ICONS;
let page       = 0;
let searchTimer;

// ── Icon Picker ───────────────────────────────────────────────────────
const iconTrigger  = document.getElementById('icon-trigger');
const iconDropdown = document.getElementById('icon-dropdown');
const iconGrid     = document.getElementById('icon-grid');
const iconSearch   = document.getElementById('icon-search');
const iconCount    = document.getElementById('icon-count');
const moreHint     = document.getElementById('icon-more-hint');

iconTrigger.addEventListener('click', function (e) {
    e.stopPropagation();
    const isOpen = !iconDropdown.classList.contains('hidden');
    iconDropdown.classList.toggle('hidden', isOpen);
    if (!isOpen) {
        iconSearch.value = '';
        filtered = ALL_ICONS;
        page = 0;
        renderGrid();
        iconSearch.focus();
    }
});

document.addEventListener('click', function (e) {
    if (!iconTrigger.contains(e.target) && !iconDropdown.contains(e.target)) {
        iconDropdown.classList.add('hidden');
    }
});

iconSearch.addEventListener('input', function () {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => {
        const q = this.value.toLowerCase().trim();
        filtered = q ? ALL_ICONS.filter(ic => ic.includes(q)) : ALL_ICONS;
        page = 0;
        renderGrid();
    }, 150);
});

// Infinite scroll inside the grid
iconGrid.addEventListener('scroll', function () {
    if (this.scrollTop + this.clientHeight >= this.scrollHeight - 20) {
        if ((page + 1) * PAGE < filtered.length) {
            page++;
            appendPage();
        }
    }
});

function renderGrid() {
    iconGrid.innerHTML = '';
    iconCount.textContent = filtered.length + ' icons';
    appendPage();
}

function appendPage() {
    const start = page * PAGE;
    const slice = filtered.slice(start, start + PAGE);
    const frag  = document.createDocumentFragment();
    slice.forEach(ic => {
        const btn = document.createElement('button');
        btn.type  = 'button';
        btn.title = ic;
        btn.className = 'w-9 h-9 rounded-lg flex items-center justify-center hover:bg-primary/10 transition group ' +
            (ic === currentIcon ? 'bg-primary/15 ring-2 ring-primary/40' : '');
        btn.innerHTML = `<i class="${ic} text-gray-600 dark:text-gray-300 text-lg group-hover:text-primary"></i>`;
        btn.addEventListener('click', () => pickIcon(ic));
        frag.appendChild(btn);
    });
    iconGrid.appendChild(frag);
    const remaining = filtered.length - (page + 1) * PAGE;
    moreHint.textContent = remaining > 0 ? `Scroll for ${remaining} more…` : '';
}

function pickIcon(icon) {
    currentIcon = icon;
    document.getElementById('icon-value').value = icon;

    // Update trigger button
    document.getElementById('icon-preview-icon').className = icon + ' text-primary text-lg';
    document.getElementById('icon-preview-name').textContent = icon;

    // Update preview badge
    refreshPreviewIcon();

    // Highlight in grid
    iconGrid.querySelectorAll('button').forEach(b => {
        const active = b.title === icon;
        b.classList.toggle('bg-primary/15', active);
        b.classList.toggle('ring-2', active);
        b.classList.toggle('ring-primary/40', active);
    });

    iconDropdown.classList.add('hidden');
}

// ── Color Picker ──────────────────────────────────────────────────────
function pickColor(key, hex) {
    activeColorKey = key;
    activeHex      = hex;
    document.getElementById('color-value').value = key;
    document.getElementById('color-label-text').textContent = key.charAt(0).toUpperCase() + key.slice(1);
    refreshPreviewColor();
    highlightSwatch(key);
}

function pickCustomColor(hex) {
    activeColorKey = 'custom';
    activeHex      = hex;
    document.getElementById('color-value').value = hex;
    document.getElementById('color-label-text').textContent = 'Custom: ' + hex;
    document.getElementById('custom-swatch-label').style.background = hex;
    refreshPreviewColor();
    highlightSwatch('custom');
}

function highlightSwatch(selected) {
    document.querySelectorAll('.color-swatch').forEach(el => {
        el.classList.remove('scale-110', 'border-gray-800', 'dark:border-white');
        el.classList.add(el.id === 'custom-swatch-label' ? 'border-dashed' : 'border-transparent');
    });
    if (selected === 'custom') {
        const lbl = document.getElementById('custom-swatch-label');
        lbl.classList.remove('border-dashed', 'border-transparent');
        lbl.classList.add('scale-110', 'border-gray-800');
    } else {
        const btn = document.querySelector(`.color-swatch[data-color="${selected}"]`);
        if (btn) {
            btn.classList.remove('border-transparent');
            btn.classList.add('scale-110', 'border-gray-800');
        }
    }
}

function refreshPreviewIcon() {
    const badge = document.getElementById('preview-badge');
    const icon  = document.getElementById('preview-icon');
    icon.className = currentIcon + ' text-lg';
    if (activeColorKey !== 'custom') {
        const tw = {
            primary:'text-primary', success:'text-success', warning:'text-warning',
            danger:'text-danger', info:'text-info', secondary:'text-secondary', dark:'text-gray-800'
        };
        icon.className = currentIcon + ' text-lg ' + (tw[activeColorKey] || 'text-primary');
        icon.style.color = '';
        const bgs = {
            primary:'bg-primary/10', success:'bg-success/10', warning:'bg-warning/10',
            danger:'bg-danger/10', info:'bg-info/10', secondary:'bg-secondary/10', dark:'bg-gray-800/10'
        };
        badge.className = 'w-10 h-10 rounded-lg flex items-center justify-center shrink-0 ' + (bgs[activeColorKey] || 'bg-primary/10');
        badge.style.background = '';
    } else {
        icon.className = currentIcon + ' text-lg';
        icon.style.color = activeHex;
        badge.className = 'w-10 h-10 rounded-lg flex items-center justify-center shrink-0';
        badge.style.background = activeHex + '26';
    }
}

function refreshPreviewColor() {
    refreshPreviewIcon();
    document.getElementById('preview-color-text').textContent =
        activeColorKey === 'custom' ? 'Custom: ' + activeHex
        : activeColorKey.charAt(0).toUpperCase() + activeColorKey.slice(1);
}

// Live name preview
document.getElementById('cat-name-input').addEventListener('input', function () {
    document.getElementById('preview-name').textContent = this.value || 'Category Name';
});

// Init
renderGrid();
refreshPreviewIcon();
</script>
@endpush
