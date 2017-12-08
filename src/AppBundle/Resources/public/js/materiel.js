function afficherMessageErreurConnexionAmazon()
{
	$message_erreur_entete.text("Erreur lors de la connexion à Amazon");
	$message_erreur_texte.text("Attendez 5 minutes et relancez la recherche");
	$message_erreur.removeClass( "hidden" );
}

function getAmazonItemCondition(etat_id)
{
	let item_condition = "";

	if(etat_id == '3')
		item_condition = "New";
	else if(amazon_tableau_etat_occasion_id.indexOf(etat_id) != -1)
		item_condition = "Used";
	else if(amazon_tableau_etat_collection_id.indexOf(etat_id) != -1)
		item_condition = "Collectible";
	else if(etat_id == '12')
		item_condition = "Refurbished";

	return item_condition;
}

function getAmazonPrixPlusBasEtPlusHaut(amazon_item_condition)
{
	$.ajax({
		method: "POST",
		url: Routing.generate('amazon_prix_plus_bas_et_plus_haut', {id_type: "EAN", id: $champ_amazon_code_barre.text(), etat: amazon_item_condition }),
		beforeSend: function(){
			$amazon_loader_recherche_concurrent.addClass( "active" );
		},
		success: function(data){

			if(data.erreur == '')
			{
				$message_erreur_entete.text("Erreur");
				$message_erreur_texte.text("Erreur lors de la connexion à Amazon. Réessayez dans 5 minutes.");
				$message_erreur.removeClass( "hidden" );

				amazon_recherche_concurrent_encours = false;
				$amazon_loader_recherche_concurrent.removeClass( "active" );

				chaine = "Prix le plus bas " + data.prix_le_plus_bas + " € et le plus haut " + data.prix_le_plus_haut + " € pour un materiel ";

				switch (amazon_item_condition)
				{
					case "New":
						chaine += "neuf";
						break;
					case "Used":
						chaine += "d'occasion";
						break;
					case "Collectible":
						chaine += "de collection";
						break;
					case "Refurbished":
						chaine += "reconditionné";
						break;
				}

				$amazon_prix_concurrent.text(chaine + ".");
			}
			else
			{
				if(data.erreur == 'connexion')
				{
					amazon_recherche_concurrent_encours = false;
					$amazon_loader_recherche_concurrent.removeClass( "active" );
					afficherMessageErreurConnexionAmazon($message_erreur_entete, $message_erreur_texte, $message_erreur);
				}
				else
				{
					amazon_recherche_concurrent_encours = false;
					$amazon_loader_recherche_concurrent.removeClass( "active" );

					$amazon_prix_concurrent.text(data.erreur);
				}
			}
		}
		/*
		,
		complete: function(jqXHR, textStatus){

			if(jqXHR.readyState == 0)
			{
				$message_erreur_entete.text("Erreur lors de la connexion à Amazon");
				$message_erreur_texte.text("Vérifier votre connexion internet");
				$message_erreur.removeClass( "hidden" );

				amazon_recherche_concurrent_encours = false;
				$amazon_loader_recherche_concurrent.removeClass( "active" );
			}
			else
			{
				nb_tentative_connexion++;

				if(textStatus == "error")
				{
					if(nb_tentative_connexion < 2)
					{
						// pause de 60 secondes et retente la récupération des données
						setTimeout(function(){
							getAmazonPrixPlusBasEtPlusHaut(amazon_item_condition, nb_tentative_connexion);
						}, 60000);
					}
					else // si 2 échec de connexion à Amazon, alors on affiche une erreur
					{
						amazon_recherche_concurrent_encours = false;
						$amazon_loader_recherche_concurrent.removeClass( "active" );
						afficherMessageErreurConnexionAmazon($message_erreur_entete, $message_erreur_texte, $message_erreur);
					}
				}
				else
				{
					amazon_recherche_concurrent_encours = false;
					$amazon_loader_recherche_concurrent.removeClass( "active" );
				}
			}
		}*/
	});
}


function pricingCalculerPrix($champ_prix,/* $champ_pricing_prix_pourcentage_min, $champ_pricing_prix_pourcentage_max,*/ $champ_pricing_prix_min, $champ_pricing_prix_max)
{
	let prix,
		prix_pourcentage_min,
		prix_pourcentage_max,
		prix_min = '',
		prix_max = '';

	if($champ_prix.val() != "")
	{
		prix = parseFloat( $champ_prix.val() );

		if($champ_pricing_prix_pourcentage_min.val() != "")
		{
			prix_pourcentage_min = parseFloat( $champ_pricing_prix_pourcentage_min.val() );
			prix_min = prix - (prix * (prix_pourcentage_min / 100));
		}

		if($champ_pricing_prix_pourcentage_max.val() != "")
		{
			prix_pourcentage_max = parseFloat( $champ_pricing_prix_pourcentage_max.val() );
			prix_max = prix + (prix * (prix_pourcentage_max / 100));
		}
	}

	$champ_pricing_prix_min
		.val(prix_min)
		.trigger( "change" )
	;

	$champ_pricing_prix_max
		.val(prix_max)
		.trigger( "change" )
	;
}

