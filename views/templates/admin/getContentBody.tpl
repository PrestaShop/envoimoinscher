 {**
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
 *}

{if isset($missedValues) && count($missedValues)}
	<div class="bootstrap">
		<div class="alert alert-danger">
		Attention : certains champs n'ont pas été renseignés et le module peut ne pas fonctionner correctement. Les champs manquants :
		{foreach from=$missedValues key=m item=missed}
			<br />- {$missed|escape:'htmlall'}
		{/foreach}
	</div>
	</div>
{/if}
{if $EMC_config.EMC_USER != "" && $EMC_config.EMC_USER >= 3 && ($EMC_config.EMC_KEY == '' || $EMC_config.EMC_LOGIN == '' || $EMC_config.EMC_PASS == '')}
	<div class="bootstrap">
		<div class="alert alert-danger">Vos données de connexion ne sont pas correctes. Le module ne va pas fonctionner correctement.</div>
	</div>
{/if}
{if $multiShipping == 1}
	<div class="bootstrap">
		<div class="alert alert-danger">La version actuelle du module est incompatible avec l'option "Livraison à plusieurs adresses". Les offres du module ne seront pas proposées sur l'écran de commande.</div>
	</div>
	{/if}

{if $successForm == 1}
	<div class="conf confirm">Les données de configuration ont été mises à jour.
		{if $lastTab == '#confSrv'}
			<p>
				<b>Si vous avez fini de configurer les offres et que vous souhaitez les afficher sur votre boutique, n'oubliez pas de leur appliquer le mode "En ligne"</b>
			</p>
		{/if}
	</div>
{/if}

{foreach from=$API_errors item=error}
	{if $error.id === false}
	<div class="bootstrap">
		<div class="alert alert-danger">{l s='API error : unknow error' mod='envoimoinscher'}{$error.message|escape:'htmlall'}</div>
	</div>
	{else}
	<div class="bootstrap">
		<div class="alert alert-danger">{l s=$error.id mod='envoimoinscher'}</div>
	</div>
	{/if}
{/foreach}
{if $need_update}
	<div class="bootstrap">
		<div class="alert alert-warning">{l s='Warning update' mod='envoimoinscher'}</div>
	</div>
{/if}

<div id="EMC_Infos">
		<h2>EnvoiMoinsCher : configuration</h2>
		<p>Pour commencer à paramétrer votre module, merci de compléter point par point, les différents boutons ci-dessous en commençant par le bouton "Compte marchand".</p>
		<p><strong id="warn-online-message" {if $EMC_config.EMC_SRV_MODE === 'online'}style="color:black;"{else}style="color:orange;"{/if}>Une fois la configuration terminée,activez le mode en ligne pour que les offres de transport apparaissent au front-office.</strong></li>
		<p>NB : N'oubliez pas de sauvegarder chaque modification. Reportez-vous à la <a href="http://www.envoimoinscher.com/api/download/doc_prestashop_configurer.pdf">documentation</a> pour plus d’informations.</p>
