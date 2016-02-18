<?php

/* Copyright (c) 2015 EVO Payments International - All Rights Reserved.
*
* This software and documentation is subject to and made
* available only pursuant to the terms of an executed license
* agreement, and may be used only in accordance with the terms
* of said agreement. This software may not, in whole or in part,
* be copied, photocopied, reproduced, translated, or reduced to
* any electronic medium or machine-readable form without
* prior consent, in writing, from EVO Payments International, INC.
*
* Use, duplication or disclosure by the U.S. Government is subject
* to restrictions set forth in an executed license agreement
* and in subparagraph (c)(1) of the Commercial Computer
* Software-Restricted Rights Clause at FAR 52.227-19; subparagraph
* (c)(1)(ii) of the Rights in Technical Data and Computer Software
* clause at DFARS 252.227-7013, subparagraph (d) of the Commercial
* Computer Software--Licensing clause at NASA FAR supplement
* 16-52.227-86; or their equivalent.
*
* Information in this software is subject to change without notice
* and does not represent a commitment on the part of EVO Payments International.
* 
* Sample Code is for reference Only and is intended to be used for educational purposes. It's the responsibility of 
* the software company to properly integrate into thier solution code that best meets thier production needs. 
*/

/**
 * Order class.
 * 
 * @author Antonio Molinero <antonio.molinero@evopayments.com>
 * @copyright Copyright (c) 2015 EVO Snap* (http://www.evosnap.com)
 * @license	EVO Payments International EULA
 */
class SnapOrder {
	
	/**
	 * Order ID.
	 * @var string
	 */
	public $id;

	/**
	 * Bill to address.
	 * @var SnapAddress
	 */
	public $billto_address;
	
	/**
	 * Ship to address.
	 * @var SnapSnapAddress
	 */
	public $shipto_address;
	
	/**
	 * Array of order lines.
	 * @var array(OrderLine)
	 */
	public $lines;
	
	/**
	 * Subtotal amount.
	 * @var float
	 */
	public $total_subtotal;
	
	/**
	 * Total discounts amount.
	 * @var float
	 */
	public $total_discount;
	
	/**
	 * Tax amount.
	 * @var float
	 */
	public $total_tax;
	
	/**
	 * Shipping amount. Any wrapping amount should be included here.
	 * @var float
	 */
	public $total_shipping;
	
	/**
	 * Total amount.
	 * @var float
	 */
	public $total;
	
	/**
	 * Ship method.
	 * @var string
	 */
	public $ship_method;
	
	/**
	 * ISO currency code.
	 * @var string
	 */
	public $currency_code;
	
	/**
	 * Metadata.
	 * @var array
	 */
	public $metadata;
	
}