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

$text_tracking = urldecode($_GET['text']);
$local_tracking = urldecode($_GET['localisation']);

include('../../config/config.inc.php');
include('../../init.php');
global $cookie;

/* init error data */
$ip_address = 'adresse inconnue ou incorrecte';
if (preg_match('/^([A-Za-z0-9.]+)$/', $_SERVER['REMOTE_ADDR']))
	$ip_address = $_SERVER['REMOTE_ADDR'];
$error_msg  = 'Une erreur pendant l\'insertion des informations du tracking pour la commande ';
$error_msg .= (int)$_GET['order'].'. La commande n\'a pas été retrouvée. L\'adresse IP de l\'appel : '.$_SERVER['REMOTE_ADDR'].'';
/* check order in the database */
$order_id = (int)$_GET['order'];
if (ctype_alnum($_GET['key']) && $order_id > 0)
{
	$order_info = Db::getInstance()->ExecuteS('SELECT * FROM '._DB_PREFIX_.'emc_orders eo
		 JOIN '._DB_PREFIX_.'orders o ON o.id_order = eo.'._DB_PREFIX_.'orders_id_order
		 WHERE eo.'._DB_PREFIX_.'orders_id_order = '.$order_id.' AND eo.tracking_eor = "'.$_GET['key'].'" ');
	if (count($order_info) > 0)
	{
		/* get last order state (prevent to repeat the same state) */
		$history_row = Db::getInstance()->ExecuteS('SELECT * FROM '._DB_PREFIX_.'order_history
			 WHERE id_order = '.$order_id.' ORDER BY id_order_history DESC LIMIT 1');
		$confs = Configuration::getMultiple(array('EMC_ANN', 'EMC_ENVO', 'EMC_CMD', 'EMC_LIV'));
		/* if EMC_LIV, do not accept other tracking infos */
		if ($history_row[0]['id_order_state'] == $confs['EMC_LIV'])
			die();
		$customer = new Customer((int)$order_info[0]['id_customer']);
		switch ($_GET['etat'])
		{
			case 'CMD':
				$new_order_atate = $confs['EMC_CMD'];
			break;
			case 'ENV':
				$new_order_atate = $confs['EMC_ENVO'];
			break;
			case 'ANN':
				$message = new Message();
				$texte = 'EnvoiMoinsCher : envoi annulé';
				$message->message = htmlentities($texte, ENT_COMPAT, 'UTF-8');
				$message->id_order = $order_id;
				$message->private = 1;
				$message->add();
				$new_order_atate = $confs['EMC_ANN'];
			break;
			case 'LIV':
				$new_order_atate = $confs['EMC_LIV'];
			break;
			default:
				die();
		}
		if ((int)$new_order_atate > 0 && $new_order_atate != $history_row[0]['id_order_state'])
		{
			$history = new OrderHistory();
			$history->id_order = $order_id;
			$history->changeIdOrderState($new_order_atate, $order_id);
			$history->id_employee = (int)$cookie->id_employee;
			$history->addWithemail();
		}
		/* only when all informations */
		if ($text_tracking == '')
		{
			$cmd_row = Db::getInstance()->ExecuteS('SELECT * FROM '._DB_PREFIX_.'order_state_lang
				 WHERE id_order_state = '.$new_order_atate.' AND id_lang = (SELECT id_lang FROM '._DB_PREFIX_.'lang WHERE iso_code = "FR") ');
			$text_tracking = 'Etat de votre commande : '.$cmd_row[0]['name'];
		}
		$date_get = date('Y-m-d H:i:s', strtotime($_GET['date']));
		if (!isset($_GET['date']))
			$date_get = date('Y-m-d H:i:s', time());
		/* insert tracking infos to EnvoiMoinsCher table */
		Db::getInstance()->Execute('INSERT INTO '._DB_PREFIX_.'emc_tracking
			 ('._DB_PREFIX_.'orders_id_order, state_et, date_et, text_et, localisation_et)
			 VALUES
			 ('.$order_id.', "'.$_GET['etat'].'", "'.$date_get.'", "'.$text_tracking.'", "'.$local_tracking.'")
			 ');
	}
	else
	{
		/* log incorrect values */
		Db::getInstance()->Execute('INSERT INTO '._DB_PREFIX_.'log
			 (severity, error_code, message, date_add, date_upd)
			 VALUES
			 (4, 1, "'.$error_msg.'", "'.date('Y-m-d H:i:s').'", "'.date('Y-m-d H:i:s').'")
			 ');
	}
}
else
{
	/* log incorrect values */
	Db::getInstance()->Execute('INSERT INTO '._DB_PREFIX_.'log
		 (severity, error_code, message, date_add, date_upd)
		 VALUES
		 (4, 1, "'.$error_msg.'", "'.date('Y-m-d H:i:s').'", "'.date('Y-m-d H:i:s').'")
		 ');
}