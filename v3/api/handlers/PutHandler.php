<?php

class PutHandler
{
    private Services $services;
    private Response $response;

    public function __construct(Services $services, Response $response)
    {
        $this->services = $services;
        $this->response = $response;
    }

    public function handle(array $uri, ?array $input): void
    {
        $resource = $uri[1] ?? '';

        switch ($resource) {
            case 'campaigns':
                $this->response->output($this->services->campaigns->update($input));
                return;

            case 'firms':
                $this->response->output($this->services->firms->updateFirm($input));
                return;

            case 'events':
                $this->response->output($this->services->events->update($input));
                return;

            case 'contacts':
                $this->response->output($this->services->contacts->updateContacts($input));
                return;

            case 'workshops':
                $this->response->output($this->services->workshops->update($input));
                return;

            case 'meets':
                $this->response->output($this->services->meets->update($input));
                return;

            case 'gifts':
                $this->response->output($this->services->gifts->update($input));
                return;

            case 'column':
                $this->response->output($this->services->firms->updateColmn($input));
                return;

            default:
                $this->response->output("err");
                return;
        }
    }
}