var fdp = '';

function calcul_frais_de_port($poids, $prixachat, $accessoire, $marketplace, $tva, $inputmin, $inputmax){
	var poids = $poids;
	var prixachat = $prixachat;
	//console.log(prixachat);
	var accessoire = $accessoire;
	var marketplace = $marketplace;
	var tva = $tva;
	//console.log(poids);
    $.ajax({
        url: $("#fraisdeport").data('fdp'),
        type: 'POST',
        //async: false,
        //dataType: 'json',
        data: {poids: poids, prixachat: prixachat, accessoire: accessoire, marketplace: marketplace, tva: tva},
        beforeSend: function(){  },
        /*success: function( response ){
        	return response;
        }*/
        //console.log("chargement");        
    })
    .done(function( data ){
      console.log("done");
      var $prix = data ;
      $inputmin.val($prix[0]);
      $inputmax.val($prix[1]);
      //alert(data);
      //console.log($prixmini[0]);
      //return fdp;
      
      //$popup.find('.popup-body').html('').append(html);
    })
    .fail(function(jqXHR, exception) {
    	 
	      var msg = '';
	      if (jqXHR.status === 0) {
	          msg = 'Not connect.\n Verify Network.';
	      } else if (jqXHR.status == 404) {
	          msg = 'Requested page not found. [404]';
	      } else if (jqXHR.status == 500) {
	          msg = 'Internal Server Error [500].';
	      } else if (exception === 'parsererror') {
	          msg = 'Requested JSON parse failed.';
	      } else if (exception === 'timeout') {
	          msg = 'Time out error.';
	      } else if (exception === 'abort') {
	          msg = 'Ajax request aborted.';
	      } else {
	          msg = 'Uncaught Error.\n' + jqXHR.responseText;
	      }
	      console.log(msg);
	      //console.log("error");
	    })
    .always(function() {
      //console.log("complete");
    });
      //console.log(fdp);

}


function pricingInitialiserEvenement(champ_prix_id,/* champ_pricing_prix_pourcentage_min_id, champ_pricing_prix_pourcentage_max_id, */$champ_prix, $champ_pricing, container_champs_pricing)
{
	// événement lors de l'activation ou la désactivation du pricing
	$champ_pricing.activer.change(function() {
		
		if(this.checked){
			container_champs_pricing.show();
			//TROUVER UNE MEILLEURE GESTION DES ERREURS
			//pricing
	/**		var $poids = $("#materiel_poids").val();
			//si le poids n'est pas défini
			if ($poids === '') {
				$poids = 0 ;
			}

			///////accessoire
			$accessoire = '0';
			//famille
			var $famille = $("#materiel_famille option:selected").attr("estaccessoire");
			//verif
			if ($famille == 'estAccessoire') {
				$accessoire = '1';
			} 
			if ($famille === ''){
				$accessoire = '0';
			}

			var $sous_famille = $("#materiel_sousFamille option:selected").attr("estaccessoire");
			if ($accessoire != '1' ) {
				if ($sous_famille == 'estAccessoire'){
					$accessoire = '1';
				}
				if ($sous_famille === ''){
					$accessoire = '0';
				}
			}

			$prixachat = $("#materiel_prixAchatMoyenHt").val();

			//tva
			$tva = $("#materiel_tauxtva option:selected").text();
			console.log('tva', $tva);
			//marketplace
			$marketplace = '';
			if ($champ_pricing == $champ_cdiscount_pricing) {
				$marketplace = 'cdiscount';
			} else {
				$marketplace = 'marketplace';
			}//faire pour chaque marketplace

			if ($poids == 0 ){
				alert('Veuillez entrer un poid pour le pricing et enregister le materiel');
			} else {
				if ($('#materiel_sousFamille option:selected').text() === ''){
					alert('Veuillez entrer une Sous-Famille et enregistrer le materiel');
				} else {
					//SI PAS UN MARKETPLACE PAS DE CALCUL !!!! //
				calcul_frais_de_port($poids, $prixachat, $accessoire, $marketplace, $tva );
				//console.log(fdp);
				}
			}
			//envoie sur la fonction de calcul
	**/
		}else{
			container_champs_pricing.hide();	
		}
	});

	// événement pour calculer les prix min et max selon les pourcentages données
	/*$(champ_prix_id + ", " + champ_pricing_prix_pourcentage_min_id + ", " + champ_pricing_prix_pourcentage_max_id).keyup(function( event ) {
		if(event.key != "Shift")
			pricingCalculerPrix($champ_prix, $champ_pricing.prix_pourcentage_min, $champ_pricing.prix_pourcentage_max, $champ_pricing.prix_min, $champ_pricing.prix_max);
	});*/
}


