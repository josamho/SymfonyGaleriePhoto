<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Security;
use AppBundle\Entity\User;
use AppBundle\Entity\Photo;
use AppBundle\Form\InscriptionType;
use AppBundle\Form\PhotoType;

class DefaultController extends Controller
{
	
    /**
     * @Route("/", name="homepage")
     */
    public function indexAction(Request $request)
    {
        
        $session = $request->getSession();


        if ($this->getUser() != NULL)
        {
            if ($session->has('tentative')){
                $session->remove('tentative');
            }         
        } else {
            if ($session->has('tentative')){
                if ($session->get('tentative') == '0'){
                    $session->remove('tentative');
                }
            }     
        }

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

    /**
     * @Route("/magalerie/{page}", name="magalerie", requirements={"page" = "\d*"})
     */
    public function maGalerieAction($page, Request $request)
    {

        $em = $this->getDoctrine()->getManager();


        $user = $this->getUser();
        $idUser = $user->getId();

        // $photos = $em->getRepository('AppBundle:Photo')->findBy(array('user' => $user));
        // var_dump($photos); exit;
        if ($page < 1) {
            throw $this->createNotFoundException("La page ".$page." n'existe pas.");
        }

        $nbPerPage = 12;

        $photos = $em->getRepository('AppBundle:Photo')->findPhotoByUser($page, $nbPerPage, $idUser);

        $nbPages = ceil(count($photos)/ $nbPerPage);

        if ($page > $nbPages){
            throw $this->createNotFoundException("La page".$page." n'existe pas.");
        }

        //***************** ajout photo ********************//
        $photo = new Photo;

        $form = $this->get('form.factory')->create(PhotoType::class, $photo);  

        if ($request->isMethod('POST') && $form->handleRequest($request)->isValid()) {

          // $photo->upload();

          $em = $this->getDoctrine()->getManager();
          $photo->setPosition(0);
          $date = new \DateTime("now");
          $photo->setDatepublication($date);

          $user = $this->getUser();

          $em->persist($photo);
          $photo->setUser($user);
          $user->addPhoto($photo);
          
          $em->persist($user);
          $em->flush();

          $request->getSession()->getFlashBag()->add('notice', 'Photo bien ajoutée.');
        } else if ( !$form->handleRequest($request)->isValid()) {
            // $request->getSession()->getFlashBag()->add('error', 'Fichier non valide ! Veuillez vérifier que la taille de la photo ne dépasse pas 5Mo et que le format est bien Jpeg ou png.');
        }

        return $this->render('profil/galerie.html.twig', array('form' => $form->createView(), 'photos' => $photos , 'nbPages' => $nbPages, 'page' => $page));
    }


    /**
     * @Route("/login", name="login")
     */
    public function loginAction(Request $request)
    {
        /** @var $session Session */
        $session = $request->getSession();
        $sessionbis = $this->get('session');
        $email = $session->get('_security.last_username');

        $em = $this->getDoctrine()->getManager();
        $user = $em->getRepository('AppBundle:User')->findOneBy(array('email' => $email));
        if ( $user != NULL){
            $autorisation = $user->isEnabled();
            if ($autorisation == 0){
                $request->getSession()->getFlashBag()->add('error', "<h3>Vous ne pouvez plus vous connectez, l'admin a été contacté<p>");

            } else { 
            // $session->set('truc', 'truc');

            // var_dump($session->get('_security.last_username'));
            // var_dump($session->all());
            // var_dump($request);

            if ($sessionbis->has('tentative')){
                var_dump('y a session');
                $tentative = $sessionbis->get('tentative');
            } else {
                $tentative = '3';
                $sessionbis->set('tentative', $tentative);
            }

            $authErrorKey = Security::AUTHENTICATION_ERROR;
            $lastUsernameKey = Security::LAST_USERNAME;

            // get the error if any (works with forward and redirect -- see below)
            if ($request->attributes->has($authErrorKey)) {
                $error = $request->attributes->get($authErrorKey);

                $tentative = $tentative -1;
                if ($tentative == 0){
                    $request->getSession()->getFlashBag()->add('error', "<h3>Vous ne pouvez plus vous connectez, l'admin a été contacté<p>");
                    //bloquer le compte utilisateur
                    $em = $this->getDoctrine()->getManager();
                    $user = $em->getRepository('AppBundle:User')->findOneBy(array('email' => $email));
                    if ( $user != NULL){
                        $user->setEnabled(0);
                        $em->persist($user);
                        $em->flush();
                    }  
                } else {
                $request->getSession()->getFlashBag()->add('error', '<h3>Attention</h3><p>plus que '.$tentative.'tentative(s) de connexion!<p>');
                $sessionbis->set('tentative', $tentative);
                
                }

            } elseif (null !== $session && $session->has($authErrorKey)) {
                $error = $session->get($authErrorKey);
                $session->remove($authErrorKey);

                $tentative = $tentative -1;
                if ($tentative == 0){
                    $request->getSession()->getFlashBag()->add('error', "<h3>Vous ne pouvez plus vous connectez, l'admin a été contacté<p>");
                    //bloquer le compte utilisateur
                    $em = $this->getDoctrine()->getManager();
                    $user = $em->getRepository('AppBundle:User')->findOneBy(array('email' => $email));
                    if ( $user != NULL){
                        $user->setEnabled(0);
                        $em->persist($user);
                        $em->flush();
                    }            


                } else {
                $request->getSession()->getFlashBag()->add('error', '<h3>Attention</h3><p>plus que '.$tentative.' tentative(s) de connexion!<p>');
                $sessionbis->set('tentative', $tentative);
                
                }


            } else {
                $error = null;
                $sessionbis->remove('tentative');
                // $request->getSession()->getFlashBag()->add('error', '<h3>Attention</h3><p>plus que'.$tentative.'!<p>');
            }

            if (!$error instanceof AuthenticationException) {
                $error = null; // The value does not come from the security component.
                
        }

        // last username entered by the user
        $lastUsername = (null === $session) ? '' : $session->get($lastUsernameKey);

        $csrfToken = $this->has('security.csrf.token_manager')
            ? $this->get('security.csrf.token_manager')->getToken('authenticate')->getValue()
            : null;


        // var_dump('ok'); exit;
           }

           
        } else {
            $request->getSession()->getFlashBag()->add('error', "<h3>Veuillez vérifier l'email, l'utilisateur n'a pas été reconnu !<p>");
        }
        //Redirection
        return $this->redirectToRoute('homepage');
        /*return $this->renderLogin(array(
            'last_username' => $lastUsername,
            'error' => $error,
            'csrf_token' => $csrfToken,
        ));*/
    }

}
