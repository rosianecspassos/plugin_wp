<?php

if (!defined('ABSPATH')) {
    exit;
}

class IdiomasController {
    private $model;

    public function __construct() {
        $this->model = new IdiomaModel();
        add_action('init', [$this, 'init_hooks']);
    }

    public function init_hooks() {
        $this->process_post_requests();
        add_shortcode('sistema_idiomas', [$this, 'render_panel']);
        add_shortcode('sistema_idiomas_progress', [$this, 'render_progress_barras']);
        add_filter('the_content', [$this, 'inject_curriculo_on_homepage'], 20);
        add_shortcode('curriculo_idiomas', [$this, 'render_idiomas_curriculo']);
    }

    public function process_post_requests() {
        if (!is_user_logged_in()) {
            return;
        }

        if (!isset($_POST['acao_idiomas']) || !isset($_POST['_wpnonce'])) {
            return;
        }

        if (!wp_verify_nonce($_POST['_wpnonce'], 'salvar_idioma_acao')) {
            wp_die('Erro de segurança. Tente novamente.');
        }

        if (!current_user_can('manage_options')) {
            return;
        }

        $action = sanitize_text_field($_POST['acao_idiomas']);
        $nome = sanitize_text_field($_POST['nome'] ?? '');

        // Suporta envio por texto de nível (ex: nativo, fluente, intermediario, basico)
        $nivel_text = isset($_POST['nivel_text']) ? sanitize_text_field($_POST['nivel_text']) : null;
        $porcentagem = 0;
        if ($nivel_text) {
            $porcentagem = $this->map_nivel_text_to_percent($nivel_text);
        } else {
            $porcentagem = max(0, min(100, intval($_POST['porcentagem'] ?? 0)));
        }

        if ($action === 'salvar_idioma' && isset($_POST['idioma_id'])) {
            $idioma_id = intval($_POST['idioma_id']);
            $this->model->atualizar($idioma_id, $nome, $porcentagem);
            $this->redirect_success();
        }

        if ($action === 'adicionar_idioma') {
            $this->model->cadastrar(get_current_user_id(), $nome, $porcentagem);
            $this->redirect_success();
        }
    }

    private function map_nivel_text_to_percent($nivel_text)
    {
        $nivel_text = strtolower(trim($nivel_text));
        switch ($nivel_text) {
            case 'nativo':
            case 'nativa':
                return 100;
            case 'fluente':
                return 85;
            case 'intermediario':
            case 'intermediário':
                return 65;
            case 'basico':
            case 'básico':
            default:
                return 30;
        }
    }

    private function redirect_success() {
        $redirect_url = add_query_arg([
            'secao'  => 'idiomas',
            'status' => 'sucesso',
        ], wp_unslash($_SERVER['REQUEST_URI']));

        wp_safe_redirect($redirect_url);
        exit;
    }

    private function get_languages() {
        return $this->model->buscar_por_usuario(get_current_user_id());
    }

    private function obter_cor($nivel) {
        $nivel = intval($nivel);
        if ($nivel === 100) {
            return '#16a34a'; // verde
        }

        if ($nivel >= 80) {
            return '#2563eb'; // azul
        }

        if ($nivel >= 50) {
            return '#f59e0b'; // amarelo
        }

        return '#ef4444'; // vermelho
    }

    public function render_idiomas_curriculo() {
        // Render public curriculum languages (visible to site visitors)
        $idiomas = $this->model->buscar_todos();

        ob_start();
        include plugin_dir_path(__FILE__) . '../views/curriculo-idiomas.php';
        return ob_get_clean();
    }

    // Nota: o formulário front-end foi removido — use o painel interno (`painel-idiomas.php`) para gerenciar idiomas.

    // Injeta o conteúdo do currículo de idiomas na página inicial
    public function inject_curriculo_on_homepage($content) {
        if (is_admin()) {
            return $content;
        }

        if (!function_exists('is_front_page')) {
            return $content;
        }

        if (is_front_page() || is_home()) {
            $html = $this->render_idiomas_curriculo();
            if (!empty($html)) {
                return $content . '\n<div class="idiomas-home-container">' . $html . '</div>';
            }
        }

        return $content;
    }

    public function render_panel() {
        if (!is_user_logged_in()) {
            return '<p>Por favor, faça <a href="' . esc_url(home_url('/login')) . '">login</a>.</p>';
        }

        $languages = $this->get_languages();
        $status = isset($_GET['status']) && $_GET['status'] === 'sucesso';

        ob_start();
        include plugin_dir_path(__FILE__) . '../views/painel-idiomas.php';
        return ob_get_clean();
    }

    public function render_progress_barras() {
        $languages = $this->get_languages();

        ob_start();
        ?>
        <div class="sistema-idiomas-progress" style="max-width:760px;margin:0 auto;font-family:Arial,sans-serif;">
            <style>
                .sistema-idiomas-progress .idioma-item { margin-bottom:20px; }
                .sistema-idiomas-progress .idioma-meta { display:flex; justify-content:space-between; align-items:center; margin-bottom:8px; font-weight:700; color:#111827; }
                .sistema-idiomas-progress .idioma-bar { background:#e5e7eb; border-radius:999px; overflow:hidden; height:20px; }
                .sistema-idiomas-progress .idioma-fill { background:#2563eb; height:100%; transition:width .3s ease; }
                .sistema-idiomas-progress .empty-state { background:#f3f4f6; color:#6b7280; padding:16px; border-radius:10px; text-align:center; }
            </style>

            <?php if (empty($languages)): ?>
                <div class="empty-state">Nenhum idioma cadastrado. Adicione um idioma no painel para ver a barra de progresso.</div>
            <?php else: ?>
                <?php foreach ($languages as $idioma): ?>
                    <div class="idioma-item">
                        <div class="idioma-meta">
                            <span><?php echo esc_html($idioma['nome']); ?></span>
                            <span><?php echo esc_html($idioma['nivel']); ?>%</span>
                        </div>
                        <div class="idioma-bar" role="progressbar" aria-valuenow="<?php echo esc_attr($idioma['nivel']); ?>" aria-valuemin="0" aria-valuemax="100" aria-label="Progresso de idioma <?php echo esc_attr($idioma['nome']); ?>">
                            <div class="idioma-fill" style="width: <?php echo esc_attr($idioma['nivel']); ?>%; background: <?php echo esc_attr($this->obter_cor(intval($idioma['nivel']))); ?>;"></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }
}