function lier_champ_principal_au_champ_marketplace(champ_principal, tableau_champs_marketplaces, tableau_drapeau, champ_principal_type = "text", executerEvenement = false)
{
	let i, valeur, taille;

	// initialisation du tableau des drapeaux des champs des marketplaces
	taille = tableau_champs_marketplaces.length;
	for(i=0; i<taille; i++)
		tableau_drapeau[i] = true;

	// initialisation de l'événement du champ principale
	champ_principal.change(function() {
		valeur = $(this).val();

		$.each(tableau_champs_marketplaces, function( index, valeur2 ) {
			if(tableau_drapeau[index] == true)
			{
				switch (champ_principal_type)
				{
					case "text":
						valeur2.val(valeur);
						break
					case "checkbox":
						if( champ_principal.prop( "checked" ) )
							valeur2.prop( "checked", true );
						else
							valeur2.prop( "checked", false );

						// deuxième solution : valeur2.prop( "checked", champ_principal.prop( "checked" ) );

						if(executerEvenement) // pour l'instant seule la checkbox activer pricing est consernée par la méthode trigger().
							valeur2.trigger( "change", false );

						break
				}
			}
		});
	});

	// initialisation des événements des champs des marketplaces
	$.each(tableau_champs_marketplaces, function( index, valeur ) {
		valeur.change(function(event, manuel) {

			if(manuel != false)
				tableau_drapeau[index] = false;
		});
	});
}

// collection de form codesBarres
function initMarketplacesListeCodeBarre()
{
	let index, i;

	index = $champ_amazon_code_barre.prop("selectedIndex");

	// on vide la liste
	$champ_amazon_code_barre.html('');

	// ajouter des codes EAN saisies dans la fiche du materiel
	i=0;
	$codesBarres_collection.find('li').not(':last').each(function() {

		valeur = $(this).find('input:first').val();

		$champ_amazon_code_barre.append('<option value='+ i +'>'+ valeur +'</option>');
		i++;
	});

	if(index >= i || index == -1)
		$champ_amazon_code_barre.prop('selectedIndex', 0); 
	else
		$champ_amazon_code_barre.prop('selectedIndex', index); 
}

function initCodesBarres($tagFormLi)
{
	$tagFormLi.find('input:first').change(function() {
		initMarketplacesListeCodeBarre();
	});
}

function addTagFormDeleteLink($tagFormLi) {

	let $removeForm = $('<button class="ui basic button" class="add_tag_link">Supprimer</button>');

	$tagFormLi.find('.supprimer').append($removeForm);

	$removeForm.on('click', function(e) {
		e.preventDefault();

		$tagFormLi.remove();

		initMarketplacesListeCodeBarre();
	});
}

function addTagForm($collection, $newLinkLi) {
	let prototype = $collection.data('prototype');

	let index = $collection.data('index');

	let newForm = prototype.replace(/__name__/g, index);

	$collection.data('index', index + 1);

	let $newFormLi = $('<li></li>').append(newForm);
	$newLinkLi.before($newFormLi);

	initCodesBarres($newFormLi);
	addTagFormDeleteLink($newFormLi);
}

var champ_sousfamille = '#materiel_sousFamille',
	champ_plateform_id = '#materiel_plateforme', // champ_plateform_id initialisé en dur, car le champs n'est pas forcement affiché et on ne peut pas récupérer l'identifiant avec twig.
	champ_prix_ttc_id = '#materiel_prixVenteTtc',
	//champ_global_pricing_prix_pourcentage_min_id = '#materiel_globalPricingPrixPourcentageMin',
	//champ_global_pricing_prix_pourcentage_max_id = '#materiel_globalPricingPrixPourcentageMax'
	champ_amazon_prix_id = '#materiel_materielMarketplaceAmazon_prix',
	
	//champ_amazon_pricing_prix_pourcentage_min_id = '#materiel_materielMarketplaceAmazon_pricing_prixPourcentageMin',
	//champ_amazon_pricing_prix_pourcentage_max_id = '#materiel_materielMarketplaceAmazon_pricing_prixPourcentageMax'
	champ_cdiscount_prix_id = "#materiel_materielMarketplaceCdiscount_prix"
	champ_priceminister_prix_id = "#materiel_materielMarketplacePriceminister_prix"
	champ_ebay_prix_id = "#materiel_materielMarketplaceEbay_prix"
	champ_fnac_prix_id = "#materiel_materielMarketplaceFnac_prix"
	champ_darty_prix_id = "#materiel_materielMarketplaceDarty_prix"

;

var $champ_famille = $('#materiel_famille'),
	$champ_categorie = $('#materiel_sousFamille'),
	$champ_plateform = $(champ_plateform_id),
	$champ_etat = $('#materiel_etat'),
	$champ_prix_ttc = $(champ_prix_ttc_id),
	$prixMoyenAchat = $("#materiel_prixAchatMoyenHt"),

	$champ_global_pricing = {
		activer: $('#materiel_globalPricingActiver'),
		//prix_pourcentage_min: $(champ_global_pricing_prix_pourcentage_min_id),
		//prix_pourcentage_max: $(champ_global_pricing_prix_pourcentage_max_id),
		prix_min: $('#materiel_globalPricingPrixMin'),
		prix_max: $('#materiel_globalPricingPrixMax'),
		prix_ecart: $('#materiel_globalPricingPrixEcart'),
		satisfaction_taux: $('#materiel_globalPricingSatisfactionTaux'),
		decompte_eval_min: $('#materiel_globalPricingDecompteEvalMin')
	},
	container_champs_global_pricing = $('#container_champs_global_pricing'),
	container_champs_amazon_pricing = $('#container_champs_amazon_pricing'),
	container_champs_cdiscount_pricing = $('#container_champs_cdiscount_pricing'),
	container_champs_priceminister_pricing = $('#container_champs_priceminister_pricing'),
	container_champs_fnac_pricing = $('#container_champs_fnac_pricing'),
	container_champs_ebay_pricing = $('#container_champs_ebay_pricing'),
	container_champs_darty_pricing = $('#container_champs_darty_pricing')
