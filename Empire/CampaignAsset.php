<?php

namespace Empire;

class CampaignAsset {
    private $guid;
    private $name;
    private $externalId;
    private $campaign;

    public function __construct( $guid, $name, $externalId, Campaign $campaign ) {
        $this->guid = $guid;
        $this->name = $name;
        $this->externalId = $externalId;
        $this->campaign = $campaign;
    }

    public function getGUID() {
        return $this->guid;
    }

    public function getName() {
        return $this->name;
    }

    public function getExternalID() {
        return $this->externalId;
    }

    public function getCampaign() {
        return $this->campaign;
    }
}
