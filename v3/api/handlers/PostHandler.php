<?php

class PostHandler
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
        $sub = $uri[2] ?? '';

        switch ($resource) {
            case 'cvinvitations':
                $this->response->output($this->services->cvInvitations->save($input));
                return;

            case 'campaigns':
                $this->response->output($this->services->campaigns->insert($input));
                return;

            case 'getCampaignSeindingExport':
                $this->response->output($this->services->campaigns->getCampaignSeindingExport($sub, $input));
                return;

            case 'campaignContacts':
                $this->response->output($this->services->campaigns->campaignContactsUpdate($sub, $input));
                return;

            case 'events':
                $this->response->output($this->services->events->insert($input));
                return;

            case 'firms':
                $this->response->output($this->services->firms->insert($input));
                return;

            case 'contacts':
                $this->response->output($this->services->contacts->insertContacts($input));
                return;

            case 'workshops':
                $this->response->output($this->services->workshops->insert($input));
                return;

            case 'columns':
                $this->response->output($this->services->firms->saveColmVisibility($input));
                return;

            case 'gifts':
                $this->response->output($this->services->gifts->insert($input));
                return;

            case 'column':
                $this->response->output($this->services->firms->addColm($input["name"] ?? '', $input["type"] ?? ''));
                return;

            case 'meets':
                $this->response->output($this->services->meets->insert($input));
                return;

            case 'practices':
                $this->response->output($this->services->practices->save($input));
                return;

            default:
                $this->response->output("err");
                return;
        }
    }
}