;

var $champ_amazon_code_barre = $('#materiel_materielMarketplaceAmazon_codeBarre'),
	$champ_amazon_etat = $('#materiel_materielMarketplaceAmazon_etat'),
	$champ_amazon_prix = $(champ_amazon_prix_id),
	$champ_amazon_pricing = {
		activer: $('#materiel_materielMarketplaceAmazon_pricing_pricingActiver'),
		//prix_pourcentage_min: $(champ_amazon_pricing_prix_pourcentage_min_id),
		//prix_pourcentage_max: $(champ_amazon_pricing_prix_pourcentage_max_id),
		prix_min: $('#materiel_materielMarketplaceAmazon_pricing_prixMin'),
		prix_max: $('#materiel_materielMarketplaceAmazon_pricing_prixMax'),
		prix_ecart: $('#materiel_materielMarketplaceAmazon_pricing_prixEcart'),
		satisfaction_taux: $('#materiel_materielMarketplaceAmazon_pricing_satisfactionTaux'),
		decompte_eval_min: $('#materiel_materielMarketplaceAmazon_pricing_decompteEvalMin')
	},
	$champ_cdiscount_prix = $(champ_cdiscount_prix_id),
	$champ_cdiscount_pricing = {
		activer: $('#materiel_materielMarketplaceCdiscount_pricing_pricingActiver'),
		//prix_pourcentage_min: $(champ_amazon_pricing_prix_pourcentage_min_id),
		//prix_pourcentage_max: $(champ_amazon_pricing_prix_pourcentage_max_id),
		prix_min: $('#materiel_materielMarketplaceCdiscount_pricing_prixMin'),
		prix_max: $('#materiel_materielMarketplaceCdiscount_pricing_prixMax'),
		prix_ecart: $('#materiel_materielMarketplaceCdiscount_pricing_prixEcart'),
		satisfaction_taux: $('#materiel_materielMarketplaceCdiscount_pricing_satisfactionTaux'),
		decompte_eval_min: $('#materiel_materielMarketplaceCdiscount_pricing_decompteEvalMin')
	},
	$champ_priceminister_prix = $(champ_priceminister_prix_id),
	$champ_priceminister_pricing = {
		activer: $('#materiel_materielMarketplacePriceminister_pricing_pricingActiver'),
		//prix_pourcentage_min: $(champ_amazon_pricing_prix_pourcentage_min_id),
		//prix_pourcentage_max: $(champ_amazon_pricing_prix_pourcentage_max_id),
		prix_min: $('#materiel_materielMarketplacePriceminister_pricing_prixMin'),
		prix_max: $('#materiel_materielMarketplacePriceminister_pricing_prixMax'),
		prix_ecart: $('#materiel_materielMarketplacePriceminister_pricing_prixEcart'),
		satisfaction_taux: $('#materiel_materielMarketplacePriceminister_pricing_satisfactionTaux'),
		decompte_eval_min: $('#materiel_materielMarketplacePriceminister_pricing_decompteEvalMin')
	},
	$champ_fnac_prix = $(champ_fnac_prix_id),
	$champ_fnac_pricing = {
		activer: $('#materiel_materielMarketplaceFnac_pricing_pricingActiver'),
		//prix_pourcentage_min: $(champ_amazon_pricing_prix_pourcentage_min_id),
		//prix_pourcentage_max: $(champ_amazon_pricing_prix_pourcentage_max_id),
		prix_min: $('#materiel_materielMarketplaceFnac_pricing_prixMin'),
		prix_max: $('#materiel_materielMarketplaceFnac_pricing_prixMax'),
		prix_ecart: $('#materiel_materielMarketplaceFnac_pricing_prixEcart'),
		satisfaction_taux: $('#materiel_materielMarketplaceFnac_pricing_satisfactionTaux'),
		decompte_eval_min: $('#materiel_materielMarketplaceFnac_pricing_decompteEvalMin')
	},
	$champ_ebay_prix = $(champ_ebay_prix_id),
	$champ_ebay_pricing = {
		activer: $('#materiel_materielMarketplaceEbay_pricing_pricingActiver'),
		//prix_pourcentage_min: $(champ_amazon_pricing_prix_pourcentage_min_id),
		//prix_pourcentage_max: $(champ_amazon_pricing_prix_pourcentage_max_id),
		prix_min: $('#materiel_materielMarketplaceEbay_pricing_prixMin'),
		prix_max: $('#materiel_materielMarketplaceEbay_pricing_prixMax'),
		prix_ecart: $('#materiel_materielMarketplaceEbay_pricing_prixEcart'),
		satisfaction_taux: $('#materiel_materielMarketplaceEbay_pricing_satisfactionTaux'),
		decompte_eval_min: $('#materiel_materielMarketplaceEbay_pricing_decompteEvalMin')
	},
	$champ_darty_prix = $(champ_darty_prix_id),
	$champ_darty_pricing = {
		activer: $('#materiel_materielMarketplaceDarty_pricing_pricingActiver'),
		//prix_pourcentage_min: $(champ_amazon_pricing_prix_pourcentage_min_id),
		//prix_pourcentage_max: $(champ_amazon_pricing_prix_pourcentage_max_id),
		prix_min: $('#materiel_materielMarketplaceDarty_pricing_prixMin'),
		prix_max: $('#materiel_materielMarketplaceDarty_pricing_prixMax'),
		prix_ecart: $('#materiel_materielMarketplaceDarty_pricing_prixEcart'),
		satisfaction_taux: $('#materiel_materielMarketplaceDarty_pricing_satisfactionTaux'),
		decompte_eval_min: $('#materiel_materielMarketplaceDarty_pricing_decompteEvalMin')
	},

	$champ_cdiscount_etat = $('#materiel_materielMarketplaceCdiscount_etat'),
	$champ_cdiscount_prix = $('#materiel_materielMarketplaceCdiscount_prix'),
	$champ_priceminister_etat = $('#materiel_materielMarketplacePriceminister_etat'),
	$champ_priceminister_prix = $('#materiel_materielMarketplacePriceminister_prix'),
	$champ_fnac_etat = $('#materiel_materielMarketplaceFnac_etat'),
	$champ_fnac_prix = $('#materiel_materielMarketplaceFnac_prix'),
	$champ_ebay_etat = $('#materiel_materielMarketplaceEbay_etat'),
	$champ_ebay_prix = $('#materiel_materielMarketplaceEbay_prix'),
	$champ_darty_etat = $('#materiel_materielMarketplaceDarty_etat'),
	$champ_darty_prix = $('#materiel_materielMarketplaceDarty_prix'),
	
	$vendreSur = {
		Amazon:  $('#materiel_vendreSurAmazon'),
		Cdiscount: $('#materiel_vendreSurCdiscount'),
		Priceminister: $('#materiel_vendreSurPriceminister'),
		Fnac: $('#materiel_vendreSurFnac'),
		Darty: $('#materiel_vendreSurDarty')
	};