</div>
<div id="EMC_top">
	<div id="EMC_Globals">
		<div id="EMC_state">
			<label>{l s='State of module:' mod='envoimoinscher'}</label>
			<div class="margin-form add-tooltip" title="{l s='Enable your module on your website' mod='envoimoinscher'}">
				<img src="../img/admin/{if $EMC_config.EMC_SRV_MODE === 'online'}enabled{else}disabled{/if}.gif" id="EMC_env" alt="{if $EMC_config.EMC_SRV_MODE === 'online'}true{else}false{/if}" onClick="EMCChangeState($(this))"/> 
				<label><span {if $EMC_config.EMC_SRV_MODE === 'online'}style="font-weight:bold;"{/if} id="mod-online">{l s='online' mod='envoimoinscher'}</span> / <span {if $EMC_config.EMC_SRV_MODE !== 'online'}style="font-weight:bold;"{/if} id="mod-offline">{l s='offline' mod='envoimoinscher'}</span></label>
			</div>
		</div>
		<div>
			<label>{l s='Work environment:' mod='envoimoinscher'}</label>
			<div class="margin-form add-tooltip" title="{l s='In your test dummy orders, production orders you place real' mod='envoimoinscher'}">
				<input type="radio" name="apiEnv" value="TEST" onClick="EMCChangeEnvironment($(this))" id="api_test"{if $EMC_config.EMC_ENV == 'TEST'} checked{/if}/>
				<label for="api_test">{l s='Test' mod='envoimoinscher'}</label>
				<input type="radio" name="apiEnv" value="PROD" onClick="EMCChangeEnvironment($(this))" id="api_prod"{if $EMC_config.EMC_ENV == 'PROD'} checked{/if}/>
				<label for="api_prod">{l s='Production' mod='envoimoinscher'}</label>
			</div>
		</div>
		<div>
			<label>
				<a id="cleanCache" class="action_module" href="{$link->getAdminLink('AdminEnvoiMoinsCher')|escape:'htmlall'}&option=cleanCache">
					{l s='Clear the cache:' mod='envoimoinscher'}
				</a>
			</label>
			<div class="margin-form add-tooltip" title="{l s='Clear the offers cache' mod='envoimoinscher'}">
			</div>
		</div>
	</div>
	<ul id="EMC_Menu">
		<li class="merchant{if Tools::getValue('EMC_tabs') === 'merchant' OR  (Tools::getValue('EMC_tabs') === false AND $EMC_config.EMC_USER >= 3)} active{/if}">
			<a href="#EMC_tab" data-tab="merchant">
			</a>
			<div>
				{l s='Merchant account' mod='envoimoinscher'}
			</div>
		</li>
		<li class="sends{if Tools::getValue('EMC_tabs') === 'sends'} active{/if}">
			<a href="#EMC_tab" data-tab="sends">
			</a>
			<div>
				{l s='Sends description' mod='envoimoinscher'}
			</div>
		</li>
		<li class="settings{if Tools::getValue('EMC_tabs') === 'settings'} active{/if}">
			<a href="#EMC_tab" data-tab="settings">
			</a>
			<div>
				{l s='Settings' mod='envoimoinscher'}
			</div>
		</li>
		<li class="simple_carriers{if Tools::getValue('EMC_tabs') === 'simple_carriers'} active{/if}">
			<a href="#EMC_tab" data-tab="simple_carriers">
			</a>
			<div>
				{l s='Simple carriers' mod='envoimoinscher'}
			</div>
		</li>
		<li class="advanced_carriers{if Tools::getValue('EMC_tabs') === 'advanced_carriers'} active{/if}">
			<a href="#EMC_tab" data-tab="advanced_carriers">
			</a>
			<div>
				{l s='Advanced carriers' mod='envoimoinscher'}
			</div>
		</li>
		<li class="simulator">
			<a id="simulator-link" href="{$link->getAdminLink('AdminEnvoiMoinsCher')|escape:'htmlall'}&option=tests" target="_blank" data-tab="simulator">
			</a>
			<div>
				{l s='Simulator' mod='envoimoinscher'}
			</div>
		</li>
		<li class="help{if Tools::getValue('EMC_tabs') === 'help'} active{/if}">
			<a href="#EMC_tab" data-tab="help">
			</a>
			<div>
				{l s='Help' mod='envoimoinscher'}
			</div>
		</li>
	</ul>
