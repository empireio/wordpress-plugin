<?php

namespace Organic;

class AmpAdsInjector extends \AMP_Base_Sanitizer {
    public function sanitize() {
        try {
            $this->handle();
        } catch ( \Exception $e ) {
            \Organic\Organic::captureException( $e );
        }
    }

    public function handle() {
        $ampConfig = $this->args['ampConfig'];
        $adsConfig = $this->args['adsConfig'];
        $targeting = $this->args['getTargeting']();

        $adsInjector = new AdsInjector(
            $this->dom,
            function( $html ) {
                $document = $this->dom::fromHtmlFragment( $html );
                return $document->getElementsByTagName( 'body' )->item( 0 );
            }
        );

        $rule = $adsInjector->getBlockRule( $adsConfig->adRules, $targeting );
        $blockedKeys = ( $rule ? $rule['placementKeys'] : [] ) ?? [];

        // all placements are blocked by rule
        if ( $rule && ! $blockedKeys ) {
            return;
        }

        foreach ( $ampConfig->forPlacement as $key => $amp ) {
            $placement = $adsConfig->forPlacement[ $key ];

            [
                'selectors' => $selectors,
                'limit' => $limit,
                'relative' => $relative,
            ] = $placement;

            // certain placement is blocked
            if ( $rule && in_array( $key, $blockedKeys ) ) {
                continue;
            }

            $adHtml = $this->applyTargeting( $amp['html'], $targeting );
            try {
                $adsInjector->injectAds( $adHtml, $relative, $selectors, $limit );
            } catch ( \Exception $e ) {
                \Organic\Organic::captureException( $e );
            }
        }
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