var $codesBarres_collection,
	$codesBarres_bouton_ajouter = $('<a href="#" class="add_tag_link">Ajouter un code EAN</a>'),
	$codesBarres_element_li = $('<li></li>').append($codesBarres_bouton_ajouter),
	$codesBarres_elements_li,
	$message_erreur = $('#message_negatif'),
	$message_erreur_entete = $message_erreur.children('.header'),
	$message_erreur_texte = $message_erreur.children('p'),
	chaine = "",
	objet_liaison_etat_etatmarketplace = {},
	amazon_tableau_etat_occasion_id = ['4', '5', '6', '7'],
	amazon_tableau_etat_collection_id = ['8', '9', '10', '11'],
	amazon_recherche_concurrent_encours = false,
	$amazon_bouton_recherche_concurrent = $('#amazon_bouton_recherche_concurrent'),
	$amazon_loader_recherche_concurrent = $amazon_bouton_recherche_concurrent.next(),
	$amazon_prix_concurrent = $('#amazon_prix_concurrent'),
	regex_obj_code_ean = new RegExp(/^[0-9]{13}$/),
	amazon_item_condition = "",
	valeur = "",
	tableau_drapeau_satisfaction_taux = [],
	tableau_drapeau_decompte_eval_min = [],
	$form = $('form'),
	tabular_1_items = $form.children(".tabular").children('.item'),
	tabular_2_items = $form.children(".tab[data-tab='marketplaces']").children(".tabular").children('.item')
;

var champ_plateform_dropdown_configuration = {
	placeholder: false
};

//variable de configuration pricing  //$(".iddata").data("videoid");
var $configuration = { 
	//generale
	margeMiniSurJeuVideo: $("#configurationPricing").data("margeminisurjeuvideo"),
	margemaxsurjeuvideo: $('#configurationPricing').data("margemaxsurjeuvideo"),
	margeminisuraccessoire: $('#configurationPricing').data("margeminisuraccessoire"),
	margemaxsuraccessoire: $('#configurationPricing').data("margemaxsuraccessoire"),
	//amazon
	amazoncommission: $('#configurationAmazon').data("amazoncommission"),
	amazonfraisfixes: $('#configurationAmazon').data("amazonfraisfixes"),
	//ebay
	ebaycommission: $('#configurationEbay').data("ebaycommission"),
	ebayfraisfixes: $('#configurationEbay').data("ebayfraisfixes"),
	//Priceminister
	priceministercommission: $('#configurationPriceminister').data("priceministercommission"),
	priceministerfraisfixes: $('#configurationPriceminister').data("priceministerfraisfixes"),
	//Cdiscount
	cdiscountcommission: $('#configurationCdiscount').data("cdiscountcommission"),
	cdiscountfraisfixes: $('#configurationCdiscount').data("cdiscountfraisfixes"),
	//Fnac
	fnaccommission: $('#configurationFnac').data("fnaccommission"),
	fnacfraisfixes: $('#configurationFnac').data("fnacfraisfixes"),
	//Darty
	dartycommission: $('#configurationDarty').data("dartycommission"),
	dartyfraisfixes: $('#configurationDarty').data("dartyfraisfixes"),
};
//pricing
var $poids = $("#materiel_poids").val();
//si le poids n'est pas défini
if ($poids === '') {
	$poids = 0 ;
}

