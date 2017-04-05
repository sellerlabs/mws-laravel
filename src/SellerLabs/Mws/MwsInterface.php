<?php
/**
 * Copyright 2017, SellerLabs <scope-devs@sellerlabs.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * This file is part of the SellerLabs package
 */

namespace SellerLabs\Mws;

use SimpleXMLElement;

interface MwsInterface {

    /**
     * Set the marketplace Id
     *
     * @url http://docs.developer.amazonservices.com/en_US/dev_guide/DG_Endpoints.html
     * @param $marketplaceId
     *
     * @return $this
     */
    public function setMarketplaceId($marketplaceId);

    /**
     * Call MWS to get the FBA, referral, and other fees for an ASIN.
     *
     * @param $asin
     * @param $listingPrice
     * @return array
     * @throws MwsException
     */
    public function getMyFeesEstimate($asin, $listingPrice);

    /**
     * Call MWS to get the basic Product information.
     *
     * @param string $asin
     *
     * @return SimpleXMLElement
     * @throws MwsException
     */
    public function getMatchingProductForId($asin);
}
