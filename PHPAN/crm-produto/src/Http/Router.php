<?php

declare(strict_types=1);

namespace App\Http;

final class Router
{
    /** @var list<array{method:string, pattern:string, handler:callable}> */
    private array $routes = [];

    public function get(string $pattern, callable $handler): void
    {
        $this->add('GET', $pattern, $handler);
    }

    public function post(string $pattern, callable $handler): void
    {
        $this->add('POST', $pattern, $handler);
    }

    public function put(string $pattern, callable $handler): void
    {
        $this->add('PUT', $pattern, $handler);
    }

    public function patch(string $pattern, callable $handler): void
    {
        $this->add('PATCH', $pattern, $handler);
    }

    public function delete(string $pattern, callable $handler): void
    {
        $this->add('DELETE', $pattern, $handler);
    }

    private function add(string $method, string $pattern, callable $handler): void
    {
        $this->routes[] = ['method' => $method, 'pattern' => $pattern, 'handler' => $handler];
    }

    /** Resolve a rota e devolve a Response — sem enviar nada (isso deixa testável). */
    public function resolver(Request $request): Response
    {
        foreach ($this->routes as $route) {
            if ($route['method'] !== $request->method) {
                continue;
            }

            $params = $this->casar($route['pattern'], $request->path);

            if ($params === null) {
                continue;
            }

            // $params é associativo: o spread vira ARGUMENTOS NOMEADOS.
            // O handler precisa ter um parâmetro com o mesmo nome do {placeholder}.
            return ($route['handler'])($request, ...$params);
        }

        return PaginaDeErro::naoEncontrado();
    }

    public function dispatch(Request $request): void
    {
        $this->resolver($request)->send();
    }

    /** @return array<string,string>|null */
    private function casar(string $pattern, string $path): ?array
    {
        $regex = preg_replace('#\{([a-zA-Z_][a-zA-Z0-9_]*)\}#', '(?P<$1>[^/]+)', $pattern);

        if (!is_string($regex) || preg_match('#^' . $regex . '$#', $path, $matches) !== 1) {
            return null;
        }

        /** @var array<string,string> $nomeados */
        $nomeados = array_filter($matches, static fn (int|string $k): bool => is_string($k), ARRAY_FILTER_USE_KEY);

        return $nomeados;
    }
}
