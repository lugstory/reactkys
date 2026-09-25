<?php

class Router
{
    private Services $services;
    private Response $response;

    public function __construct(Services $services, Response $response)
    {
        $this->services = $services;
        $this->response = $response;
    }

    public function dispatch(): void
    {
        $url = $_SERVER['REQUEST_URI'] ?? '';
        $url = str_replace("/rest.php", "", $url);
        $url = str_replace("/v3", "", $url);
        $url = str_replace("//", "/", $url);

        $method = $_SERVER["REQUEST_METHOD"] ?? 'GET';
        $uri = explode('/', $url);

        if (isset($uri[1]) && $uri[1] === 'session') {
            print_r($_SESSION);
            print_r($_COOKIE);
            exit;
        }

        $raw = file_get_contents('php://input');
        $input = json_decode($raw, true);

        switch ($method) {
            case 'GET':
                $handler = new GetHandler($this->services, $this->response);
                $handler->handle($uri, $input);
                break;

            case 'POST':
                $handler = new PostHandler($this->services, $this->response);
                $handler->handle($uri, $input);
                break;

            case 'PUT':
                $handler = new PutHandler($this->services, $this->response);
                $handler->handle($uri, $input);
                break;

            case 'DELETE':
                $handler = new DeleteHandler($this->services, $this->response);
                $handler->handle($uri, $input);
                break;

            default:
                $this->response->output("err");
                break;
        }
    }
}
