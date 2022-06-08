<?php

namespace Organic;

class AmpAdsInjector extends \AMP_Base_Sanitizer {
    private $organic;
    private $adsInjector;
    private $connatixInjected = false;
    private $targeting = null;

    public function sanitize() {
        try {
            $this->adsInjector = new AdsInjector(
                $this->dom,
                function( $html ) {
                    $document = $this->dom::fromHtmlFragment( $html );
                    return $document->getElementsByTagName( 'body' )->item( 0 );
                }
            );
            $this->organic = Organic::getInstance();
            $this->targeting = $this->organic->getTargeting();
            $this->handle();
        } catch ( \Exception $e ) {
            \Organic\Organic::captureException( $e );
        }
    }

    public function handle() {
        $ampConfig = $this->args['ampConfig'];
        $adsConfig = $this->args['adsConfig'];

        $rule = $this->adsInjector::getBlockRule( $adsConfig->adRules, $this->targeting );
        $blockedKeys = ( $rule ? $rule['placementKeys'] : [] ) ?? [];

        // all placements are blocked by rule
        if ( $rule && ! $blockedKeys ) {
            return;
        }

        foreach ( $ampConfig->forPlacement as $key => $amp ) {
            $placement = $adsConfig->forPlacement[ $key ];

            $selectors = $placement['selectors'];
            $limit = $placement['limit'];
            $relative = $placement['relative'];

            // certain placement is blocked
            if ( $rule && in_array( $key, $blockedKeys ) ) {
                continue;
            }

            $adHtml = $this->applyTargeting( $amp['html'], $this->targeting );
            try {
                $this->adsInjector->injectAds( $adHtml, $relative, $selectors, $limit );
            } catch ( \Exception $e ) {
                \Organic\Organic::captureException( $e );
            }
        }

        $this->injectConnatix();
    }

    private function injectConnatix() {
        if ( $this->connatixInjected ) {
            return;
        }

        if ( ! $this->organic->useConnatix() || ! is_single() ) {
            return;
        }

        $psid = $this->organic->getConnatixPlayspaceId();
        $player = "
            <amp-connatix-player
                data-player-id=\"ps_$psid\"
                layout=\"responsive\"
                width=\"16\"
                height=\"9\"
            >
            </amp-connatix-player>";

        $this->adsInjector->injectAds(
            $player,
            'after',
            [ 'p:first-child', 'span:first-child' ],
            1
        );

        $this->connatixInjected = true;
    }

    public function applyTargeting( $html, $values ) {
        $targeting = [
            'amp' => 1,
            'site' => $values['siteDomain'],
            'article' => $values['gamPageId'],
            'targeting_article' => $values['gamExternalId'],
        ];

        $keywords = $values['keywords'];
        if ( ! empty( $keywords ) ) {
            $targeting['content_keyword'] = $keywords;
            $targeting['targeting_keyword'] = $keywords;
        }

        $category = $values['category'];
        if ( ! is_null( $category ) ) {
            $targeting['site_section'] = $category->slug;
            $targeting['targeting_section'] = $category->slug;
        }

        $json = json_encode( [ 'targeting' => $targeting ] );
        return str_replace( 'json="{}"', 'json=' . $json, $html );
    }
}
