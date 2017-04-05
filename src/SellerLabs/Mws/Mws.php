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
use MarketplaceWebServiceProducts_Model_GetMyFeesEstimateRequest as GMF;
use MarketplaceWebServiceProducts_Model_GetMatchingProductForIdRequest as GMP;
use MarketplaceWebServiceProducts_Model_IdListType as IL;
use SimpleXMLElement;

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
     *
     * @param Repository $config
     */
    public function __construct(Repository $config)
    {
        $this->config = $config;
    }

    /**
     * Set the marketplace Id
     *
     * @url http://docs.developer.amazonservices.com/en_US/dev_guide/DG_Endpoints.html
     * @param $marketplaceId
     *
     * @return $this
     */
    public function setMarketplaceId($marketplaceId)
    {
        $this->marketplaceId = $marketplaceId;

        return $this;
    }

    /**
     * Get the products API client.
     *
     * Each Amazon MWS endpoint has a separate client. If we expand this
     * class and use more of those endpoints, we'll need to create a
     * get...Client for each of those.
     *
     * @return MarketplaceWebServiceProducts_Mock|MarketplaceWebServiceProducts_Client
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
     * @param string $asin
     * @param int|float $listingPrice
     *
     * @return array
     * @throws MwsException
     */
    public function getMyFeesEstimate($asin, $listingPrice)
    {
        $request = new GMF(
            [
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
            ]
        );

        $response = $this->getProductsClient()->getMyFeesEstimate($request);

        $xml = simplexml_load_string(
            $response->toXML()
        )->GetMyFeesEstimateResult->FeesEstimateResultList->FeesEstimateResult;

        $this->throwExceptionOnError($xml);

        $detail = [];
        $feesEstimate = $xml->FeesEstimate;
        foreach ($feesEstimate->FeeDetailList->FeeDetail as $fee) {
            $detail[(string) $fee->FeeType] = (float) $fee->FinalFee->Amount;
        }

        return [
            'total' => (float) $feesEstimate->TotalFeesEstimate->Amount,
            'detail' => $detail,
        ];
    }

    /**
     * Call MWS to get the basic Product information.
     *
     * @param string $asin
     *
     * @return SimpleXMLElement
     */
    public function getMatchingProductForId($asin)
    {
        $request = new GMP();
        $request->setSellerId($this->config->get('mws.seller_id'));
        $idList = new IL();
        $idList->setId($asin);
        $request->setIdList($idList);
        $request->setIdType('ASIN');

        $request->setMarketplaceId($this->marketplaceId);

        $response = $this->getProductsClient()
            ->getMatchingProductForId($request);
        $xml = simplexml_load_string(
            str_replace('ns2:','',$response->toXML())
        )->GetMatchingProductForIdResult;
        $this->throwExceptionOnError($xml);

        return $xml->Products->Product;
    }

    /**
     * This is a hack. The mock classes built in to Amazon's MWS will return
     * 'String' for the status. The real API returns 'Success'. This allows
     * us to set this fake config in testing so we can use Amazon's mocks.
     *
     * NOTE: response status is not consistent. It could be an element or an
     * attribute.
     *
     * @param SimpleXMLElement $response
     *
     * @throws MwsException
     */
    private function throwExceptionOnError(SimpleXMLElement $response)
    {
        $successString = $this->config->get('mws.success_string', 'Success');
        if ((string) $response->Status != $successString
            && (string) $response['status'] != $successString
        ) {
            $error = $response->Error;
            throw new MwsException(
                sprintf(
                    "%s (%s): %s",
                    $error->Code,
                    $error->Type,
                    $error->Message
                )
            );
        }
    }
}
