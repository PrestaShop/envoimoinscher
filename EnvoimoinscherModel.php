<?php
/**
 * 2007-2014 PrestaShop
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to http://www.prestashop.com for more information.
 *
 * @author    EnvoiMoinsCher <informationapi@boxtale.com>
 * @copyright 2007-2014 PrestaShop SA / 2011-2014 EnvoiMoinsCher
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 * International Registred Trademark & Property of PrestaShop SA
 */

class EnvoimoinscherModel
{

	private $db;
	protected $module_name;

	/**
	* List with offers order (by price or date)
	* @var array
	* @access protected
	*/
	protected $offers_order = array(
		0 => array(
			'emcValue' => 'aucun',
			'alias' => 'le prix'),
		1 => array(
			'emcValue' => 'minimum',
			'alias' => 'le délai'));
	/* About price */
	const RATE_PRICE          = 0;
	const REAL_PRICE          = 1;

	const WITHOUT_CHECK       = 0;
	const WITH_CHECK          = 1;
	/* Multi-parcel */
	const MULTI_PARCEL        = 1;
	/* Zone (type) */
	const ZONE_FRANCE   			= 1;
	const ZONE_INTER 					= 2;
	const ZONE_EUROPE         = 3;
	/* Family */
	const FAM_ECONOMIQUE      = 1;
	const FAM_EXPRESSISTE     = 2;
	/* Module mode */
	const MODE_CONFIG         = 'config';
	const MODE_ONLINE         = 'online';
	/* Track */
	const TRACK_EMC_TYPE      = 1;
	const TRACK_OPE_TYPE      = 2;

	public function __construct(Db $db, $module)
	{
		$this->db = $db;
		$this->module_name = $module;
	}

	/**
	* Gets configuration data for the module.
	* @access public
	* @return array List with configutarion data
	*/
	public function getConfigData()
	{
		return $this->db->ExecuteS('SELECT * FROM '._DB_PREFIX_.'configuration WHERE name LIKE "EMC_%"');
	}

	/**
	* Gets EnvoiMoinsCher's offers.
	* @access public
	* @param string $where Query clause.
	* @return array List with offers.
	*/
	public function getOffers($type = false, $family = false, $where = false)
	{
		$query = '
		 SELECT *, c.`emc_type` AS emc_type, CONCAT_WS("_", es.emc_operators_code_eo, es.code_es) AS `offerCode`
		 FROM `'._DB_PREFIX_.'emc_services` es
		 JOIN `'._DB_PREFIX_.'emc_operators` eo
		 ON eo.`code_eo` = es.`emc_operators_code_eo`
		 LEFT JOIN `'._DB_PREFIX_.'carrier` c
		 ON c.`emc_services_id_es` = es.`id_es` AND c.`deleted` = 0
		 WHERE 1';

		if ($type !== false && Validate::isInt($type))
			$query .= ' AND es.`type_es` = "'.(int)$type.'" ';

		// Get By family
		if ($family !== false && Validate::isInt($family))
			$query .= ' AND es.`family_es` = "'.(int)$family.'" ';

		if ($where !== false)
			$query .= ' '.$where.' ';

		$query .= '
			GROUP BY es.`id_es`
			ORDER BY eo.`name_eo` ASC, es.`label_es` ASC';

		return $this->db->ExecuteS($query);
	}

	/**
	 * Get offers by Family
	 * @param  [type] $family [description]
	 * @return [type]         [description]
	 */
	public function getOffersByFamily($family = false)
	{
		return $this->getOffers(false, $family);
	}

	public static function getOperatorsForType($type)
	{
		$query = 'SELECT * FROM `'._DB_PREFIX_.'emc_operators_categories` WHERE id_eca = "'.$type.'" ';

		$results = DB::getInstance()->executeS($query);
		$operators = array();

		foreach ($results as $result)
			$operators[] = $result['id_eo'];

		return $operators;
	}

	/**
	* Gets all dimensions.
	* @access public
	* @return array List with dimensions.
	*/
	public function getDimensions()
	{
		return $this->db->ExecuteS('SELECT * FROM '._DB_PREFIX_.'emc_dimensions');
	}

