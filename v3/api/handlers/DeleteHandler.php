<?php

class DeleteHandler
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
        $id = $uri[2] ?? '';

        switch ($resource) {
            case 'campaignContacts':
                $this->response->output($this->services->campaigns->deleteCampaignContacts($id, $input));
                return;

            case 'campaign':
                $this->response->output($this->services->campaigns->delete($id));
                return;

            case 'contacts':
                $this->response->output($this->services->contacts->deleteContact($id));
                return;

            case 'events':
                $this->response->output($this->services->events->delete($id));
                return;

            case 'workshops':
                $this->response->output($this->services->workshops->delete($id));
                return;

            case 'firms':
                $this->response->output($this->services->firms->delete($id));
                return;

            case 'meets':
                $this->response->output($this->services->meets->delete($id));
                return;

            case 'gifts':
                $this->response->output($this->services->gifts->delete($id));
                return;

            case 'column':
                $this->response->output($this->services->firms->deleteColmn($id));
                return;

            case 'practices':
                $this->response->output($this->services->practices->delete($id));
                return;

            default:
                $this->response->output("err");
                return;
        }
    }
}