</div>
<script src="{$modulePath|escape:'htmlall'}js/jquery.tooltipster.min.js"></script>
<script type="text/javascript">
	{literal}
	var EMC_modify = false;

	$(document).ready(function() {
		$('#cleanCache').click(function() {
			$('#cacheCleaning').show();
			$.ajax({
				url: $(this).attr("href"),
				type: "GET", 
				dataType: "json", 
				success : function(ret)
				{
					$('#cacheCleaning').hide();
					if(ret.error != 0)
					{
						alert("Une erreur s'est produite pendant la suppression du cache");
					}
					else
					{
						alert("Le cache a été correctement supprimé");
					}
				}
			});
			return false;
		});
	});
	
	function loadAllCarriers(){
		$('#carriers_update_result').html("");
		$.ajax({
			url: $("#loadAllCarriers").attr("rel"),
			type: "GET", 
			dataType: "json", 
			success : function(ret)
			{
				var message = "";
				if (ret.offers_added.length == 0 && ret.offers_updated.length == 0 && ret.offers_deleted.length == 0)
				{
					message = "<p>Aucune mise à jour des offres disponible.</p>";
				}
				else
				{
					message = "<p style='color:green'>Mise à jour réussie</p><br/>";
					if (ret.offers_added.length > 0)
					{
						message += "<b>" + ret.offers_added.length + " nouvelle(s) offre(s) :</b>"
						message += "<ul style='margin-left:20px;list-style-type:square'>";
						for (i = 0 ; i < ret.offers_added.length ; i++)
						{
							message += "<li>"+ret.offers_added[i]+"</li>";
						}
						message += "</ul><br/>";
					}
					if (ret.offers_updated.length > 0)
					{
						message += "<b>" + ret.offers_updated.length + " offre(s) mise(s) à jour :</b>"
						//message += "<br/><b style='color:orange;'>Attention : si un transporteur que vous utilisez est mis à jour, vous devez le supprimer et le recréer via le module pour appliquer les changements de <u>description</u></b>"
						message += "<ul style='margin-left:20px;list-style-type:square'>";
						for (i = 0 ; i < ret.offers_updated.length ; i++)
						{
							message += "<li>"+ret.offers_updated[i]+"</li>";
						}
						message += "</ul><br/>";
					}
					if (ret.offers_deleted.length > 0)
					{
						message += "<b>" + ret.offers_deleted.length + " offre(s) supprimée(s) :</b>"
						message += "<ul style='margin-left:20px;list-style-type:square'>";
						for (i = 0 ; i < ret.offers_deleted.length ; i++)
						{
							message += "<li>"+ret.offers_deleted[i]+"</li>";
						}
						message += "</ul><br/>";
					}
				}
				$('#carriers_update_result').html(message);
			},
			error : function(ret)
			{
				$('#carriers_update_result').html("<p style='color:red'>Erreur lors de la reception de la liste des transporteurs : <br/><div style='color:red'>"+ret.responseText+"</div></p>");
			}
		});
		return false;
	}
	
	function EMCTooltipHelp() {
		var divClass = '<div class="tooltip"></div>';
		$(".add-tooltip").each(function() {
			var parent = $(this);
			parent.after(divClass);
			parent.next('.tooltip').attr('title', parent.attr('title'));
			parent.removeAttr('title');
			parent.removeClass('add-tooltip');
		});
		// Initialize tooltip
		$('.tooltip').tooltipster({
			position : 'right',
			maxWidth : 350
		});

	}

	function EMCtoggleCarrier (carrier) {
		EMC_modify = true;
		var EMC_env = $("#EMC_env").attr("alt");
		if (EMC_env == "true") {
			alert("{/literal}{l s='Please set the module state to off' mod='envoimoinscher'}{literal}");
			return;
		}
		var value = carrier.attr('alt');
		if (carrier.parents('tr').hasClass('disabled') === false) {
			var prices = carrier.parents('tr').find('.price').children('div');
			var checkbox = carrier.parent('td').find('input');
			if (value === 'true') {
				carrier.parents('tr').find('.parcelPoint').fadeOut();
				prices.fadeOut();
				carrier.attr('alt', 'false');
				checkbox.attr('checked', false);
				carrier.attr('src', '../img/admin/disabled.gif');
			} else {
				carrier.parents('tr').find('.parcelPoint').fadeIn();
				prices.fadeIn();
				carrier.attr('alt', 'true');
				checkbox.attr('checked', true);
				carrier.attr('src', '../img/admin/enabled.gif');
			}
		}
	}
	{/literal}
