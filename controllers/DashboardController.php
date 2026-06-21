<?php

if (!defined('ABSPATH')) {
    exit;
}

class DashboardController
{
    public function __construct()
    {
        add_action('init', [$this, 'init_hooks']);
    }

    public function init_hooks()
    {
        // Process POSTs early so redirects and updates happen before output
        $this->process_post_requests();

        // Register painel shortcode during init
        add_shortcode('sistema_painel', [$this, 'render_panel']);
    }

    public function process_post_requests()
    {
        if (!is_user_logged_in()) {
            return;
        }

        if (isset($_POST['acao_painel']) && $_POST['acao_painel'] === 'salvar_pagina') {
            if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'salvar_pagina_acao')) {
                wp_die('Erro de segurança. Tente novamente.');
            }

            $pagina_id     = intval($_POST['pagina_id']);
            $novo_conteudo = wp_kses_post(wp_unslash($_POST['conteudo']));

            if (current_user_can('edit_pages')) {
                $updated = wp_update_post([
                    'ID'           => $pagina_id,
                    'post_content' => $novo_conteudo,
                ]);

                if ($updated) {
                    $secao = isset($_GET['secao']) ? sanitize_text_field($_GET['secao']) : 'dashboard';
                    $redirect_url = add_query_arg([
                        'secao'  => $secao,
                        'status' => 'sucesso',
                    ], wp_unslash($_SERVER['REQUEST_URI']));

                    wp_safe_redirect($redirect_url);
                    exit;
                }
            }
        }
    }

    public function render_panel()
    {
        if (!is_user_logged_in()) {
            return '<p>Por favor, faça <a href="' . esc_url(home_url('/login')) . '">login</a>.</p>';
        }

        $user  = wp_get_current_user();
        $secao = isset($_GET['secao']) ? sanitize_text_field($_GET['secao']) : 'dashboard';

        ob_start();
        ?>
        <div class="painel-container" style="display:flex; min-height:600px; border:1px solid #ddd;">
            <div class="menu" style="width:200px; background:#2c3e50; padding:20px; color:#fff;">
                <h4 style="margin-top:0;">Menu</h4>
                <a href="?secao=dashboard" style="color:#fff; display:block; margin-bottom:10px;">🏠 Dashboard</a>
                <a href="?secao=home" style="color:#fff; display:block; margin-bottom:10px;">📄 Home</a>
                <a href="?secao=sobre" style="color:#fff; display:block; margin-bottom:10px;">📄 Sobre</a>
                <a href="?secao=servicos" style="color:#fff; display:block; margin-bottom:10px;">📄 Serviços</a>
                <hr>
                <a href="<?php echo esc_url(wp_logout_url(home_url('/login'))); ?>" style="color:#ff7675;">Sair</a>
            </div>

            <div class="conteudo" style="flex:1; padding:30px; background:#f9f9f9;">
                <?php
                if (isset($_GET['status']) && $_GET['status'] === 'sucesso') {
                    echo '<div style="background:#d4edda; color:#155724; padding:15px; margin-bottom:20px; border-radius:5px;">✅ Alterações salvas com sucesso!</div>';
                }

                if ($secao === 'dashboard') {
                    echo '<h2>Olá, ' . esc_html($user->display_name) . '!</h2><p>Selecione uma página ao lado para editar o conteúdo diretamente por aqui.</p>';
                } else {
                    $this->generate_dynamic_editor($secao);
                }
                ?>
            </div>
        </div>
        <?php

        return ob_get_clean();
    }

    private function generate_dynamic_editor($slug)
    {
        $pagina = get_page_by_path($slug);

        if (!$pagina) {
            echo '<p>Página <strong>' . esc_html($slug) . '</strong> não encontrada. Verifique se o slug na URL está correto.</p>';
            return;
        }

        echo '<h3>Editando: ' . esc_html(get_the_title($pagina->ID)) . '</h3>';
        ?>
        <form method="post">
            <input type="hidden" name="acao_painel" value="salvar_pagina">
            <input type="hidden" name="pagina_id" value="<?php echo esc_attr($pagina->ID); ?>">
            <?php wp_nonce_field('salvar_pagina_acao'); ?>
            <?php wp_editor($pagina->post_content, 'conteudo', [
                'textarea_name' => 'conteudo',
                'media_buttons' => true,
                'textarea_rows' => 15,
            ]); ?>
            <br>
            <button type="submit" style="background:#27ae60; color:white; border:none; padding:10px 25px; border-radius:4px; cursor:pointer;">
                Salvar Alterações
            </button>
        </form>
        <?php
    }
}
