<?php
/*
Plugin Name: Gerenciador Saas
Description: Sistema SaaS com painel externo.
Version: 1.0
Author: Rosiane Passos
*/

if (!defined('ABSPATH')) {
    exit;
}

define('GERENCIADOR_SAAS_PATH', plugin_dir_path(__FILE__));
define('GERENCIADOR_SAAS_URL', plugin_dir_url(__FILE__));

require_once GERENCIADOR_SAAS_PATH . 'core/Loader.php';
require_once GERENCIADOR_SAAS_PATH . 'models/IdiomaModel.php';

$saas_plugin = new Loader();
$saas_plugin->run();

// Garante páginas necessárias e inicializa página de idiomas
gerenciamento_saas_ensure_login_page();
gerenciamento_saas_ensure_idiomas_page();

function gerenciamento_saas_ensure_idiomas_page()
{
    if (!function_exists('get_page_by_path')) {
        return;
    }

    $slug = 'idiomas';
    if (!get_page_by_path($slug)) {
        wp_insert_post([
            'post_title'   => 'Idiomas',
            'post_name'    => $slug,
            'post_content' => '[sistema_idiomas]',
            'post_status'  => 'publish',
            'post_type'    => 'page',
        ]);
    }
    else {
        $page = get_page_by_path($slug);
        if ($page && strpos($page->post_content, '[sistema_idiomas]') === false) {
            wp_update_post([
                'ID' => $page->ID,
                'post_content' => '[sistema_idiomas]'
            ]);
        }
    }
}

// Flush rewrite rules on activation/deactivation to register the public route
register_activation_hook(__FILE__, 'gerenciador_saas_activate');
function gerenciador_saas_activate() {
    // Ensure dependencies are loaded so rewrite rules are added
    $loader = new Loader();
    $loader->run();
    // Create required pages if they don't exist yet
    $required_pages = [
        'sistema-painel' => '[sistema_painel]',
        'login' => '[login_usuario]',
    ];

    foreach ($required_pages as $slug => $content) {
        if (!get_page_by_path($slug)) {
            wp_insert_post([
                'post_title'   => ucwords(str_replace('-', ' ', $slug)),
                'post_name'    => $slug,
                'post_content' => $content,
                'post_status'  => 'publish',
                'post_type'    => 'page',
            ]);
        }
    }

    gerenciador_saas_create_schema();
    flush_rewrite_rules();
}

function gerenciador_saas_create_schema() {
    $model = new IdiomaModel();
    $model->criar_tabela();
}

register_deactivation_hook(__FILE__, 'gerenciador_saas_deactivate');
function gerenciador_saas_deactivate() {
    flush_rewrite_rules();
}

function gerenciamento_saas_ensure_login_page()
{
    if (!function_exists('get_page_by_path')) {
        return;
    }

    $slug = 'login';
    if (!get_page_by_path($slug)) {
        wp_insert_post([
            'post_title'   => 'Login',
            'post_name'    => $slug,
            'post_content' => '[login_usuario]',
            'post_status'  => 'publish',
            'post_type'    => 'page',
        ]);
    }
}
//http://localhost:8080/?saas_dashboard=1
//[sistema_idiomas_progress]
//Proximo delete e update