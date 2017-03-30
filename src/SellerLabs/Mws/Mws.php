<?php

/**
 * Copyright 2015-2016, SellerLabs <scope-devs@sellerlabs.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * This file is part of the SellerLabs package
 */

namespace SellerLabs\Mws;

use Illuminate\Contracts\Config\Repository;
use MarketplaceWebServiceProducts_Client;
use MarketplaceWebServiceProducts_Mock;
use MarketplaceWebServiceProducts_Model_GetMyFeesEstimateRequest;

/**
 * Class Mws.
 *
 * @author Dennis S. Hennen <dennis@sellerlabs.com>
 * @package SellerLabs\Scope\Support
 */
class Mws implements MwsInterface
{
    /**
     * @var Repository
     */
    private $config;
    
    private $serviceUrl = 'https://mws.amazonservices.com/Products/2011-10-01';
    // Only US marketplace for now.
    private $marketplaceId = 'ATVPDKIKX0DER';

    /**
     * Mws constructor.
     * @param Repository $config
     */
    public function __construct(Repository $config)
    {
        $this->config = $config;
    }

    /**
     * Get the products API client.
     *
     * Each Amazon MWS endpoint has a separate client. If we expand this
     * class and use more of those endpoints, we'll need to create a
     * get...Client for each of those.
     *
     * @return MarketplaceWebServiceProducts_Mock or MarketplaceWebServiceProducts_Client
     */
    private function getProductsClient()
    {
        $class = $this->config->get('mws.mock', false)
            ? MarketplaceWebServiceProducts_Mock::class
            : MarketplaceWebServiceProducts_Client::class;
        $key = $this->config->get('mws.aws_key');
        $secret = $this->config->get('mws.aws_secret');
        return new $class(
            $key,
            $secret,
            'sellerlabs',
            '1',
            ['ServiceURL' => $this->serviceUrl]
        );
    }

    /**
     * Call MWS to get the FBA, referral, and other fees for an ASIN.
     *
     * @param $asin
     * @param $listingPrice
     * @return array
     * @throws MwsException
     */
    public function getMyFeesEstimate($asin, $listingPrice)
    {
        $request = new MarketplaceWebServiceProducts_Model_GetMyFeesEstimateRequest([
            'SellerId' => $this->config->get('mws.seller_id'),
            'FeesEstimateRequestList' => [
                'FeesEstimateRequest' => [
                    'MarketplaceId' => $this->marketplaceId,
                    'IdType' => 'ASIN',
                    'IdValue' => $asin,
                    'Identifier' => 'identifier',
                    'IsAmazonFulfilled' => true,
                    'PriceToEstimateFees' => [
                        'ListingPrice' => [
                            'CurrencyCode' => 'USD',
                            'Amount' => $listingPrice,
                        ],
                    ],
                ],
            ],
        ]);

        $response = $this->getProductsClient()->GetMyFeesEstimate($request);

        $xml = simplexml_load_string($response->toXML())->GetMyFeesEstimateResult->FeesEstimateResultList->FeesEstimateResult;
        // This is a hack. The mock classes built in to Amazon's MWS will return 'String' for the status. The real
        // API returns 'Success'. This allows us to set this fake config in testing so we can use Amazon's mocks.
        $successString = $this->config->get('mws.success_string', 'Success');
        if ($xml->Status != $successString) {
            $error = $xml->Error;
            throw new MwsException(sprintf(
                "%s (%s): %s",
                $error->Code,
                $error->Type,
                $error->Message
            ));
        }
        $detail = [];
        $feesEstimate = $xml->FeesEstimate;
        foreach ($feesEstimate->FeeDetailList->FeeDetail as $fee) {
            $detail[(string)$fee->FeeType] = (float)$fee->FinalFee->Amount;
        }
        return [
            'total' => (float)$feesEstimate->TotalFeesEstimate->Amount,
            'detail' => $detail,
        ];
    }
}
