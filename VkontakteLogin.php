<?php
/**
 * Plugin Name:  Socialify Vkontakte Login
 * Description:  Vkontakte Social Login for WordPress based the OAuth2 and HybridAuth
 * Plugin URI:   https://github.com/uptimizt/socialify-vkontakte-login
 * Author:       uptimizt
 * Author URI:   https://github.com/uptimizt
 * Text Domain:  socialify
 * Domain Path:  /languages/
 * Requires PHP: 5.6
 * GitHub Plugin URI: https://github.com/uptimizt/socialify-vkontakte-login
 * Version:      0.4
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 */

namespace Socialify;
defined('ABSPATH') || die();

final class VkontakteLogin
{
    public static $option_name = 'socialify_config_vkontakte';
    public static $endpoint = '/socialify/Vkontakte/';

    public static $data = [
        'settings_section_title' => 'Vkontakte Login',
        'setting_title_id' => 'Vkontakte ID',
        'setting_title_secret' => 'Vkontakte Secret',
    ];

    public static function init()
    {
        self::$endpoint = site_url(self::$endpoint);

        add_action('plugins_loaded', function(){
            if(!class_exists('\Socialify\General')){
                add_action( 'admin_notices',  [__CLASS__, 'notice_no_socialify']);
                return;
            }

            add_action('admin_init', [__CLASS__, 'add_settings']);
            add_filter('socialify_user_profile', [__CLASS__, 'auth_handler'], 11, 2);
        });
    }

    public static function auth_handler($userProfile, $endpoint)
    {
        if('Vkontakte' != $endpoint){
            return $userProfile;
        }

        if(!$config = self::get_config()){
            return $userProfile;
        }

        $adapter = new \Hybridauth\Provider\Vkontakte($config);

        //Attempt to authenticate the user with Facebook
        if($accessToken = $adapter->getAccessToken()){
            $adapter->setAccessToken($accessToken);
        }

        $adapter->authenticate();

        //Retrieve the user's profile
        $userProfile = $adapter->getUserProfile();

        //Disconnect the adapter
        $adapter->disconnect();

        return $userProfile;
    }

    public static function get_config(){

        $config_data = get_option(self::$option_name);
        if(empty($config_data['id']) || empty($config_data['secret'])){
            return false;
        }

        $config = [
            'callback' => self::$endpoint,
            'keys' => [ 'id' => $config_data['id'], 'secret' => $config_data['secret'] ],

        ];

        return $config;
    }

    /**
     * Add settings
     */
    public static function add_settings(){
        add_settings_section(
            $section_id = self::$option_name,
            $section_title = self::$data['settings_section_title'],
            $callback = [__CLASS__, 'render_settings_instructions'],
            General::$settings_group
        );
        register_setting(General::$settings_group, self::$option_name);

        self::add_id_setting();
        self::add_secret_setting();

    }

    public static function render_settings_instructions()
    {
        ?>
        <ol>
            <li>
                <span><?= __('Получить реквизиты для доступа можно по ссылке: ', 'socialify') ?></span>
                <a href="https://vk.com/apps?act=manage/" target="_blank">https://vk.com/apps?act=manage</a>
            </li>
            <li><?= __('В поле Callback URI запишите: ', 'socialify') ?><code><?= self::$endpoint ?></code></li>
            <li>Ссылка на сайт: <code><?= site_url() ?></code></li>
            <li>Домен если потребуется: <code><?= $_SERVER['SERVER_NAME'] ?></code></li>
        </ol>
        <?php
    }

    /**
     * add_id_setting
     *
     * input name: socialify_config_vkontakte[id]
     */
    public static function add_id_setting(){
        $setting_title = self::$data['setting_title_id'];
        $setting_id = self::$option_name . '_id';

        add_settings_field(
            $setting_id,
            $setting_title,
            $callback = function($args){
                printf(
                    '<input type="text" name="%s" value="%s" size="77">',
                    $args['name'], $args['value']
                );
            },
            $page = General::$settings_group,
            $section = self::$option_name,
            $args = [
                'name' => self::$option_name . '[id]',
                'value' => @get_option(self::$option_name)['id'],
            ]
        );
    }

    /**
     * add_secret_setting
     *
     * input name: socialify_config_vkontakte[secret]
     */
    public static function add_secret_setting()
    {
        $setting_id = self::$option_name . '_secret';
        add_settings_field(
            $setting_id,
            $setting_title = self::$data['setting_title_secret'],
            $callback = function($args){
                printf(
                    '<input type="text" name="%s" value="%s" size="77">',
                    $args['name'], $args['value']
                );
            },
            $page = General::$settings_group,
            $section = self::$option_name,
            $args = [
                'name' => self::$option_name . '[secret]',
                'value' => @get_option(self::$option_name)['secret'],
            ]
        );
    }

    /**
     * if no main plugin - show notice
     */
    public static function notice_no_socialify()
    {
        $plugin_data = get_file_data(__FILE__, ['name'=>'Plugin Name'] );
        if(empty($plugin_data['name'])){
            return;
        }

        $class = 'notice notice-error';
        $message = __( 'Oops! No exist Socialify plugin. Please install and activate main plugin.', 'socialify' );
        $message .= ' ' . sprintf(__('%s Deactivated.', 'socialify'), $plugin_data['name']);
        $link = '<a href="https://github.com/uptimizt/socialify" target="_blank">https://github.com/uptimizt/socialify</a>';
        printf( '<div class="%s"><p>%s %s</p></div>', esc_attr( $class ), esc_html( $message ), $link );
    }
}

VkontakteLogin::init();