	/**
	* Gets all EnvoiMoinsCher orders and other orders which haven't been send yet.
	* @access public
	* @param array $params Query parameters.
	* @return array Orders list.
	*/
	public function getEligibleOrders($params)
	{
		$sql = 'SELECT *, oc.id_carrier AS carrierId,
			 c.name AS carrierName, cur.sign,
			 o.id_order AS idOrder, SUBSTRING(a.firstname, 1, 1) AS firstNameShort,
			 DATE_FORMAT(eo.date_del_eor, \'%d-%m-%Y\') AS dateDel,
			 DATE_FORMAT(eo.date_collect_eor, \'%d-%m-%Y\') AS dateCol,
			 DATE_FORMAT(eo.date_order_eor, \'%d-%m-%Y\') AS dateCom,
			 (UNIX_TIMESTAMP("'.date('Y-m-d H:i:s').'") - UNIX_TIMESTAMP(eo.date_order_eor)) AS timeDifference,
			 ROUND(eo.price_ttc_eor, 2) AS priceRound
			 FROM '._DB_PREFIX_.'orders o
			 JOIN '._DB_PREFIX_.'order_carrier oc
			 ON oc.id_order = o.id_order
			 JOIN '._DB_PREFIX_.'carrier c
			 ON c.id_carrier = oc.id_carrier
			 LEFT JOIN '._DB_PREFIX_.'carrier_lang cl
			 ON cl.id_carrier = c.id_carrier
			 JOIN '._DB_PREFIX_.'address a
			 ON a.id_address = o.id_address_delivery
			 JOIN '._DB_PREFIX_.'currency cur
			 ON o.id_currency = cur.id_currency
			 LEFT JOIN '._DB_PREFIX_.'emc_services es
			 ON es.id_es = c.emc_services_id_es
			 LEFT JOIN '._DB_PREFIX_.'emc_operators eop
			 ON eop.code_eo = es.emc_operators_code_eo
			 LEFT JOIN '._DB_PREFIX_.'order_history oh
			 ON oh.id_order = o.id_order AND oh.id_order_history = (
			 SELECT MAX(id_order_history)
			 FROM '._DB_PREFIX_.'order_history moh
			 WHERE moh.id_order = o.id_order
			 GROUP BY moh.id_order)
			 LEFT JOIN '._DB_PREFIX_.'order_state_lang osl
			 ON osl.id_order_state = oh.id_order_state AND osl.id_lang = '.(int)$params['lang'].'
			 LEFT JOIN '._DB_PREFIX_.'emc_orders eo
			 ON eo.'._DB_PREFIX_.'orders_id_order = o.id_order
			 LEFT JOIN '._DB_PREFIX_.'emc_documents d
			 ON d.'._DB_PREFIX_.'orders_id_order = o.id_order AND type_ed = "label"
			 LEFT JOIN '._DB_PREFIX_.'emc_orders_errors er
			 ON er.'._DB_PREFIX_.'orders_id_order = o.id_order
			 WHERE
			 (c.external_module_name = "envoimoinscher" OR o.delivery_number = 0)
			 AND eo.ref_emc_eor IS NULL
			 GROUP BY o.id_order
			 ORDER BY o.id_order DESC';

		$orders = $this->db->ExecuteS($sql);
		//we sort orders by EMC/other carriers
		$final = array('emc' => array(), 'others' => array(), 'errors' => array());
		foreach ($orders as $order)
		{
			$key = 'others';
			if ($order['errors_eoe'] != '')
				$key = 'errors';
			elseif ($order['external_module_name'] == 'envoimoinscher')
				$key = 'emc';
			$final[$key][] = $order;
		}
		return $final;
	}

	/**
	* Gets EnvoiMoinsCher realized orders.
	* @access public
	* @param array $params Query parameters.
	* @return array Orders list.
	*/
	public function getDoneOrders($params)
	{
		$sql = 'SELECT *, cur.sign,
			 o.id_order AS idOrder,
			 SUBSTRING(a.firstname, 1, 1) AS firstNameShort,
			 DATE_FORMAT(eo.date_del_eor, \'%d-%m-%Y\') AS dateDel,
			 DATE_FORMAT(eo.date_collect_eor, \'%d-%m-%Y\') AS dateCol,
			 DATE_FORMAT(eo.date_order_eor, \'%d-%m-%Y\') AS dateCom,
			 (UNIX_TIMESTAMP("'.date('Y-m-d H:i:s').'") - UNIX_TIMESTAMP(eo.date_order_eor)) AS timeDifference,
			 ROUND(eo.price_ttc_eor, 2) AS priceRound
			 FROM '._DB_PREFIX_.'emc_orders eo
			 JOIN '._DB_PREFIX_.'orders o
			 ON eo.'._DB_PREFIX_.'orders_id_order = o.id_order
			 JOIN '._DB_PREFIX_.'carrier c
			 ON c.id_carrier = o.id_carrier
			 JOIN '._DB_PREFIX_.'carrier_lang cl
			 ON cl.id_carrier = c.id_carrier
			 JOIN '._DB_PREFIX_.'address a
			 ON a.id_address = o.id_address_delivery
			 JOIN '._DB_PREFIX_.'currency cur
			 ON o.id_currency = cur.id_currency
			 JOIN '._DB_PREFIX_.'emc_services es
			 ON es.id_es = c.emc_services_id_es
			 JOIN '._DB_PREFIX_.'emc_operators eop
			 ON eop.code_eo = es.emc_operators_code_eo
			 LEFT JOIN '._DB_PREFIX_.'emc_documents d
			 ON d.'._DB_PREFIX_.'orders_id_order = o.id_order AND type_ed = "label"
			 LEFT JOIN '._DB_PREFIX_.'order_history oh
			 ON oh.id_order = o.id_order AND oh.id_order_history = (
			 SELECT MAX(id_order_history)
			 FROM '._DB_PREFIX_.'order_history moh
			 WHERE moh.id_order = o.id_order GROUP BY moh.id_order)
			 LEFT JOIN '._DB_PREFIX_.'order_state_lang osl
			 ON osl.id_order_state = oh.id_order_state AND osl.id_lang = '.(int)$params['lang'].'
			 WHERE eo.ref_emc_eor != "" AND c.external_module_name = "envoimoinscher" AND cl.id_lang = '.(int)$params['lang'].'
			 GROUP BY o.id_order
			 ORDER BY o.id_order DESC LIMIT '.(int)$params['start'].', '.(int)$params['limit'].' ';
		return $this->db->ExecuteS($sql);
	}

	/**
	* Gets dimensions by $weight parameter.
	* @access public
	* @param float $weight Weight used to get the dimensions.
	* @return array Dimensions array.
	*/
	public function getDimensionsByWeight($weight)
	{
		return $this->db->ExecuteS('SELECT * FROM '._DB_PREFIX_.'emc_dimensions
		 WHERE weight_from_ed < '.$weight.' AND weight_ed >= '.$weight.'');
	}

	/**
	* Prepares data used in the quotation and shipping order process.
	* @access public
	* @param int $order_id Id of order.
	* @param array $config Configuration data.
	* @return array List with data used to quotation and shipping order.
	*/
	public function prepareOrderInfo($order_id, $config)
	{
		$query = 'SELECT *, ap.point_eap,
				 a.firstname AS afirstname,
				 a.lastname AS alastname,
				 es.emc_operators_code_eo AS emc_operators_code_eo,
				 es.is_parcel_point_es,
				 o.total_products_wt AS totalOrder,
				 c.id_carrier AS carrierId,
				 CONCAT_WS("_", es.emc_operators_code_eo, es.code_es) AS offerCode
				 FROM '._DB_PREFIX_.'orders o
				 JOIN '._DB_PREFIX_.'carrier c
				 ON c.id_carrier = o.id_carrier
				 JOIN '._DB_PREFIX_.'address a
				 ON a.id_address = o.id_address_delivery
				 LEFT JOIN '._DB_PREFIX_.'customer cu
				 ON cu.id_customer = a.id_customer
				 JOIN '._DB_PREFIX_.'country co
				 ON co.id_country = a.id_country
				 LEFT JOIN '._DB_PREFIX_.'order_detail od
				 ON od.id_order = o.id_order
				 LEFT JOIN '._DB_PREFIX_.'emc_services es
				 ON es.id_es = c.emc_services_id_es
				 LEFT JOIN '._DB_PREFIX_.'emc_operators eo
				 ON es.emc_operators_code_eo = eo.code_eo
				 LEFT JOIN '._DB_PREFIX_.'emc_points ep
				 ON ep.'._DB_PREFIX_.'orders_id_order = o.id_order
				 LEFT JOIN '._DB_PREFIX_.'emc_api_pricing ap
				 ON ap.'._DB_PREFIX_.'cart_id_cart = o.id_cart
				 WHERE o.id_order = '.$order_id.' GROUP BY od.id_order_detail';
		$row = $this->db->ExecuteS($query);

		if (isset($row[0]) && (int)$row[0]['carrierId'] == 0)
		{
			$sql = 'SELECT *, ap.point_eap,
					 a.firstname AS afirstname,
					 a.lastname AS alastname,
					 es.emc_operators_code_eo AS emc_operators_code_eo,
					 es.is_parcel_point_es, o.total_products_wt AS totalOrder,
					 c.id_carrier AS carrierId,
					 CONCAT_WS("_", es.emc_operators_code_eo, es.code_es) AS offerCode
					 FROM '._DB_PREFIX_.'orders o
					 JOIN '._DB_PREFIX_.'order_carrier oc
					 ON oc.id_order = o.id_order
					 JOIN '._DB_PREFIX_.'carrier c
					 ON oc.id_carrier = c.id_carrier
					 JOIN '._DB_PREFIX_.'address a
					 ON a.id_address = o.id_address_delivery
					 LEFT JOIN '._DB_PREFIX_.'customer cu
					 ON cu.id_customer = a.id_customer
					 JOIN '._DB_PREFIX_.'country co
					 ON co.id_country = a.id_country
					 LEFT JOIN '._DB_PREFIX_.'order_detail od
					 ON od.id_order = o.id_order
					 LEFT JOIN '._DB_PREFIX_.'emc_services es
					 ON es.id_es = c.emc_services_id_es
					 LEFT JOIN '._DB_PREFIX_.'emc_operators eo
					 ON es.emc_operators_code_eo = eo.code_eo
					 LEFT JOIN '._DB_PREFIX_.'emc_points ep
					 ON ep.'._DB_PREFIX_.'orders_id_order = o.id_order
					 LEFT JOIN '._DB_PREFIX_.'emc_api_pricing ap
					 ON ap.'._DB_PREFIX_.'cart_id_cart = o.id_cart
					 WHERE o.id_order = '.$order_id.' GROUP BY od.id_order_detail';
			$rows = $this->db->ExecuteS($sql);
			if (count($rows) > 0)
				$row = $rows;
		}

		if (isset($row) === false || count($row) == 0)
			return array();

		$row[0]['id_carrier'] = (int)isset($row[0]['carrierId'])?$row[0]['carrierId'] : 0;
		//POST weight value
		$products_desc = array();
		if (isset($_POST['weight']))
			$product_weight = (float)str_replace(',', '.', $_POST['weight']);
		else
		{
			$product_weight = 0;
			$avg_weight = (float)Configuration::get('EMC_AVERAGE_WEIGHT');
			foreach ($row as $line)
			{
				$line['product_weight'] = EnvoimoinscherHelper::normalizeToKg(Configuration::get('PS_WEIGHT_UNIT'), $line['product_weight']);
				$product_weight += ((float)$line['product_weight'] * (float)$line['product_quantity']);
				//if we haven't product weight, take average weight option
				if (((float)$line['product_weight'] * (float)$line['product_quantity']) == 0)
					$product_weight += $avg_weight * (float)$line['product_quantity'];
				$products_desc[] = $line['product_name'];
			}
			//option < 100g
			if ($product_weight < 0.1 && (int)$config['EMC_WEIGHTMIN'] == 1)
				$product_weight = 0.1;
		}
		//delivery
		$addresses = $row[0]['address1'];
		if ($row[0]['address2'] != '')
			$addresses .= '|'.$row[0]['address2'];
		$del_type = 'particulier';
		if ($row[0]['company'] != '' && (int)Configuration::get('EMC_INDI') != 1)
			$del_type = 'entreprise';
		$delivery = array(
			'pays' => $row[0]['iso_code'],
			'adresse' => $addresses,
			'code_postal' => $row[0]['postcode'],
			'ville' => $row[0]['city'],
			'nom' => $row[0]['alastname'],
			'prenom' => $row[0]['afirstname'],
			'societe' => $row[0]['company'],
			'tel' => $row[0]['phone'],
			'email' => $row[0]['email'],
			'other' => $row[0]['other'],
			'type' => $del_type
		);
		$delivery['phoneAlert'] = false;
		if ($delivery['tel'] == '')
		{
			$delivery['tel'] = $row[0]['phone_mobile'];
			if ($delivery['tel'] == '')
			{
				//if phone number isn't indicated, put it the shipper's number
				$delivery['tel'] = $config['EMC_TEL'];
				$delivery['phoneAlert'] = true;
			}
		}

		$parcels_weight = array();
		$parcels_height = array();
		$parcels_width = array();
		$parcels_length = array();
		$j = 1;

		if (isset($_POST['multiParcel']) && (int)$_POST['multiParcel'] > 1)
		{
			$nb = (int)$_POST['multiParcel'];
			$parcels_w = array();
			if (isset($_POST['parcels_w']) && $_POST['parcels_w'] != '')
				$parcels_w = explode(';', $_POST['parcels_w']);
			else
				for ($i = 0; $i < $nb; $i++)
					$parcels_w[$i] = $_POST['parcel_w_'.($i + 1)];

			if (isset($_POST['parcels_h']) && $_POST['parcels_h'] != '')
				$parcels_h = explode(';', $_POST['parcels_h']);
			else
				for ($i = 0; $i < $nb; $i++)
					$parcels_h[$i] = $_POST['parcel_h_'.($i + 1)];

			if (isset($_POST['parcels_d']) && $_POST['parcels_d'] != '')
				$parcels_d = explode(';', $_POST['parcels_d']);
			else
				for ($i = 0; $i < $nb; $i++)
					$parcels_d[$i] = $_POST['parcel_d_'.($i + 1)];

			if (isset($_POST['parcels_l']) && $_POST['parcels_l'] != '')
				$parcels_l = explode(';', $_POST['parcels_l']);
			else
				for ($i = 0; $i < $nb; $i++)
					$parcels_l[$i] = $_POST['parcel_l_'.($i + 1)];

			foreach ($parcels_w as $parcel_weight)
			{
				$parcels_weight[$j] = (float)$parcel_weight;
				$j++;
			}

			$j = 1;
			foreach ($parcels_h as $parcel_height)
			{
				$parcels_height[$j] = (float)$parcel_height;
				$j++;
			}

			$j = 1;
			foreach ($parcels_d as $parcel_width)
			{
				$parcels_width[$j] = (float)$parcel_width;
				$j++;
			}

			$j = 1;
			foreach ($parcels_l as $parcel_length)
			{
				$parcels_length[$j] = (float)$parcel_length;
				$j++;
			}
		}
		else
		{
			$parcels_weight[1] = $product_weight;
			if (isset($_POST['length']))
				$parcels_length[1] = $_POST['length'];
			if (isset($_POST['width']))
				$parcels_width[1] = $_POST['width'];
			if (isset($_POST['height']))
				$parcels_height[1] = $_POST['height'];
		}

		$final_parcels = array();

		foreach ($parcels_weight as $k => $one_parcel_weight)
		{
			$dimensions = array();
			//get dimensions by weight
			if (isset($parcels_height[$k]) && $parcels_height[$k] != '' &&
					isset($parcels_width[$k]) && $parcels_width[$k] != '' &&
					isset($parcels_length[$k]) && $parcels_length[$k] != '')
			{
					$dimensions['length_ed'] = $parcels_length[$k];
					$dimensions['width_ed'] = $parcels_width[$k];
					$dimensions['height_ed'] = $parcels_height[$k];
			}
			else
				$dimensions = $this->db->getRow('SELECT * FROM '._DB_PREFIX_.'emc_dimensions
					WHERE weight_from_ed < '.$one_parcel_weight.' AND weight_ed >= '.$one_parcel_weight.'');
			if ($dimensions)
			{
				$final_parcels[$k] = array(
					'poids'    => $one_parcel_weight,
					'longueur' => (float)$dimensions['length_ed'],
					'largeur'  => (float)$dimensions['width_ed'],
					'hauteur'  => (float)$dimensions['height_ed']
				);
			}
		}
		// prepare pro forma informations
		$proforma = $this->makeProforma($row);
		// put default informa

		if (isset($config['EMC_PP_'.strtoupper($row[0]['offerCode'])]))
			$default_point = $config['EMC_PP_'.strtoupper($row[0]['offerCode'])];
		else
			$default_point = null;

		$insurance = false;
		if (isset($_POST['insurance'])) $insurance = (bool)(int)$_POST['insurance'];
		elseif (!isset($_POST['insurance']) && isset($_POST) && count($_POST) > 0) $insurance = false;
		elseif (isset($config['EMC_ASSU']) && (int)$config['EMC_ASSU'] == 1) $insurance = true;

		$defaults = array(
			'disponibilite.HDE' => $config['EMC_DISPO_HDE'],
			'disponibilite.HLE' => $config['EMC_DISPO_HLE'],
			'depot.pointrelais' => $default_point,
			'retrait.pointrelais' => $row[0]['point_ep'],
			'type_emballage.emballage' => Configuration::get('EMC_WRAPPING'),
			$config['EMC_TYPE'].'.description' => implode(',', $products_desc),
			$config['EMC_TYPE'].'.valeur' => $row[0]['totalOrder'],
			'assurance.selection' => $insurance,
			'assurance.emballage' => 'Caisse',
			'assurance.materiau' => 'Carton',
			'assurance.protection' => 'Carton antichoc',
			'assurance.fermeture' => 'Clous'
			);
		//if parcel point wasn't saved
		if (trim($defaults['retrait.pointrelais']) == '')
		{
			$point = explode('-', $row[0]['point_eap']);
			if (strpos(trim($point[0]), $row[0]['emc_operators_code_eo']) !== false)
			{
				$defaults['retrait.pointrelais'] = trim($point[1]);
				$data = array(
					_DB_PREFIX_.'orders_id_order' => $order_id,
					'point_ep' => trim($point[1]),
					'emc_operators_code_eo' => trim($point[0])
					);
				$this->db->autoExecute(_DB_PREFIX_.'emc_points', $data, 'INSERT');
			}
		}
		//If option 'use content as parcel description' is checked
		if ((int)$config['EMC_CONTENT_AS_DESC'] == 1)
		{
			$category_row = self::getNameCategory((int)$config['EMC_NATURE']);
			if ($category_row)
				$defaults['colis.description'] = $category_row;
		}
		$defaults['raison'] = 'sale';
		return array(
			'order'         => $row,
			'productWeight' => $product_weight,
			'default'       => $defaults,
			'dimensions'    => $dimensions,
			'parcels'       => $final_parcels,
			'config'        => $config,
			'delivery'      => $delivery,
			'proforma'      => $proforma,
			'code_eo'       => $row[0]['emc_operators_code_eo'],
			'is_pp'         => $row[0]['is_parcel_point_es'],
			'isEMCCarrier'  => (bool)$row[0]['external_module_name'] == $this->module_name
		);
	}

	/**
	* Prepares pro forma array.
	* @param Mage_Sales_Model_Order_Item $items Array of orders' items.
	* @return array Proformas array.
	*/
	public function makeProforma($items)
	{
		$s = 1;
		$proforma = array();
		foreach ($items as $item)
		{
			$proforma[$s] = array('description_en' => $item['product_name'],
				'description_fr' => $item['product_name'],  'nombre' => $item['product_quantity'],
				'valeur' => $item['product_price'],
				'origine' => 'FR', 'poids' => $item['product_weight']);
			$s++;
		}
		return $proforma;
	}

	/**
	* Updates configured dimensions.
	* @access public
	* @param array $data New dimensions data.
	* @param int $id Id of dimensions to update.
	* @return void
	*/
	public function updateDimensions($data, $id)
	{
		$this->db->autoExecute(_DB_PREFIX_.'emc_dimensions', $data, 'UPDATE', 'id_ed = '.$id);
	}

	/**
	* Insert new service.
	* @access public
	* @param array $service Service data.
	* @param array $carrier Carrier data.
	* @return bool True if inserted correctly, false otherwise
	*/
	public function insertService($service, $carrier)
	{
		//check if carrier is installed on emc_operators
		$db_carrier = $this->getCarrierByCode($carrier['code']);
		//if not exists, install it and get the last inserted id
		if (!isset($db_carrier[0]['code_eo']))
			$this->insertCarrier($carrier);
		//finally, install service
		$data = array(
			'id_carrier' => 0,
			'code_es' => $service['code'],
			'emc_operators_code_eo' => $carrier['code'],
			'label_es' => $service['label'],
			'desc_es' => ($service['srvInfos']['label_backoffice']),
			'desc_store_es' => ($service['srvInfos']['label_store']),
			'label_store_es' => $service['label'],
			'price_type_es' => 0,
			'is_parcel_point_es' => (int)$service['delivery'] == 'DROPOFF_POINT',
			'family_es' => $service['srvInfos']['offer_family']
			);
		return $this->db->autoExecute(_DB_PREFIX_.'emc_services', $data, 'INSERT');
	}

	/**
	* Update new service.
	* @access public
	* @param array $data Updated data.
	* @param string $carrier_code Carrier code.
	* @param string $service_code Service code.
	* @return bool True if updated correctly, false otherwise
	*/
	public function updateService($data, $carrier_code, $service_code)
	{
		if (!ctype_alnum($carrier_code) || !ctype_alnum($service_code)) return false;
		return $this->db->autoExecute(
			_DB_PREFIX_.'emc_services',
			$data,
			'UPDATE',
			'code_es = "'.trim($service_code).'" AND emc_operators_code_eo = "'.trim($carrier_code).'"');
	}

	/**
	* Insert new carrier.
	* @access public
	* @param array $carrier Carrier data.
	* @return bool True if inserted correctly, false otherwise
	*/
	public function insertCarrier($carrier)
	{
		$data = array('name_eo' => $carrier['label'], 'code_eo' => $carrier['code']);
		$this->db->autoExecute(_DB_PREFIX_.'emc_operators', $data, 'INSERT');
		return $this->db->Insert_ID();
	}

	/**
	* Delete service.
	* @access public
	* @param string $code Service code.
	* @return bool True if deleted correctly, false otherwise
	*/
	public function uninstallService($code)
	{
		$parts = explode('_', $code);
		$car_class = new CarrierCore;
		$service = $this->getServiceByCode($parts[1], $parts[0]);
		$data = array('active' => 0, 'deleted' => $car_class->deleted);
		$this->db->autoExecute(_DB_PREFIX_.'carrier', $data, 'UPDATE', 'emc_services_id_es = '.(int)$service[0]['id_es'].'');
		$r = $this->db->Execute('DELETE FROM '._DB_PREFIX_.'emc_services WHERE id_es = '.(int)$service[0]['id_es']);
		//if no more service attached to this operator, delete it too
		$r2 = true;
		if (!$this->hasServices($parts[0]))
			$r2 = $this->db->Execute('DELETE FROM '._DB_PREFIX_.'emc_operators WHERE code_eo = "'.$parts[0].'"');
		return $r && $r2;
	}

	/**
	* Checks if service exists in the database.
	* @access public
	* @param string $code Service code.
	* @return bool True if exists, false otherwise
	*/
	public function hasServices($code)
	{
		if (!ctype_alnum($code)) return;
		$c = $this->db->ExecuteS('SELECT COUNT(id_es) AS offers FROM '._DB_PREFIX_.'emc_services
			WHERE emc_operators_code_eo = "'.$code.'"');
		return $c[0]['offers'] > 0;
	}

	/**
	* Gets carrier by code.
	* @access public
	* @param string $code Carrier code.
	* @return array Carrier data
	*/
	public function getCarrierByCode($code)
	{
		if (strlen($code) != 4) return array();
		return $this->db->ExecuteS('SELECT * FROM '._DB_PREFIX_.'emc_operators
			WHERE code_eo = "'.$code.'"');
	}

	/**
	* Gets service by code.
	* @access public
	* @param string $code Service code.
	* @return array Service data
	*/
	public function getServiceByCode($ser_code, $ope_code)
	{
		if (!ctype_alnum($ope_code) || !ctype_alnum($ser_code)) return array();
		return $this->db->ExecuteS('SELECT * FROM '._DB_PREFIX_.'emc_services
			WHERE code_es = "'.$ser_code.'" AND emc_operators_code_eo = "'.$ope_code.'"');
	}

	/**
	* Inserts shippment order informations into the database.
	* @access public
	* @param int $order_id Id of order.
	* @param array $data List of order data (Prestashop order).
	* @param array $emcData List of order data (EnvoiMoinsCher order).
	* @param array $post List of post data.
	* @return void
	*/
	public function insertOrder($order_id, $data, $emc_order, $post)
	{
		global $cookie;
		//insert into emc_orders
		$date_collect_eor = $emc_order['collection']['date'].' '.(isset($emc_order['collection']['time'])?$emc_order['collection']['time']:'');
		$date_del_eor = $emc_order['delivery']['date'].' '.(isset($emc_order['delivery']['time'])?$emc_order['delivery']['time']:'');
		$order_data = array(
			''._DB_PREFIX_.'orders_id_order' => $order_id,
			'emc_operators_code_eo'          => $emc_order['offer']['operator']['code'],
			'price_ht_eor'                   => (float)$emc_order['price']['tax-exclusive'],
			'price_ttc_eor'                  => (float)$emc_order['price']['tax-inclusive'],
			'ref_emc_eor'                    => $emc_order['ref'],
			'service_eor'                    => $emc_order['service']['label'],
			'date_order_eor'                 => date('Y-m-d H:i:s'),
			'date_collect_eor'               => $date_collect_eor,
			'date_del_eor'                   => $date_del_eor,
			'tracking_eor'                   => $data['trackingKey'],
			'parcels_eor'                    => count($data['parcels'])
			);
		$this->db->autoExecute(_DB_PREFIX_.'emc_orders', $order_data, 'REPLACE');
		//insert parcels
		foreach ($data['parcels'] as $p => $parcel)
		{
			$parcel_data = array(
				''._DB_PREFIX_.'orders_id_order' => $order_id,
				'number_eop'                     => $p,
				'weight_eop'                     => $parcel['poids'],
				'length_eop'                     => $parcel['longueur'],
				'width_eop'                      => $parcel['largeur'],
				'height_eop'                     => $parcel['hauteur']
				);
			$this->db->autoExecute(_DB_PREFIX_.'emc_orders_parcels', $parcel_data, 'REPLACE');
		}
		//insert new shipping documents into emc_documents
		foreach ($emc_order['labels'] as $label) 	//labels
		{
			$this->db->autoExecute(
				_DB_PREFIX_.'emc_documents',
				array(
					''._DB_PREFIX_.'orders_id_order' => $order_id,
					'link_ed'                        => $label,
					'type_ed'                        => 'label',
					'generated_ed'                   => 0
				),
				'REPLACE'
			);
		}
		if (isset($emc_order['proforma']) && $emc_order['proforma'] != '')
		{
			$this->db->autoExecute(
				_DB_PREFIX_.'emc_documents',
				array(
					''._DB_PREFIX_.'orders_id_order' => $order_id,
					'link_ed'                        => $emc_order['proforma'],
					'type_ed'                        => 'proforma',
					'generated_ed'                   => 1
				),
				'REPLACE');
		}
		//update order (shipping_number with EnvoiMoinsCher referency)
		$this->db->autoExecute(
			_DB_PREFIX_.'orders',
			array(
				'shipping_number' => $emc_order['ref']
			),
			'UPDATE',
			'id_order = '.$order_id.''
		);
		//insert the new order state into order_history
		$history = new OrderHistory();
		$history->id_order = $order_id;
		$history->changeIdOrderState($data['new_order_state'], $order_id);
		$history->id_employee = (int)$cookie->id_employee;
		$history->addWithemail();
		//update parcel point info (event when it wasn't modified)
		if (isset($post['retrait_pointrelais']) && $post['retrait_pointrelais'] != '')
		{
			$this->db->autoExecute(
				_DB_PREFIX_.'emc_points',
				array(
					'point_ep' => $post['retrait_pointrelais']
				),
				'UPDATE',
				_DB_PREFIX_.'orders_id_order = '.$order_id
			);
		}
		$this->db->execute('DELETE FROM '._DB_PREFIX_.'emc_orders_errors WHERE '._DB_PREFIX_.'orders_id_order = '.(int)$order_id.'');
	}

	/**
	* Inserts order error message.
	* @access public
	* @param int $order_id Order id.
	* @param string $message Error message.
	* @return void
	*/
	public function insertOrderError($order_id, $message)
	{
		$this->db->Execute('DELETE FROM '._DB_PREFIX_.'emc_orders_errors WHERE '._DB_PREFIX_.'orders_id_order = '.(int)$order_id.'');
		$error_data = array(_DB_PREFIX_.'orders_id_order' => $order_id, 'errors_eoe' => ($message));
		$this->db->autoExecute(_DB_PREFIX_.'emc_orders_errors', $error_data, 'INSERT');
	}

	/**
	* Constructs categories tree.
	* @access public
	* @param array $config Config array used to web service connection.
	* @return array List with categories
	*/
	public function getCategoriesTree($config)
	{
		$rows = $this->db->ExecuteS('SELECT * FROM '._DB_PREFIX_.'emc_categories
			WHERE emc_categories_id_eca > 0
			ORDER BY name_eca ASC');
		$categories = array();
		$all_categories = array();
		foreach ($rows as $row)
		{
			$categories[$row['id_eca']] = array('id' => $row['id_eca'], 'name' => $row['name_eca']);
			$all_categories[$row['id_eca']] = $row['id_eca'];
		}
		//check new categories
		//$codes = array();
		if (isset($config['EMC_KEY']))
		{
			require_once(__DIR__.'/Env/WebService.php');
			require_once(__DIR__.'/Env/ContentCategory.php');
			$content_cl = new EnvContentCategory(array('user' => $config['EMC_LOGIN'], 'pass' => $config['EMC_PASS'], 'key' => $config['EMC_KEY']));
			$emc = new Envoimoinscher();
			$content_cl->setPlatformParams($emc->ws_name, _PS_VERSION_, $emc->version);
			$content_cl->setParam(array('module' => $config['wsName'], 'version' => $config['localVersion']));
			$content_cl->setGetParams();
			$content_cl->getCategories();
			$content_cl->getContents();
			foreach ($content_cl->categories as $category)
			{
				if (isset($content_cl->contents[$category['code']]))
				{
					foreach ($content_cl->contents[$category['code']] as $child)
					{
						if (!isset($all_categories[$child['code']]))
						{
							//add new
							$data = array('id_eca' => $child['code'], 'emc_categories_id_eca' => $category['code'], 'name_eca' => ($child['label']));
							$this->db->autoExecute(_DB_PREFIX_.'emc_categories', $data, 'INSERT');
							$categories[] = array('id' => $data['id_eca'], 'name' => $data['name_eca']);
						}
						else
							unset($all_categories[$child['code']]);
					}
				}
			}
		}
		return $categories;
	}

	/**
	* Gets tracking informations.
	* @access public
	* @param int $order Order id.
	* @return array Tracking data
	*/
	public function getTrackingInfos($order)
	{
		return $this->db->ExecuteS('SELECT *, DATE_FORMAT(date_et, \'%d-%m-%Y\') AS date
			FROM '._DB_PREFIX_.'emc_tracking
			WHERE '._DB_PREFIX_.'orders_id_order = '.$order.' ORDER BY id_et DESC');
	}

	/**
	* Get tracking informations by order and customer ids.
	* @access public
	* @param int $order Order id.
	* @param int $customer Customer id.
	* @return array Tracking data
	*/
	public function getTrackingByOrderAndCustomer($order, $customer)
	{
		return $this->db->ExecuteS('SELECT *, DATE_FORMAT(et.date_et, \'%d-%m-%Y\') AS date
			FROM '._DB_PREFIX_.'emc_tracking et
			JOIN '._DB_PREFIX_.'orders o ON o.id_order = et.'._DB_PREFIX_.'orders_id_order
			WHERE et.'._DB_PREFIX_.'orders_id_order = '.(int)$order.' AND o.id_customer = '.(int)$customer.' ORDER BY id_et DESC');
	}

	/**
	* Get tracking informations by order and customer ids.
	* @access public
	* @param int $order Order id.
	* @param int $customer Customer id.
	* @return array Tracking data
	*/
	public function getParcelsInfos($order)
	{
		return $this->db->ExecuteS('SELECT * FROM '._DB_PREFIX_.'emc_orders_parcels
			 WHERE '._DB_PREFIX_.'orders_id_order = '.(int)$order.' ORDER BY number_eop ASC');
	}

	/**
	* Gets order data to tracking.
	* @access public
	* @param int $order Order id.
	* @return array Tracking data
	*/
	public function getOrderData($order)
	{
		return $this->db->ExecuteS('SELECT a.*, co.iso_code, o.id_order, es.is_parcel_point_es,
			 ep.point_ep, es.emc_operators_code_eo FROM '._DB_PREFIX_.'orders o
			 JOIN '._DB_PREFIX_.'address a ON o.id_address_delivery = a.id_address
			 JOIN '._DB_PREFIX_.'country co ON co.id_country = a.id_country
			 JOIN '._DB_PREFIX_.'carrier c ON c.id_carrier = o.id_carrier
			 LEFT JOIN '._DB_PREFIX_.'emc_services es ON c.emc_services_id_es = es.id_es
			 LEFT JOIN '._DB_PREFIX_.'emc_points ep ON ep.'._DB_PREFIX_.'orders_id_order = o.id_order
			 WHERE o.id_order = '.(int)$order.'');
	}

	/**
	* Gets carrier with API pricing.
	* @access public
	* @param int $id_cart Cart id.
	* @param int $id_carrier Carrier id.
	* @return array Pricing data.
	*/
	public function getCarrierWithPricing($id_cart, $id_carrier)
	{
		return $this->db->ExecuteS('SELECT eap.prices_eap ,
			 eap.point_eap, c.id_carrier,
			 es.emc_operators_code_eo,
			 es.is_parcel_point_es
			 FROM '._DB_PREFIX_.'carrier c
			 JOIN '._DB_PREFIX_.'emc_services es ON es.id_es = c.emc_services_id_es
			 JOIN '._DB_PREFIX_.'emc_api_pricing eap ON eap.'._DB_PREFIX_.'cart_id_cart = '.(int)$id_cart.'
			 WHERE c.id_carrier = '.(int)$id_carrier.' ');
	}

	/**
	* Gets carrier with API pricing.
	* @access public
	* @param int $cart Cart id.
	* @return array Pricing data.
	*/
	public function getCarrierByCartPricing($cart)
	{
		return $this->db->ExecuteS('SELECT eap.*, c.id_carrier, c.external_module_name, es.emc_operators_code_eo
			 FROM '._DB_PREFIX_.'emc_api_pricing eap
			 JOIN '._DB_PREFIX_.'cart ca ON ca.id_cart = eap.'._DB_PREFIX_.'cart_id_cart
			 JOIN '._DB_PREFIX_.'carrier c ON c.id_carrier = ca.id_carrier
			 LEFT JOIN '._DB_PREFIX_.'emc_services es ON es.id_es = c.emc_services_id_es
			 WHERE eap.'._DB_PREFIX_.'cart_id_cart = '.(int)$cart.' ');
	}

	/**
	* Gets EnvoiMoinsCher's carrier by zone and language.
	* @access public
	* @param int $zone Zone id.
	* @param int $lang Lang id.
	* @return array Carrier data.
	*/
	public function getEmcCarriersByZone($zone, $lang)
	{
		return $this->db->ExecuteS('SELECT * FROM '._DB_PREFIX_.'carrier c
			 LEFT JOIN '._DB_PREFIX_.'emc_services es ON c.emc_services_id_es = es.id_es
			 JOIN '._DB_PREFIX_.'carrier_zone cz ON cz.id_carrier = c.id_carrier
			 JOIN '._DB_PREFIX_.'carrier_lang cl ON cl.id_carrier = c.id_carrier
			 WHERE c.external_module_name = "envoimoinscher"
			 AND cz.id_zone = '.(int)$zone.' AND c.shipping_external = 1
			 AND c.deleted = 0
			 AND c.active = 1
			 AND cl.id_lang = '.(int)$lang.' ');
	}
	/**
	* Gets EnvoiMoinsCher's carrier by language.
	* @access public
	* @param int $lang Lang id.
	* @return array Carrier data.
	*/
	public function getEmcCarriersWithoutZone($lang)
	{
		return $this->db->ExecuteS('SELECT * FROM '._DB_PREFIX_.'carrier c
			 LEFT JOIN '._DB_PREFIX_.'emc_services es ON c.emc_services_id_es = es.id_es
			 JOIN '._DB_PREFIX_.'carrier_lang cl ON cl.id_carrier = c.id_carrier
			 WHERE c.external_module_name = "envoimoinscher"
			 AND c.shipping_external = 1
			 AND c.deleted = 0
			 AND c.active = 1
			 AND cl.id_lang = '.(int)$lang.' ');
	}

	/**
	 * Return the attributes of a product
	 * @access public
	 * @param int $id_attribute : attribute id.
	 * @return array Attribute data.
	 */
	public function getProductAttributes($id_attribute)
	{
		return $this->db->ExecuteS('SELECT * FROM '._DB_PREFIX_.'product_attribute where id_product_attribute = '.$id_attribute);
	}

	/**
	* Gets cart informations.
	* @access public
	* @param int $cart Cart id.
	* @param array $address Address informations.
	* @return array Cart data.
	*/
	public function getCartInformations($cart, $address)
	{
		// find address id
		if (isset($address->id))
			$id_address = $address->id;
		else if (is_array($address))
			return getCartInformations($cart, $address[0]);
		else
			$id_address = $address;

		if ($id_address > 0)
		{
			$address_clause = 'a.id_address = '.(int)$id_address;
			return $this->db->ExecuteS('SELECT *, cp.quantity AS productQuantity FROM '._DB_PREFIX_.'cart ct
				 JOIN '._DB_PREFIX_.'cart_product cp ON cp.id_cart = ct.id_cart
				 JOIN '._DB_PREFIX_.'address a ON '.$address_clause.'
				 JOIN '._DB_PREFIX_.'product p ON cp.id_product = p.id_product
				 JOIN '._DB_PREFIX_.'country co ON co.id_country = a.id_country
				 WHERE ct.id_cart = '.(int)$cart);
		}
		else
		{
			return $this->db->ExecuteS('SELECT *, cp.quantity AS productQuantity FROM '._DB_PREFIX_.'cart_product cp
				 JOIN '._DB_PREFIX_.'product p ON cp.id_product = p.id_product
				 WHERE cp.id_cart = '.(int)$cart);
		}
	}

	/**
	* Gets order references to download.
	* @access public
	* @param array $orders Orders of labels to download.
	* @return array Order references.
	*/
	public function getReferencesToLabels($orders)
	{
		return $this->db->ExecuteS('SELECT * FROM '._DB_PREFIX_.'emc_orders eo
			 JOIN '._DB_PREFIX_.'emc_documents ed ON eo.'._DB_PREFIX_.'orders_id_order = ed.'._DB_PREFIX_.'orders_id_order
			 AND ed.type_ed = "label" WHERE eo.'._DB_PREFIX_.'orders_id_order IN ('.implode(', ', $orders).')
			 AND ed.generated_ed = 1 GROUP BY ed.'._DB_PREFIX_.'orders_id_order');
	}

	public function getPointInfos($order)
	{
		$order = $this->getOrderData($order);
		if (isset($order[0]['id_order']) && $order[0]['point_ep'] != '')
		{
		//get parcel point informations
			require_once(realpath(dirname(__FILE__).'/Env/WebService.php'));
			require_once(realpath(dirname(__FILE__).'/Env/ParcelPoint.php'));
			require_once(realpath(dirname(__FILE__).'/EnvoimoinscherHelper.php'));
			$helper = new EnvoimoinscherHelper;
			$config = $helper->configArray($this->getConfigData());
			$poi_cl = new EnvParcelPoint(array('user' => $config['EMC_LOGIN'], 'pass' =>
				$config['EMC_PASS'], 'key' => $config['EMC_KEY'])
			);
			$emc = new Envoimoinscher();
			$poi_cl->setPlatformParams($emc->ws_name, _PS_VERSION_, $emc->version);
			$poi_cl->setEnv(strtolower($config['EMC_ENV']));
			$poi_cl->getParcelPoint('dropoff_point', $order[0]['emc_operators_code_eo'].'-'.$order[0]['point_ep'], $order[0]['iso_code']);
			return $poi_cl->points['dropoff_point'];
		}
		return array();
	}

	/**
	* Gets last planning data.
	* @access public
	* @return array Planning data
	*/
	public function getLastPlanning()
	{
		$row = $this->db->getRow('SELECT * FROM '._DB_PREFIX_.'emc_orders_plannings
			 ORDER BY id_eopl DESC');
		return $row;
	}

	/**
	* Updates order planning data.
	* @access public
	* @param array $data New planning data.
	* @param int $id Planning id.
	* @return void
	*/
	public function updateOrdersList($data, $id)
	{
		$sql_data = array(
			'orders_eopl' => serialize($data['orders']),
			'stats_eopl' => serialize($data['stats']),
			'errors_eopl' => (serialize($data['errors']))
			);
		$this->db->autoExecute(_DB_PREFIX_.'emc_orders_plannings', $sql_data, 'UPDATE', 'id_eopl = '.(int)$id);
	}

	/**
	* Makes new order planning data.
	* @access public
	* @param array $data New planning data.
	* @param int $type Planning type (0 => separate order, 1 => from EMC orders table, 2 => from no EMC orders table, 3 => from errors table)
	* @return void
	*/
	public function makeNewPlanning($orders, $type)
	{
		$sql_data = array(
			'orders_eopl' => serialize(array('todo' => $orders, 'done' => array())),
			'stats_eopl'  => serialize(array('total' => count($orders), 'ok' => 0, 'skipped' => 0, 'errors' => 0)),
			'errors_eopl' => serialize(array()),
			'date_eopl'   => date('Y-m-d H:i:s'),
			'type_eopl'   => $type
		);
		$this->db->autoExecute(_DB_PREFIX_.'emc_orders_plannings', $sql_data, 'INSERT');
	}

	/**
	* Removes all plannings.
	* @access public
	* @return void
	*/
	public function removePlanning()
	{
		$this->db->Execute('DELETE FROM '._DB_PREFIX_.'emc_orders_plannings');
	}


	public function addPostData($order, $post_data)
	{
		$data = array(
			_DB_PREFIX_.'orders_id_order' => $order,
			'data_eopo'                   => pSQL(serialize($post_data)),
			'date_eopo'                   => date('Y-m-d H:i:s')
		);
		$this->db->autoExecute(_DB_PREFIX_.'emc_orders_post', $data, 'REPLACE');
	}

	public function getPostData($order)
	{
		$row = $this->db->ExecuteS('SELECT * FROM '._DB_PREFIX_.'emc_orders_post WHERE '._DB_PREFIX_.'orders_id_order = '.(int)$order.'');
		if (isset($row[0]['data_eopo'])) return unserialize($row[0]['data_eopo']);
		return array(
			'delivery'     => array(),
			'quote'        => array(),
			'parcels'      => array(),
			'proforma'     => array(),
			'emcErrorTxt'  => '',
			'emcErrorSend' => ''
		);
	}

	public function removeTemporaryPost($order)
	{
		$this->db->Execute('DELETE FROM '._DB_PREFIX_.'emc_orders_post WHERE '._DB_PREFIX_.'orders_id_order = '.(int)$order.'');
	}

	/**
	* Gets EnvoiMoinsCher's carriers.
	* @access public
	* @return array Carriers list.
	*/
	public function getEmcCarriers()
	{
		return $this->db->ExecuteS('SELECT * FROM '._DB_PREFIX_.'carrier
			 WHERE external_module_name = "envoimoinscher" AND active = 1');
	}

	/**
	* Updates order delivery address.
	* @access public
	* @param int $order Order id.
	* @param array $data Data of new address.
	* @param array $old Data of old address.
	* @return void
	*/
	public function putNewAddress($order_id, $data, $old)
	{
		if ($old['alias'] != $data['alias'])
		{
			Db::getInstance()->autoExecute(_DB_PREFIX_.'address', $data, 'INSERT');
			$id = Db::getInstance()->Insert_ID();
			//update id_address_delivery
			Db::getInstance()->autoExecute(_DB_PREFIX_.'orders', array('id_address_delivery' => $id), 'UPDATE', 'id_order = '.$order_id);
		}
		else
			Db::getInstance()->autoExecute(_DB_PREFIX_.'address', $data, 'UPDATE', 'id_address = '.$old['id_address']);
	}

	public function getOffersOrder()
	{
		return $this->offers_order;
	}

	public function getOffersFamilies()
	{
		return array(
			self::FAM_ECONOMIQUE   			=> 'Offres économiques',
			self::FAM_EXPRESSISTE 			=> 'Offres expressiste',
		);
	}

	public function getTrackingModes()
	{
		return array(
			self::TRACK_EMC_TYPE => 'EnvoiMoinsCher',
			self::TRACK_OPE_TYPE => 'Transporteur'
		);
	}

	/**
	* Adds new carrier into carrier table.
	* @access public
	* @param array $data New carrier's informations.
	* @return int Carrier's id
	*/
	public function saveCarrier($data, $service)
	{
		$langs = Language::getLanguages(true); 	//Get all langs enabled
		$zones = Zone::getZones(true); 	//Gel all zones enabled

		//get 19.6% tax id
		if (!isset($data['id_tax_rules_group']))
		{
			$tax = $this->db->getRow('SELECT * FROM `'._DB_PREFIX_.'tax` WHERE `rate` = "19.6"');
			$data['id_tax_rules_group'] = (int)$tax['id_tax'];
		}
		//Add field to Carrier
		Carrier::$definition['fields']['emc_services_id_es'] = array('type' => ObjectModel::TYPE_INT, 'required' => true);
		Carrier::$definition['fields']['emc_type']           = array('type' => ObjectModel::TYPE_INT, 'required' => true);

		//Set Carrieru
		$carrier = new Carrier((int)$service['id_carrier']);

		$carrier->emc_services_id_es   = (int)$data['emc_services_id_es'];
		$carrier->id_reference         = (int)$service['id_carrier'];
		$carrier->name                 = $data['name'];
		$carrier->active               = (int)$data['active'];
		$carrier->is_module            = (int)$data['is_module'];
		$carrier->need_range           = (int)$data['need_range'];
		$carrier->range_behavior       = (int)$data['range_behavior'];
		$carrier->shipping_external    = (int)$data['shipping_external'];
		$carrier->emc_type             = $data['emc_type'];
		$carrier->external_module_name = $data['external_module_name'];
		$carrier->delay                = array();

		if ($langs && count($langs) > 0)
			foreach ($langs as $lang)
				$carrier->delay[$lang['id_lang']] = substr(pSQL($service['desc_store_es']), 0, 128);

		//Save carrier
		if ($carrier->save() === false)
			return false;

		//Get carrier id
		$carrier_id = (int)$carrier->id;

		DB::getInstance()->Execute('UPDATE '._DB_PREFIX_.'carrier
			 SET emc_services_id_es = '.$data['emc_services_id_es'].',emc_type = '.$data['emc_type'].'
			 WHERE id_carrier = '.$carrier_id);

		if ((int)$service['id_carrier'] === 0)
		{
			$groups = Group::getGroups((int)Context::getContext()->language->id);
			$datas = array();

			if ($groups && count($groups) > 0)
			{
				foreach ($groups as $group)
				{
					$datas[] = array(
						'id_carrier' => (int)$carrier_id,
						'id_group'   => (int)$group['id_group']
						);
				}
			}

			DB::getInstance()->autoExecute(_DB_PREFIX_.'carrier_group', $datas, 'REPLACE');
		}

		//Get Ranges price by carrier id
		$ranges_price = RangePrice::getRanges((int)$carrier_id);
		if (count($ranges_price) === 0)
			$ranges_price[] = array('id_range_price' => null);
		//RangePrice
		$range_price = new RangePrice((int)$ranges_price[0]['id_range_price']);
		$range_price->id_carrier = (int)$carrier_id;
		$range_price->delimiter1 = 0;
		$range_price->delimiter2 = 10000;
		$range_price->save();

		//Get Ranges price by carrier id
		$ranges_weight = RangeWeight::getRanges((int)$carrier_id);
		if (count($ranges_weight) === 0)
			$ranges_weight[] = array('id_range_weight' => null);

		//RangeWeight
		$range_weight = new RangeWeight((int)$ranges_weight[0]['id_range_weight']);
		$range_weight->id_carrier = (int)$carrier_id;
		$range_weight->delimiter1 = 0;
		$range_weight->delimiter2 = 10000;
		$range_weight->save();

		if ($zones && count($zones) > 0)
			foreach ($zones as $zone)
				if (count($carrier->getZone((int)$zone['id_zone'])) === 0)
					$carrier->addZone((int)$zone['id_zone']);

		copy(__DIR__.'/img/detail_'.strtolower($service['code_eo']).'.jpg', _PS_IMG_DIR_.'s/'.(int)$carrier_id.'.jpg');
		return $carrier_id;
	}

	/**
	* Makes plugin at 'online' mode.
	* @access public
	* @return void
	*/
	public function passToOnlineMode()
	{
		//update every range = 1 EMC carrier
		$this->db->autoExecute(
			_DB_PREFIX_.'carrier',
			array('need_range' => 1),
			'UPDATE',
			'external_module_name = "envoimoinscher" AND need_range = 1 AND emc_type = '.self::RATE_PRICE.'');
	}

	/**
	* Gets order carriers.
	* @access public
	* @param int $order Order id
	* @return array Array with keys the same as two tables with carrier informations :
	*               'orders' for orders table and 'order_carrier' for order_carriers table
	*/
	public function getOrderCarriers($order)
	{
		$orders = $this->db->executeS('SELECT o.id_carrier AS oCarrier,  o.total_shipping AS oShipping,
			 total_shipping_tax_incl AS oShippingInc, carrier_tax_rate AS oCarrierTax,
			 total_shipping_tax_excl AS oShippingExc, o.id_cart AS oCart,
			 c.id_carrier AS cCarrier, c.delivery_option AS cDelivery,
			 o.total_paid AS oTotal, o.total_paid_tax_incl AS oTotalIncl,
			 o.total_paid_tax_excl AS oTotalExc,
			 o.total_products AS oProducts, o.total_products_wt AS oProductsWt,
			 o.total_wrapping AS oWrapping, o.total_wrapping_tax_incl AS oWrappingInc,
			 o.total_wrapping_tax_excl AS oWrappingExc
			 FROM '._DB_PREFIX_.'orders o
			 JOIN '._DB_PREFIX_.'cart c ON c.id_cart = o.id_cart
			 WHERE o.id_order = '.(int)$order);
		$orders_carriers = $this->db->executeS('SELECT * FROM '._DB_PREFIX_.'order_carrier WHERE
			 id_order = '.(int)$order);
		return array('orders' => $orders[0], 'order_carriers' => $orders_carriers);
	}

	/**
	* Checks if $carrier belongs to EMC carriers.
	* @access public
	* @param int $carrier Carrier's id.
	* @return boolean True if $carrier belongs to EMC, false otherwise.
	*/
	public function isEmcCarrier($carrier)
	{
		$row = $this->db->getRow('SELECT external_module_name FROM '._DB_PREFIX_.'carrier WHERE id_carrier = '.(int)$carrier);
		return ($row['external_module_name'] == 'envoimoinscher');
	}

	/**
	* Gets commands by cart_id (specially used by multi-delivery option.
	* @access public
	* @param int $cart Cart id.
	* @return array Orders of $cart.
	*/
	public function getOrdersByCart($cart)
	{
		$orders = $this->db->executeS('SELECT o.id_order, o.id_address_delivery AS oAddress,
			 o.id_carrier AS oCarrier,  o.total_shipping AS oShipping,
			 total_shipping_tax_incl AS oShippingInc, carrier_tax_rate AS oCarrierTax,
			 total_shipping_tax_excl AS oShippingExc, o.id_cart AS oCart,
			 c.id_carrier AS cCarrier, c.delivery_option AS cDelivery,
			 o.total_paid AS oTotal, o.total_paid_tax_incl AS oTotalIncl,
			 o.total_paid_tax_excl AS oTotalExc,
			 o.total_products AS oProducts, o.total_products_wt AS oProductsWt,
			 o.total_wrapping AS oWrapping, o.total_wrapping_tax_incl AS oWrappingInc,
			 o.total_wrapping_tax_excl AS oWrappingExc
			 FROM '._DB_PREFIX_.'orders o
			 JOIN '._DB_PREFIX_.'cart c ON c.id_cart = o.id_cart
			 WHERE o.id_cart = '.(int)$cart);
		$orders_carriers = array();
		foreach ($orders as $order)
		{
			$orders_carriers[$order['id_order']] = $this->db->executeS('SELECT * FROM '._DB_PREFIX_.'order_carrier WHERE
				 id_order = '.(int)$order['id_order']);
		}
		//get cart rules too (by now only for free shipping)
		$rules_rows = $this->db->executeS('SELECT * FROM '._DB_PREFIX_.'order_cart_rule ocr
				 JOIN '._DB_PREFIX_.'orders ord ON ord.id_order = ocr.id_order
				 JOIN '._DB_PREFIX_.'cart_rule cr ON cr.id_cart_rule = ocr.id_cart_rule
				 WHERE ord.id_cart = '.(int)$cart);
		$rules = array();
		foreach ($rules_rows as $rule)
			$rules[$rule['id_order']] = $rule;
		return array('orders' => $orders, 'order_carriers' => $orders_carriers, 'rules' => $rules);
	}

	/**
	* Gets cart from database and prepares it to EMC's API call.
	* @access public
	* @param int $cart Cart id.
	* @return array Cart informations.
	*/
	public function prepareCart($cart)
	{
		$row = $this->db->getRow('SELECT * FROM '._DB_PREFIX_.'cart WHERE id_cart = '.(int)$cart);
		//TODO : gérer multi-livraisons avec $options
		//$options = unserialize($row['delivery_option']);
		return $row;
	}

	/**
	* Get shipping costs from order_carrier table by cart id.
	* @deprecated Is making in override/controller/front/OrderConfirmationController.php
	* @access public
	* @param int $id_cart Id cart.
	* @return array List with order_carrier informations.
	*/
	public function getCarriersCostByCart($id_cart)
	{
		$orders = $this->db->executeS('SELECT oc.*, car.*, api.*, o.id_address_delivery AS oAddress,
			 o.total_paid AS oTotal, o.total_paid_tax_incl AS oTotalIncl,
			 o.total_paid_tax_excl AS oTotalExc,
			 o.total_products AS oProducts, o.total_products_wt AS oProductsWt,
			 o.total_wrapping AS oWrapping, o.total_wrapping_tax_incl AS oWrappingInc,
			 o.total_shipping AS oShipping, o.total_shipping_tax_excl AS oShippingExc,
			 o.total_wrapping_tax_excl AS oWrappingExc, total_shipping_tax_incl AS oShippingInc
			 FROM '._DB_PREFIX_.'order_carrier oc
			 JOIN '._DB_PREFIX_.'orders o ON o.id_order = oc.id_order
			 JOIN '._DB_PREFIX_.'carrier car ON oc.id_carrier = car.id_carrier
			 JOIN '._DB_PREFIX_.'emc_api_pricing api ON '._DB_PREFIX_.'cart_id_cart = o.id_cart
			 WHERE o.id_cart = '.(int)$id_cart);
		if (count($orders) > 0) return $orders;
		$orders = $this->db->executeS('SELECT car.*, api.*, o.id_order, o.id_address_delivery AS oAddress,
			 o.total_paid AS oTotal, o.total_paid_tax_incl AS oTotalIncl,
			 o.total_paid_tax_excl AS oTotalExc,
			 o.total_products AS oProducts, o.total_products_wt AS oProductsWt,
			 o.total_wrapping AS oWrapping, o.total_wrapping_tax_incl AS oWrappingInc,
			 o.total_shipping AS oShipping, o.total_shipping_tax_excl AS oShippingExc,
			 o.total_wrapping_tax_excl AS oWrappingExc, total_shipping_tax_incl AS oShippingInc
			 FROM '._DB_PREFIX_.'orders o
			 JOIN '._DB_PREFIX_.'carrier car ON o.id_carrier = car.id_carrier
			 JOIN '._DB_PREFIX_.'emc_api_pricing api ON '._DB_PREFIX_.'cart_id_cart = o.id_cart
			 WHERE o.id_cart = '.(int)$id_cart);
		return $orders;
	}

	private function canBeFreeShipping($carrier_id)
	{
		$rows = $this->db->getRow('SELECT * FROM '._DB_PREFIX_.'configuration WHERE name = "EMC_NO_FREESHIP"');
		if (!isset($rows['value'])) $rows['value'] = array();
		else $rows['value'] = unserialize($rows['value']);
		$carrier = $this->db->getRow('SELECT * FROM '._DB_PREFIX_.'carrier c
			 JOIN '._DB_PREFIX_.'emc_services es ON c.emc_services_id_es = es.id_es
			 WHERE c.id_carrier = '.(int)$carrier_id);
		if (in_array($carrier['id_es'], $rows['value'])) return false;
		return true;
	}

	public function getLastPrices($cart_code)
	{
		return $this->db->getRow('SELECT * FROM '._DB_PREFIX_.'emc_api_pricing WHERE id_ap = "'.$cart_code.'" ORDER BY date_eap DESC');
	}

	public function cleanCache()
	{
		return $this->db->Execute('DELETE FROM '._DB_PREFIX_.'emc_api_pricing');
	}

	public static function getNameCategory($id_eca)
	{
		$query = 'SELECT `name_eca` FROM `'._DB_PREFIX_.'emc_categories` WHERE `id_eca` = "'.(int)$id_eca.'" ';

		return DB::getInstance()->getValue($query);
	}
}