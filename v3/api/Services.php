<?php

class Services
{
    public $conn;
    public $firms;
    public $contacts;
    public $workshops;
    public $stats;
    public $meets;
    public $gifts;
    public $events;
    public $campaigns;
    public $practices;
    public $cvInvitations;

    public function __construct($conn)
    {
        $this->conn = $conn;
        $this->firms = new firms($conn);
        $this->contacts = new contacts($conn);
        $this->workshops = new workshops($conn);
        $this->stats = new stats($conn);
        $this->meets = new meets($conn);
        $this->gifts = new gifts($conn);
        $this->events = new events($conn);
        $this->campaigns = new campaigns($conn);
        $this->practices = new practices($conn);
        $this->cvInvitations = new cvInvitations($conn);
    }
}
