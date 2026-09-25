<?php

class GetHandler
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
        $action = $uri[3] ?? '';

        switch ($resource) {
            case 'user':
                $this->handleUser();
                return;

            case 'copyCampaign':
                if (!empty($sub)) {
                    $this->response->output($this->services->campaigns->copyCampaign($sub));
                }
                return;

            case 'cvinvitations':
                $this->response->output($this->services->cvInvitations->getcvIvnvitatios($sub));
                return;

            case 'campaignAttachment':
                if (!empty($sub)) {
                    $this->response->downloadAttachment($sub);
                }
                return;

            case 'campaignExport':
                $this->response->output($this->services->campaigns->getCampaignExport($sub !== '' ? $sub : 0));
                return;

            case 'campaigns':
                if ($sub === 'getCampaignSending') {
                    $this->response->output($this->services->campaigns->getCampaignSending($action !== '' ? $action : 0));
                } else {
                    $this->response->output($this->services->campaigns->getCampaigns());
                }
                return;

            case 'getCampaignContacts':
                if (!empty($sub)) {
                    $this->response->output($this->services->campaigns->getCampaignContacts($sub));
                }
                return;

            case 'campaign':
                if (!empty($sub)) {
                    $this->response->output($this->services->campaigns->getCampaign($sub));
                }
                return;

            case 'event':
                if (!empty($sub)) {
                    $this->response->output($this->services->events->getevent($sub));
                }
                return;

            case 'checkfirmExist':
                $this->response->output($this->services->firms->checkIfFirmExist($sub));
                return;

            case 'events':
                if ($sub === 'generateICS') {
                    $this->services->events->generateICS($action);
                } elseif ($sub === 'getFutureEvents') {
                    $this->response->output($this->services->events->getFutureEvents());
                } else {
                    $this->response->output($this->services->events->getEvents($sub !== '' ? $sub : null));
                }
                return;

            case 'contacts':
                if ($sub === 'search') {
                    $this->response->output($this->services->contacts->search($action));
                } elseif ($sub === 'exportVcf') {
                    $exporter = new ContactVcfExporter($this->services->conn);
                    $exporter->export($input);
                    exit;
                } else {
                    $this->response->output($this->services->contacts->getFirmContacts($sub));
                }
                return;

            case 'firm':
                if ($sub === 'contactsList') {
                    $this->response->output($this->services->firms->contactsList());
                } else {
                    $this->response->output($this->services->firms->getFirm($sub));
                }
                return;

            case 'firms':
                if ($sub === 'list') {
                    if ($action === 'filter') {
                        $this->response->output($this->services->firms->getFirmsFilter($_GET));
                    } else {
                        $this->response->output($this->services->firms->getFirms());
                    }
                } elseif ($sub === 'getFirmsNotCont') {
                    $this->response->output($this->services->firms->getFirmsNotCont());
                } elseif ($sub === 'form') {
                    if (!empty($action)) {
                        $this->response->output($this->services->firms->getFirmAndForm($action));
                    } else {
                        $this->response->output($this->services->firms->getFirmForm());
                    }
                }
                return;

            case 'workshops':
                $this->response->output($this->services->workshops->getworkshops($sub));
                return;

            case 'stats':
                $this->handleStats($sub, $action);
                return;

            case 'columnsFilter':
                $this->response->output($this->services->firms->getColmVisibilityFilter());
                return;

            case 'columns':
                $this->response->output($this->services->firms->getColmVisibility());
                return;

            case 'columnsList':
                $this->response->output($this->services->firms->getColms());
                return;

            case 'meets':
                $this->response->output($this->services->meets->getMeets($sub));
                return;

            case 'gifts':
                $this->response->output($this->services->gifts->getgifts($sub));
                return;

            case 'practices':
                $this->response->output($this->services->practices->getpractices($sub !== '' ? $sub : 0));
                return;
        }

        // Backward compatibility fallbacks for routes without resource prefix
        if ($sub === 'list' && $action === 'filter') {
            $this->response->output($this->services->firms->getFirmsFilter($_GET));
            return;
        }
        if ($sub === 'list') {
            $this->response->output($this->services->firms->getFirms());
            return;
        }
        if ($sub === 'getFirmsNotCont') {
            $this->response->output($this->services->firms->getFirmsNotCont());
            return;
        }
        if ($sub === 'form') {
            if (!empty($action)) {
                $this->response->output($this->services->firms->getFirmAndForm($action));
            } else {
                $this->response->output($this->services->firms->getFirmForm());
            }
            return;
        }

        $this->response->output("err");
    }

    private function handleStats(string $sub, string $action): void
    {
        $year = !empty($action) ? intval($action) : 0;
        switch ($sub) {
            case 'invitations':
                $this->response->output($this->services->stats->getInvitations(1, $year));
                break;
            case 'cvcount':
                $this->response->output($this->services->stats->getCvCount($year));
                break;
            case 'practices':
                $this->response->output($this->services->stats->getAllPractices($year));
                break;
            case 'getStatBySYears':
                $this->response->output($this->services->stats->getStatBySYears());
                break;
            case 'getAllCVInvitations':
                $this->response->output($this->services->stats->getAllCVInvitations());
                break;
            case 'getFirmStats':
                $this->response->output($this->services->stats->getFirmStats());
                break;
            case 'export':
                $this->response->output($this->services->stats->export());
                break;
            case 'getAllWSs':
                $this->response->output($this->services->stats->getAllWSs($year));
                break;
            case 'getAllGifts':
                $this->response->output($this->services->stats->getAllGifts($year));
                break;
            case 'getAllMeets':
                $this->response->output($this->services->stats->getAllMeets($year));
                break;
            case 'getTopCompanies':
                $this->response->output($this->services->stats->getTopCompanies($year));
                break;
            case 'getAllNotActivity':
                $this->response->output($this->services->stats->getAllNotActivity($year));
                break;
            default:
                $this->response->output($this->services->stats->getAll());
                break;
        }
    }

    private function handleUser(): void
    {
        if (isset($_SESSION["user"])) {
            if ($_SESSION["user"] !== null) {
                $this->response->output(["user" => $_SESSION["user"]]);
            } else {
                $this->response->output(["user" => "reader"]);
            }
        } else {
            if (isset($_COOKIE['localhostUser'])) {
                switch ($_COOKIE['localhostUser']) {
                    case 'admin':
                        $this->response->output(["user" => "admin"]);
                        break;
                    case 'user':
                        $this->response->output(["user" => "reader"]);
                        break;
                    default:
                        $this->response->output(["user" => "admin"]);
                        break;
                }
            } else {
                $this->response->output(["user" => "admin"]);
            }
        }
    }
}
