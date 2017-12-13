<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Entity\User;
use AppBundle\Form\InscriptionType;

class DefaultController extends Controller
{
	//generation du mot de passe
	public function chaine_aleatoire($nb_car, $chaine = 'azertyuiopqsdfghjklmwxcvbn123456789')
			{
			    $nb_lettres = strlen($chaine) - 1;
			    $generation = '';
			    for($i=0; $i < $nb_car; $i++)
			    {
			        $pos = mt_rand(0, $nb_lettres);
			        $car = $chaine[$pos];
			        $generation .= $car;
			    }
			    return $generation;
			}

    /**
     * @Route("/", name="homepage")
     */
    public function indexAction(Request $request)
    {
        
	    return $this->render('accueil/accueil.html.twig');
    }


    /**
     * @Route("/inscription", name="inscription")
     */
    public function inscriptionAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();

    	// $user = new User();

    	$userManager = $this->get('fos_user.user_manager');
		$user = $userManager->createUser();
 
    	$form = $this->createForm(InscriptionType::class, $user);

    	$form->handleRequest($request);


    	if ($form->isSubmitted() && $form->isValid()) {

    		//generation mot de passe
    		$chaine = 'azertyuiopqsdfghjklmwxcvbn123456789';
    		$nb_lettres = strlen($chaine) - 1;
			$mdp = '';
			    for($i=0; $i < 8; $i++)
			    {
			        $pos = mt_rand(0, $nb_lettres);
			        $car = $chaine[$pos];
			        $mdp .= $car;
			    }
			    
			// var_dump($mdp); exit;

   			 $user->setPlainPassword($mdp);
 
// $userManager->updateUser($user);
    		// enregistrement en base

			$user->setPlainpassword($mdp);
			$em->persist($user);
			$em->flush();


			//envoi du mail avec le mot de passe

			$message = \Swift_Message::newInstance()
				->setSubject('Inscription sur Galerie Photo')
		        ->setFrom('galeriephoto@appli.com')
		        ->setTo($user->getEmail())
		        ->setBody(
		            $this->renderView(
		                // templates/emails/registration.html.twig
		                'emails/inscription_mail.html.twig',
		                array('mdp' => $mdp, 'user' => $user->getUsername())
		            ),
		            'text/html'
		    )
    		;
          	$this->get('mailer')->send($message);


    		// return $this->redirect('accueil/accueil.html.twig');
    	}



        return $this->render('accueil/inscription.html.twig', array('form' => $form->createView(),));
    }


}
