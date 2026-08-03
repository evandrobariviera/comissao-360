<?php

declare(strict_types=1);

namespace App\Core;

use RuntimeException;

abstract class Controller
{
    protected function render(string $view, array $data = [], ?string $layout = 'layout/base'): void
    {
        $viewPath = APP_DIR . '/Views/' . $view . '.php';
        if (!is_file($viewPath)) {
            throw new RuntimeException("View não encontrada: {$view}");
        }

        extract($data, EXTR_SKIP);
        ob_start();
        require $viewPath;
        $content = ob_get_clean();

        if ($layout === null) {
            echo $content;
            return;
        }

        $layoutPath = APP_DIR . '/Views/' . $layout . '.php';
        if (!is_file($layoutPath)) {
            throw new RuntimeException("Layout não encontrado: {$layout}");
        }
        require $layoutPath;
    }

    protected function redirect(string $path): void
    {
        header('Location: ' . $path);
        exit;
    }

    protected function input(string $key, mixed $default = null): mixed
    {
        return $_POST[$key] ?? $_GET[$key] ?? $default;
    }

    protected function requireCsrf(): void
    {
        $token = $this->input('_csrf');
        if (!Csrf::validate(is_string($token) ? $token : null)) {
            http_response_code(419);
            exit('Sessão expirada. Volte à página anterior e tente novamente.');
        }
    }
}
