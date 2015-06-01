<?php
require_once __DIR__ . '/badge.php';
require_once __DIR__ . '/client.php';

/**
 * Class for storing the badges fetched from Open Badge Factory.
 */
class obf_badge_collection {

    /**
     * @var obf_badge[] The badges in this collection.
     */
    private $badges = array();

    /**
     * @var obf_client The client communicating with OBF. 
     */
    private $client = null;
    
    /**
     * Initalizes the collection.
     * 
     * @param obf_client $client The client communicating with OBF
     */
    public function __construct(obf_client $client) {
        $this->client = $client;
    }

    /**
     * Returns a single badge from this collection.
     * 
     * @param string $badgeid The id of the badge.
     * @return obf_badge Returns the badge if it exists in this collection and
     *      null otherwise.
     */
    public function get_badge($badgeid) {
        return isset($this->badges[$badgeid]) ? $this->badges[$badgeid] : null;
    }

    /**
     * Populates this collection by fetching the badges from the OBF.
     */
    public function populate() {
        $badges = $this->client->get_badges();

        foreach ($badges as $badge) {
            $this->badges[$badge['id']] = obf_badge::get_instance_from_array($badge);
        }
    }

}