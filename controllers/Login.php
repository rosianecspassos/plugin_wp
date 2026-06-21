<?php

if (!defined('ABSPATH')) {
    exit;
}

// 1. Processa o login ANTES de carregar o HTML da página
add_action('init', 'processar_login_usuario');
add_shortcode('login_usuario', 'render_login');

function processar_login_usuario() {
    // Só age se o formulário foi enviado
    if (!isset($_POST['login_usuario'])) {
        return;
    }

    // Segurança: Verifica se os campos existem
    $username = isset($_POST['username']) ? sanitize_text_field($_POST['username']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';

    $creds = [
        'user_login'    => $username,
        'user_password' => $password,
        'remember'      => true
    ];

    $user = wp_signon($creds, false);

    if (!is_wp_error($user)) {
        // Verifica as roles permitidas
        if (in_array('usuarios_internos', $user->roles) || in_array('administrator', $user->roles)) {
            wp_redirect(home_url('/sistema-painel'));
            exit;
        }
    } else {
        // Se houver erro, podemos passar por URL ou usar uma Global para exibir no shortcode
        add_filter('login_errors_custom', function() { return "Login ou senha inválidos."; });
    }
}

// 2. Shortcode apenas exibe o formulário
function render_login() {
    // Se já estiver logado, redireciona via JavaScript (fallback seguro para dentro de shortcode)
    if (is_user_logged_in()) {
        echo '<script>window.location.href="' . home_url('/sistema-painel') . '";</script>';
        return;
    }

    ob_start();
    
    // Exibe mensagem de erro se o login falhou
    if (isset($_POST['login_usuario']) && !is_user_logged_in()) {
        echo "<p style='color:red;'>Usuário ou senha incorretos.</p>";
    }
    ?>

    <h2>Login</h2>
    <form method="post">
        <input type="text" name="username" placeholder="Usuário" required><br><br>
        <input type="password" name="password" placeholder="Senha" required><br><br>
        <button type="submit" name="login_usuario">Entrar</button>
    </form>

    <?php
    return ob_get_clean();
}