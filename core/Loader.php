<?php


class Loader
{
    public function __construct()
    {
        // The constructor remains lightweight.
    }

    public function run()
    {
        $this->load_dependencies();
        $this->init_hooks();

        if (function_exists('error_log')) {
            error_log('[Gerenciador Saas] Loader::run executed.');
        }
    }

    private function load_dependencies()
    {
        $files = [
            GERENCIADOR_SAAS_PATH . 'controllers/DashboardController.php',
            GERENCIADOR_SAAS_PATH . 'controllers/Login.php',
        ];

        foreach ($files as $file) {
            if (file_exists($file)) {
                require_once $file;
                continue;
            }

            if (function_exists('error_log')) {
                error_log('[Gerenciador Saas] Missing file: ' . $file);
            }
        }
    }

    private function init_hooks()
    {
        if (class_exists('DashboardController')) {
            new DashboardController();
        } else {
            if (function_exists('error_log')) {
                error_log('[Gerenciador Saas] DashboardController class not found.');
            }
        }
    }
}