///////accessoire
$accessoire = '0';
//famille
var $famille = $("#materiel_famille option:selected").attr("estaccessoire");
//verif
if ($famille == 'estAccessoire') {
	$accessoire = '1';
} 
if ($famille === ''){
	$accessoire = '0';
}

var $sous_famille = $("#materiel_sousFamille option:selected").attr("estaccessoire");
if ($accessoire != '1' ) {
	if ($sous_famille == 'estAccessoire'){
		$accessoire = '1';
	}
	if ($sous_famille === ''){
		$accessoire = '0';
	}
}



$( document ).ready(function() {


	$(".mark").each(function(){ 
		//console.log("item");
		var $market = $(this).data('tab');
		switch ($market) {
			case "marketplaces/amazon":
				var $vendreSurAmazon = $('#materiel_vendreSurAmazon');
				if ( $vendreSurAmazon.prop("checked") == false) {
					$(this).css({"background":"#e0e1e2"});
				} else {
					$(this).css({"color":"#0db9b1"})
				}
			break;
			case "marketplaces/cdiscount":
				var $vendreSurCdiscount = $('#materiel_vendreSurCdiscount');
				if ( $vendreSurCdiscount.prop("checked") == false) {
					$(this).css({"background":"#e0e1e2"});
				} else {
					$(this).css({"color":"#0db9b1"})
				}
			break;
			case "marketplaces/priceminister":
				var $vendreSurPriceminister = $('#materiel_vendreSurPriceminister');
				if ( $vendreSurPriceminister.prop("checked") == false) {
					$(this).css({"background":"#e0e1e2"});
				} else {
					$(this).css({"color":"#0db9b1"})
				}
			break;
			case "marketplaces/fnac":
				var $vendreSurFnac = $('#materiel_vendreSurFnac');
				if ( $vendreSurFnac.prop("checked") == false) {
					$(this).css({"background":"#e0e1e2"});
				} else {
					$(this).css({"color":"#0db9b1"})
				}
			break;
			case "marketplaces/ebay":
				var $vendreSurEbay = $('#materiel_vendreSurEbay');
				if ( $vendreSurEbay.prop("checked") == false) {
					$(this).css({"background":"#e0e1e2"});
				} else {
					$(this).css({"color":"#0db9b1"})
				}
			break;
			case "marketplaces/darty":
				var $vendreSurDarty = $('#materiel_vendreSurDarty');
				if ( $vendreSurDarty.prop("checked") == false) {
					$(this).css({"background":"#e0e1e2"});
				} else {
					$(this).css({"color":"#0db9b1"})
				}
			break;
			default:
				console.log('nothing');
			break;
		}
  		// $(this).css({"background":"#e0e1e2"});
	});
	
	//mise a jour prix max et min au changement d'onglet de marketplace
	$(".mark").click(function(){
		var $poids       = $("#materiel_poids").val();
		var $prixachat   = $("#materiel_prixAchatMoyenHt").val();
		var $marketplace = $(this).attr('id');
		$tva             = $("#materiel_tauxtva option:selected").text();
		var $accessoire  = '0';
		//famille
		var $famille     = $("#materiel_famille option:selected").attr("estaccessoire");
		//verif
		if ($famille == 'estAccessoire') {
			$accessoire  = '1';
		} 
		if ($famille === ''){
			$accessoire  = '0';
		}

		var $sous_famille = $("#materiel_sousFamille option:selected").attr("estaccessoire");
		if ($accessoire != '1' ) {
			if ($sous_famille == 'estAccessoire'){
				$accessoire = '1';
			}
			if ($sous_famille === ''){
				$accessoire = '0';
			}
		}
		//input a remplir
		switch ($marketplace) {
			case 'amazon':
				var $inputmin = $("#materiel_materielMarketplaceAmazon_pricing_prixMin");
				var $inputmax = $("#materiel_materielMarketplaceAmazon_pricing_prixMax");
			break;
			case 'cdiscount':
				var $inputmin = $("#materiel_materielMarketplaceCdiscount_pricing_prixMin");
				var $inputmax = $("#materiel_materielMarketplaceCdiscount_pricing_prixMax");
			break;
			case 'priceminister':
				var $inputmin = $("#materiel_materielMarketplacePriceminister_pricing_prixMin");
				var $inputmax = $("#materiel_materielMarketplacePriceminister_pricing_prixMax");
			break;
			case 'fnac':
				var $inputmin = $("#materiel_materielMarketplaceFnac_pricing_prixMin");
				var $inputmax = $("#materiel_materielMarketplaceFnac_pricing_prixMax");
			break;
			case 'ebay':
				var $inputmin = $("#materiel_materielMarketplaceEbay_pricing_prixMin");
				var $inputmax = $("#materiel_materielMarketplaceEbay_pricing_prixMax");
			break;
			case 'darty':
				var $inputmin = $("#materiel_materielMarketplaceDarty_pricing_prixMin");
				var $inputmax = $("#materiel_materielMarketplaceDarty_pricing_prixMax");
			break;
			default:
				console.log('nothing');
			break;
		}
		//$("#materiel_materielMarketplaceCdiscount_pricing_prixMax")


		// console.log($poids);
		// console.log($prixachat);
		// console.log($accessoire);
		// console.log($marketplace);
		// console.log($tva);

		calcul_frais_de_port($poids, $prixachat, $accessoire, $marketplace, $tva, $inputmin, $inputmax);

		
	});


	//console.log($accessoire);

	// pour activer les tabs du premier champ du formulaire qui a une erreur
	$('button[type=submit]').click(function(event) {

		if( $form[0].checkValidity() == false )
		{
			let elements = $form.find(':input'),
				nb = elements.length,
				element,
				i = 0,
				drapeau = true,
				tab_nom,
				tab_parent
			;

			while(i < nb && drapeau == true)
			{
				element = elements[i];

				if( element.checkValidity() == false )
				{
					tab_parent = $(element).parents( ".tab" );

					if(tab_parent.length == 1)
					{
						tab_nom = tab_parent.attr( "data-tab" );
						
						tabular_1_items.each(function( index ) {
							if($( this ).attr( "data-tab" ) == tab_nom)
								$( this ).addClass( "active" );
							else
								$( this ).removeClass( "active" );
						});
					}
					else // sinon 2
					{
						tab_nom = $(tab_parent[1]).attr( "data-tab" );
						
						tabular_1_items.each(function( index ) {
							if($( this ).attr( "data-tab" ) == tab_nom)
								$( this ).addClass( "active" );
							else
								$( this ).removeClass( "active" );
						});

						tab_nom = $(tab_parent[0]).attr( "data-tab" );
						
						tabular_2_items.each(function( index ) {
							if( $( this ).attr( "data-tab" ) == tab_nom)
								$( this ).addClass( "active" );
							else
								$( this ).removeClass( "active" );
						});
					}

					$.tab('change tab', tab_nom);

					drapeau = false;
				}
				i++;
			}
		}
	});

	$('.tabular.menu .item').tab();

	$('.tabular.menu .item mark').tab();

	$('input[type="text"]:first').focus();

	$('label + i')
		.popup()
	;

	$('.message .close')
		.on('click', function() {
			$(this)
			.closest('.message')
			.transition('fade')
			;
		})
	;



	// ONGLET FICHE

	$champ_categorie
		.dropdown({
			placeholder: false
		})
	;



	// gestion de l'affichage du champ plateforme
	if ($champ_plateform.length != 0)
	{
		$champ_plateform
			.dropdown(champ_plateform_dropdown_configuration)
		;
	}

	$champ_famille
		.dropdown({
			onChange: function(value, text, $selectedItem) {
				let $form = $(this).closest('form');

				let data = {};
				data[$(this).attr('name')] = value;

				$.ajax({
					url : $form.attr('action'),
					type: $form.attr('method'),
					data : data,
					success: function(html) {
						$champ_categorie.parent().replaceWith(
							$(html).find(champ_sousfamille).dropdown()
						);

						$champ_categorie = $(champ_sousfamille);

						$nouveau_champ_plateform = $(html).find(champ_plateform_id);

						if ($nouveau_champ_plateform.length > 0 && $champ_plateform.length == 0) // si la catégorie sélectionnée autorise l'affichage des plateformes et que le champ plateform n'est pas déjà affiché
						{
							// on affiche le champ plateform en dessous des champs Famille et Sous-Famille
							$champ_famille.parents('.fields').after(
								$nouveau_champ_plateform.parents('.fields')
							);

							$champ_plateform = $(champ_plateform_id);

							$champ_plateform
								.dropdown(champ_plateform_dropdown_configuration)
							;
						}
						else if ($nouveau_champ_plateform.length == 0 && $champ_plateform.length != 0) // on supprime le champ plateform s'il est affiché et que la catégorie sélectionnée n'autorise pas l'affichage des plateformes
						{
							$champ_plateform.parents('.fields').remove();
							$champ_plateform = $(); // initialisation d'un objet jQuery vide
						}
					}
				});
			}
		})
	;

	// initialisation de la collection de form codesBarresAssocies
	$codesBarres_collection = $('ul.codes_barres');

	$codesBarres_collection.append($codesBarres_element_li);

	$codesBarres_collection.data('index', $codesBarres_collection.find(':input').length);

	$codesBarres_bouton_ajouter.on('click', function(e) {
		e.preventDefault();

		addTagForm($codesBarres_collection, $codesBarres_element_li);
	});

	$codesBarres_elements_li = $codesBarres_collection.find('li');

	initCodesBarres( $codesBarres_elements_li.first() );

	$codesBarres_elements_li.not(":nth-child(1)").each(function() {
		initCodesBarres($(this));
		addTagFormDeleteLink($(this));
	});
	
	// autre
	pricingInitialiserEvenement(champ_prix_ttc_id, /* champ_global_pricing_prix_pourcentage_min_id, champ_global_pricing_prix_pourcentage_max_id, */ $champ_prix_ttc, $champ_global_pricing, container_champs_global_pricing, $configuration);

	//quand un checkbox est check et si pricing activé alors function de caclul en fonction du marketplace
	//pricingventemarketplaceEvent($vendreSur);

	// ONGLET MARKETPLACES AMAZON

	// code pour récupérer le prix le plus bas et le prix le plus haut d'un materiel sur Amazon
	$amazon_bouton_recherche_concurrent.click(function(event) {

		event.preventDefault();

		if(amazon_recherche_concurrent_encours == false)
		{					
			if($champ_amazon_etat.val() == "")
				alert("Sélectionnez un état Amazon pour le materiel.");
			else if(regex_obj_code_ean.test( $champ_amazon_code_barre.text() ) == false)
				alert("Le code EAN n'est pas valide.");
			else
			{
				amazon_recherche_concurrent_encours = true;

				amazon_item_condition = getAmazonItemCondition( $champ_amazon_etat.val(), amazon_tableau_etat_occasion_id, amazon_tableau_etat_collection_id );

				getAmazonPrixPlusBasEtPlusHaut(amazon_item_condition);
			}
		}
	});

	$amazon_loader_recherche_concurrent.removeClass( "active" );

	// code pour le pricing Amazon
	pricingInitialiserEvenement(champ_amazon_prix_id, $champ_amazon_prix, $champ_amazon_pricing, container_champs_amazon_pricing, $configuration);

	//code pour le pricing Cdiscount
	pricingInitialiserEvenement(champ_cdiscount_prix_id, $champ_cdiscount_prix, $champ_cdiscount_pricing, container_champs_cdiscount_pricing, $configuration);

	//code pour le pricing Pricemminister
	pricingInitialiserEvenement(champ_priceminister_prix_id, $champ_priceminister_prix, $champ_priceminister_pricing, container_champs_priceminister_pricing, $configuration);

	//code pour le pricing Fnac
	pricingInitialiserEvenement(champ_fnac_prix_id, $champ_fnac_prix, $champ_fnac_pricing, container_champs_fnac_pricing, $configuration);

	//code pour le pricing Ebay
	pricingInitialiserEvenement(champ_ebay_prix_id, $champ_ebay_prix, $champ_ebay_pricing, container_champs_ebay_pricing, $configuration);

	//code pour le pricing Darty
	pricingInitialiserEvenement(champ_darty_prix_id, $champ_darty_prix, $champ_darty_pricing, container_champs_darty_pricing, $configuration);


	$('#bouton_test_pricing').click(function(event) {

		event.preventDefault();

		if($champ_amazon_etat.val() == "")
			alert("Sélectionnez un état Amazon pour le materiel.");
		else if(regex_obj_code_ean.test( $champ_amazon_code_barre.text() ) == false)
			alert("Le code EAN n'est pas valide.");
		else if($champ_amazon_pricing.prix_min.val() == "" || $champ_amazon_pricing.prix_max.val() == "" || $champ_amazon_pricing.prix_ecart.val() == "" || $champ_amazon_pricing.satisfaction_taux.val() == "" || $champ_amazon_pricing.decompte_eval_min.val() == "")
			alert("Tous les champs pricing doivent être remplis");
		else
		{
			amazon_item_condition = getAmazonItemCondition( $champ_amazon_etat.val(), amazon_tableau_etat_occasion_id, amazon_tableau_etat_collection_id );

			$.ajax({
				method: "POST",
				url: Routing.generate('amazon_test_pricing_materiel_nouveau', {idType: "EAN", id: $champ_amazon_code_barre.text(), codePays: 'FR', etat: amazon_item_condition, prixMin: $champ_amazon_pricing.prix_min.val(), prixMax: $champ_amazon_pricing.prix_max.val(), prixEcart: $champ_amazon_pricing.prix_ecart.val(), satisfactionTaux: $champ_amazon_pricing.satisfaction_taux.val(), decompteEvalMin: $champ_amazon_pricing.decompte_eval_min.val() }),
				success: function(data){
					if(data.erreur != "")
						$('#bouton_test_pricing').next("p").html(data.erreur);
					else
						$('#bouton_test_pricing').next("p").html(data.prixOptimise);
				},
				complete: function(jqXHR, textStatus){
					if(textStatus == "error")
						afficherMessageErreurConnexionAmazon($message_erreur_entete, $message_erreur_texte, $message_erreur);
				}
			});
		}
	});
/*
	$vendreSur.Cdiscount.change(function(){
		if(this.checked)
			
		else

		;

	}); */


});