<?php
require_once "helper.php";
require_once "firms/firms.php";
require_once "firms/contacts.php";
require_once "firms/workshops.php";
require_once "firms/stats.php";
require_once "firms/meets.php";
require_once "firms/gifts.php";
require_once "firms/events.php";
require_once "firms/campaign.php";
require_once "firms/practice.php";
require_once "firms/cvinvitations.php";
require_once "firms/ContactVcfExporter.php";

require_once "api/Services.php";
require_once "api/Response.php";
require_once "api/handlers/GetHandler.php";
require_once "api/handlers/PostHandler.php";
require_once "api/handlers/PutHandler.php";
require_once "api/handlers/DeleteHandler.php";
require_once "api/Router.php";

class requests
{
    private Services $services;
    private Response $response;
    private Router $router;

    public function __construct($conn)
    {
        $this->services = new Services($conn);
        $this->response = new Response($this->services);
        $this->router = new Router($this->services, $this->response);
        $this->router->dispatch();
    }
}
