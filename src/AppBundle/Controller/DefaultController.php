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
        

        $csrfToken = $this->has('security.csrf.token_manager')
            ? $this->get('security.csrf.token_manager')->getToken('authenticate')->getValue()
            : null;
   
	    return $this->render('accueil/accueil.html.twig', array('csrf_token' => $csrfToken));
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
			 
			 //lui donner un role user
			$user->addRole("ROLE_USER");   

    		$user->setPlainpassword($mdp);
    		

    		// enregistrement en base
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

          	$request->getSession()->getFlashBag()->add('success', '<h3>Félicitation</h3><p>Un mail contenant votre mot de passe vient de vous être envoyé !<p>');

    		return $this->redirectToRoute('homepage');
    	}



        return $this->render('accueil/inscription.html.twig', array('form' => $form->createView(),));
    }


}
