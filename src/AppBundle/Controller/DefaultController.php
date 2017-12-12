<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Entity\User;
use AppBundle\Form\InscriptionType;

class DefaultController extends Controller
{
    /**
     * @Route("/", name="homepage")
     */
    public function indexAction(Request $request)
    {
        
    	$user = new User();

    	$form = $this->createForm(InscriptionType::class, $user);

    	if ($request->isMethod('POST') && $form->handleRequest($request)->isValid()) {

    		var_dump('ok'); exit;

    	}



        return $this->render('accueil/accueil.html.twig', array('form' => $form->createView(),));
    }



}