</script>
{if $EMC_config.EMC_USER >= 3}
	<fieldset id="EMC_Content">
		<img src="../img/loader.gif" alt="" />
	</fieldset>
	<script type="text/javascript">
		{literal}

			var EMC_load = false;
			var currentValueInput = null;
			var envUrl = "{/literal}{$envUrl}{literal}";
			$(function(){
				EMCGetContentAjax('{/literal}{Tools::getValue('EMC_tabs', 'merchant')}{literal}');
				$("#EMC_Menu > li").click(function(){
					// cas special de simulation
					if ($(this).hasClass('simulator')){
						window.open($("#simulator-link").attr("href"));
						return false;
					}
					
					if ($("#EMC_Menu > li.active").hasClass('simple_carriers') || $(this).hasClass('simple_carriers') || $(this).hasClass('advanced_carriers') || $("#EMC_Menu > li.active").hasClass('advanced_carriers')) {
						if (EMC_modify === true) {
							var message = '{/literal}{html_entity_decode(addslashes({l s='Did you save your settings before exiting?' mod='envoimoinscher'}))}{literal}';
							$(window).bind('beforeunload', function(){
								return message;
							});

							if ($("#EMC_Menu > li.active").hasClass('simple_carriers') || $("#EMC_Menu > li.active").hasClass('advanced_carriers'))
								if (!confirm(message))
									return false;

							if (!$(this).hasClass('simple_carriers') && !$(this).hasClass('advanced_carriers')) {
								$(window).unbind('beforeunload', '');
							}
						}
					}

					$("#EMC_Menu > li").removeClass("active");
					$(this).addClass('active');
					EMCGetContentAjax($(this).children('a').data('tab'));
					return false;
				});
			});

			function EMCGetContentAjax(tab) {
				if(EMC_load === true)
					return;
				EMC_load = true;
				$("#EMC_Content").html('<div class="center"><img src="../img/loader.gif" alt="" /></div>');
				$.ajax({
					type : "POST",
					url : "",
					data : "ajax=1&EMC_tab=" + tab,
					success : function (msg) {
						currentValueInput = null
						EMC_modify = false;
						$("#EMC_Content").html(msg);
						$(".row input[type=radio],.row input[type=checkbox]").change(function(){
							EMC_modify = true;
						});
						$(".row input[type=text]").focusin(function(){
							currentValueInput = $(this).val();
						});
						$(".row input[type=text]").focusout(function(){
							if (currentValueInput != $(this).val())
								EMC_modify = true;
						});

						$("input[name=btnCarriersSimple], input[name=btnCarriersAdvanced]").click(function(){
								$(window).unbind('beforeunload', '');
						});

						$(".fancybox").fancybox({
							'transitionIn'	:	'elastic',
							'transitionOut'	:	'elastic',
							'width'		:	1000, 
							'height'		:	900
						});
						EMC_load = false;
						EMCTooltipHelp();
					}
				});
			}

			function EMCChangeState(state) {
				var status = state.attr('alt');
				// Go off
				if(status === 'true') {
					if (!confirm("{/literal}{l s='Are you sure to offline the module? (Carriers Envoimoinscher will not be available to discount module)' mod='envoimoinscher'}{literal}")) {
						return false;
					}
					state.attr('alt', 'false');
					state.attr('src', '../img/admin/disabled.gif')
					$("#warn-online-message").css("color","orange");
					$("#mod-offline").css("font-weight","bold");
					$("#mod-online").css("font-weight","normal");
				}
				// Go on
				else {
					state.attr('alt', 'true');
					state.attr('src', '../img/admin/enabled.gif')
					$("#warn-online-message").css("color","black");
					$("#mod-online").css("font-weight","bold");
					$("#mod-offline").css("font-weight","normal");
				}
				status = state.attr('alt');
				$.ajax({
					type : "POST",
					url : "",
					data : "ajax=1&EMC_Status=" + status,
					success : function (msg) {
						if(status === 'false') {
							$(".offersList input").attr("disabled", false);
						}
						else {
							$(".offersList input").attr("disabled", true);
						}
					}
				});
			}

			function EMCChangeEnvironment(env) {
				var temp = env.attr("value");
				$.ajax({
					type : "POST",
					url : "",
					data : "ajax=1&EMC_Env=" + temp,
					success : function (msg) {
						// If you want message	
					}
				});
			}

			function openPopupEmc(ope) {
				var post_code = $("#EMC_exp_postcode").val();
				var address = $("#EMC_exp_address").val();
				var city = $("#EMC_exp_town").val();

				var popin_name = 'emcwindow';
				var popin_width = 800;
				var popin_height = 680;
				var popin_scrollbars = 1;
				var popin_resizable = 1;

				if(ope == "SOGP") {
					window.open(
						envUrl + "/magento_rc.html?fcp="+ post_code +"&fadr=" + address + "&fvil=" + city + "&TypeLiv=REL&type=Exp&isPrestashop=true",
						popin_name,
						"scrollbars=" + popin_scrollbars + ", resizable=" + popin_resizable + ",width=" + popin_width + ",height=" + popin_width + ""
					);
				} else if(ope == "MONR") {
					window.open(
						envUrl + "/modules_monr.html?isPrestashop=true&isModule=true&monrCp=" + post_code + "&monrVille=" + city + "&monrPays=FR&monrPoids=1",
						popin_name,
						"scrollbars=" + popin_scrollbars + ", resizable=" + popin_resizable + ",width=" + popin_width + ",height=" + popin_width + ""
					);
				}
			}
		{/literal}
	</script>
{else}
	<fieldset id="EMC_Content" style="display: none;">
		<ul class="EMC_steps">
			<li>
				<a{if $EMC_config.EMC_USER >= 0 || empty($EMC_config.EMC_USER) || $EMC_config.EMC_USER == ""} class="selected{if $EMC_config.EMC_USER > 0} old{/if}"{/if}>
					<label for="" class="stepNumber">1</label>
					<span class="stepDesc">{l s='Merchant account' mod='envoimoinscher'}</span>
				</a>
			</li>
			<li>
				<a{if $EMC_config.EMC_USER >= 1} class="selected{if $EMC_config.EMC_USER > 1} old{/if}"{/if}>
					<label for="" class="stepNumber">2</label>
					<span class="stepDesc">{l s='Sends description' mod='envoimoinscher'}</span>
				</a>
			</li>
			<li>
				<a{if $EMC_config.EMC_USER >= 2} class="selected"{/if}>
					<label for="" class="stepNumber">3</label>
					<span class="stepDesc">{l s='Carriers choice' mod='envoimoinscher'}</span>
				</a>
			</li>
		</ul>
		<div>
			<fieldset>
				{$content|unescape:'html'}
			</fieldset>
		</div>
		<div class="actionBar">
			<a class="btnPrev{if $EMC_config.EMC_USER >= 1} selected{/if}">{l s='Previous' mod='envoimoinscher'}</a>
			<a class="btnValid {if $EMC_config.EMC_USER < 2} selected{/if}">{l s='Next' mod='envoimoinscher'}</a>
			<a class="btnClose{if $EMC_config.EMC_USER == 2} selected{/if}">{l s='End' mod='envoimoinscher'}</a>
			<form method="POST" style="display: none;" id="btnPrev">
				<input type="hidden" name="previous" value="{$EMC_config.EMC_USER|escape:'htmlall'}" />
				<input type="submit">
			</form>
		</div>
	</fieldset>
	<script type="text/javascript">
		$(function(){

			$(".fancybox").fancybox();

			var content = $("#EMC_Content").html();
			var toAppend = '<div id="EMC_cfg_bg"></div>';
			{if $PS_ver == "1"}
				{if $PS_subver == "5"}
					toAppend = '<div id="EMC_cfg_bg" class="presta_1_5"></div>';
				{else}
					toAppend = '<div id="EMC_cfg_bg" class="presta_1_6"></div>';
				{/if}
			{/if}
			
			$("#content").append(toAppend);
			$("#EMC_Content:eq(0)").remove();
			var element = $("#EMC_cfg_bg");

			element.html('<div id="EMC_Content">' + content + '</div>');
			element.show();
			$("#content > .warn").appendTo("#EMC_cfg_bg #EMC_Content fieldset");

			$(".btnValid").click(function() {
				$(this).parent().prev('div').find('fieldset').find('input[type=submit]').click();
			});

			$(".btnClose.selected").click(function() {
				$(this).parent().prev('div').find('fieldset').find('input[type=submit]').click();
			});

			$(".btnPrev").click(function() {
				$("#btnPrev").submit();
			});

			// Initialize tooltip
			$('.tooltip').tooltipster({
				position : 'right',
				maxWidth : 350
			});

			EMCTooltipHelp();
		});
	</script>
{/if}