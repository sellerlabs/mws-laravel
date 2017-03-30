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

use SellerLabs\Mws\MwsException;

interface MwsInterface {

    /**
     * Call MWS to get the FBA, referral, and other fees for an ASIN.
     *
     * @param $asin
     * @param $listingPrice
     * @return array
     * @throws MwsException
     */
    public function getMyFeesEstimate($asin, $listingPrice);
}
