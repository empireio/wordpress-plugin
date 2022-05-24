<?php

namespace Organic;

class Affiliate {
    /**
     * @var Organic
     */
    private $organic;

    public function __construct( Organic $organic ) {
        $this->organic = $organic;
        add_action( 'init', [ $this, 'register_gutenberg_block' ] );
        add_action( 'wp_enqueue_scripts', [ $this, 'register_scripts' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'register_scripts' ] );
    }

    public function register_scripts() {
        $siteId = $this->organic->getSiteId();
        $sdk_url = getenv( 'ORGANIC_SDK_URL' );
        if ( ! $sdk_url ) {
            $sdk_url = 'https://organiccdn.io/assets/sdk/sdkv2.js';
        }
        $link_domains = [];
        if ( $this->organic->getSitePublicDomain() ) {
            array_push( $link_domains, 'https://' . $this->organic->getSitePublicDomain() );
        }
        if ( $this->organic->getSiteOrganicDomain() ) {
            array_push( $link_domains, 'https://' . $this->organic->getSiteOrganicDomain() );
        }
        // prefer custom subdomain like organic.example.com
        $public_domain = $this->organic->getSitePublicDomain() ?: $this->organic->getSiteOrganicDomain();
        wp_enqueue_script( 'organic-sdk-config', plugins_url( 'affiliate/config.js', __DIR__ ) );
        wp_localize_script(
            'organic-sdk-config',
            'organic_sdk_config',
            [
                'siteGuid' => $siteId,
                'publicDomain' => 'https://' . $public_domain,
                'linkDomains' => $link_domains,
            ],
        );
        wp_enqueue_script( 'organic-sdk', $sdk_url . '?guid=' . $siteId, [ 'organic-sdk-config' ], null, true );
        wp_register_script(
            'organic-affiliate',
            plugins_url( 'affiliate/build/index.js', __DIR__ ),
            [ 'organic-sdk' ],
        );
    }

    public function register_gutenberg_block() {
        register_block_type(
            plugin_dir_path( __DIR__ ) . 'affiliate/build/',
        );
    }

}
