<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
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
        $em = $this->getDoctrine()->getManager();
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
   


        //galerie aleatoire
       $listeUser = $em->getRepository('AppBundle:User')->findAll(); 

       $listeUserAvecPhoto = [];

       foreach ($listeUser as $user) {
           if ($user->getPhotos()->isEmpty() == false){
                $listeUserAvecPhoto[] = $user;
           }
       }

       $test = $em->getRepository('AppBundle:Photo')->findUserAvecPhotoPubliee();
       // var_dump($test);
       shuffle($test);
       $photosaleatoires = $em->getRepository('AppBundle:Photo')->findPhotoPublieeDuRand($test[0]['id']);
       $useraleatoire = $em->getRepository('AppBundle:User')->find($test[0]['id']);
       // var_dump($useraleatoire);

       // exit;


       // dump($listeUserAvecPhoto);
        

       //  $test[] = array_rand($listeUserAvecPhoto);
       //  var_dump($test);
       //  exit;
	    return $this->render('accueil/accueil.html.twig', array('csrf_token' => $csrfToken, 'listeUser' => $listeUser, 'photosaleatoires' => $photosaleatoires,  'useraleatoire' => $useraleatoire));
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
        $flagPhoto = 0;

        $user = $this->getUser();

        $idUser = $user->getId();

        // $photos = $em->getRepository('AppBundle:Photo')->findBy(array('user' => $user));
        // var_dump($photos); exit;
        if ($page < 1) {
            throw $this->createNotFoundException("La page ".$page." n'existe pas.");
        }

        $nbPerPage = 12;

        $photos = $em->getRepository('AppBundle:Photo')->findPhotoByUser($page, $nbPerPage, $idUser);
        //compter nombre photo publier et faire un tableau des chiffre de celle déjà publier +1
        $nbPhotosPubliees = $em->getRepository('AppBundle:Photo')->findPhotoPubliee($idUser);
        $nbP = $nbPhotosPubliees[1] + 1;
        $nbPages = ceil(count($photos)/ $nbPerPage);

        if ($page > $nbPages){
            // throw $this->createNotFoundException("La page".$page." n'existe pas.");
            $nbPages = 1;

        }

        //***************** ajout photo ********************//
        $photo = new Photo;

        $form = $this->get('form.factory')->create(PhotoType::class, $photo);  

        // var_dump(   $form->getErrors()  ) ; exit;

        if ($request->isMethod('POST')){ 
            if ( $form->handleRequest($request)->isValid()) {

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

        unset($photo);
        unset($form);

          $request->getSession()->getFlashBag()->add('notice', 'Photo bien ajoutée.');

          return $this->redirectToRoute('magalerie', array('page' => 1)); 

        // } else if ( !$form->handleRequest($request)->isValid()) {
        //
            }
            $request->getSession()->getFlashBag()->add('error', 'Fichier non valide ! Veuillez vérifier que la taille de la photo ne dépasse pas 5Mo et que le format est bien Jpeg ou png.');

            // unset($photo);
            // unset($form);
            // $photo = new Photo;
            // $form = $this->get('form.factory')->create(PhotoType::class, $photo);  
            $flagPhoto = 1;
            // return $this->render('profil/galerie.html.twig', array('form' => $form->createView(), 'photos' => $photos , 'nbPages' => $nbPages, 'page' => $page, 'nbP' => $nbP, 'flagPhoto' => 1));
        }

        return $this->render('profil/galerie.html.twig', array('form' => $form->createView(), 'photos' => $photos , 'nbPages' => $nbPages, 'page' => $page, 'nbP' => $nbP, 'flagPhoto' => $flagPhoto));
    }


    /**
    * @Route("/publication/photo", name="publication_photo")
    */
    public function publicationAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $user = $this->getUser();

        $idUser = $user->getId();

        $nbPhotosPubliees = $em->getRepository('AppBundle:Photo')->findPhotoPubliee($idUser);

        $idphoto = $request->request->get('idphoto'); 
        $positionphoto = $request->request->get('positionphoto');
            // var_dump( $idphoto );
            // var_dump( $positionphoto );

        
        //on récupére notre photo
        $photo = $em->getRepository('AppBundle:Photo')->findOneBy(array('id' => $idphoto));
        // var_dump( $photo );
        //on compare sa position existante et la nouvelle souhaité pour traiter les cas
        $positionExistante = $photo->getPosition();
            if ($positionExistante == 0){
                if ($positionphoto  > $nbPhotosPubliees[1] ){
                    // var_dump('nouvelle publication');
                    $photo->setPosition($positionphoto);

                } else {
                    $photosABouger = $em->getRepository('AppBundle:Photo')->findModifPosition($positionphoto, $idUser);
                    // var_dump($photosABouger);
                    foreach ($photosABouger as $p) {
                        $entiteP = $em->getRepository('AppBundle:Photo')->find($p['id']);
                        $newPosition = $p['position'] + 1;
                        $entiteP->setPosition($newPosition);
                        $em->persist($entiteP);
                        $photo->setPosition($positionphoto);              
                    }

                }
            } else if($positionExistante != 0 && $positionphoto == 0){
                if ($positionExistante  == $nbPhotosPubliees[1] ){
                    // var_dump('derniere publication');
                    $photo->setPosition($positionphoto);
                } else {
                    $selectposition = $positionExistante + 1;
                    $photosABouger = $em->getRepository('AppBundle:Photo')->findModifPosition($selectposition, $idUser);
                    // var_dump($photosABouger);
                    foreach ($photosABouger as $p) {
                        $entiteP = $em->getRepository('AppBundle:Photo')->find($p['id']);
                        $newPosition = $p['position'] - 1;
                        $entiteP->setPosition($newPosition);
                        $em->persist($entiteP);
                        $photo->setPosition($positionphoto);
                    }
                }
            } else if ($positionExistante != 0 && $positionphoto != 0){
                if ($positionExistante  < $positionphoto ){
                     $photosABouger = $em->getRepository('AppBundle:Photo')->findModifDoublePosition($positionphoto, $positionExistante, $idUser);
                    foreach ($photosABouger as $p) {
                        $entiteP = $em->getRepository('AppBundle:Photo')->find($p['id']);
                        $newPosition = $p['position'] - 1;
                        $entiteP->setPosition($newPosition);
                        $em->persist($entiteP);
                        $photo->setPosition($positionphoto);
                    }
                } else if ($positionExistante  > $positionphoto ){
                    $poshaute = $positionExistante  - 1;
                    $posbasse = $positionphoto  - 1;
                    $photosABouger = $em->getRepository('AppBundle:Photo')->findModifDoublePosition($poshaute, $posbasse, $idUser);
                    // var_dump($photosABouger);
                    foreach ($photosABouger as $p) {
                        $entiteP = $em->getRepository('AppBundle:Photo')->find($p['id']);
                        $newPosition = $p['position'] + 1;
                        $entiteP->setPosition($newPosition);
                        $em->persist($entiteP);
                        $photo->setPosition($positionphoto);
                    }    
                }
            }
            // exist;

            //cas changement de position entre 2
        
        $em->persist($photo);
        $em->flush();


        return New JsonResponse('ok');
    }

    /**
    * @Route("/suppression/photo/{id}", name="supression_photo", requirements={"id" = "\d*"})
    */
    public function suppresionAction($id, Request $request)
    {
            $em = $this->getDoctrine()->getManager();
            $user = $this->getUser();

            $idUser = $user->getId();

            $nbPhotosPubliees = $em->getRepository('AppBundle:Photo')->findPhotoPubliee($idUser);

            $photo = $em->getRepository('AppBundle:Photo')->find($id);
            $positionExistante = $photo->getPosition();
                if ($positionExistante == 0){
                    $em->remove($photo);
                } else if($positionExistante != 0){
                if ($positionExistante  == $nbPhotosPubliees[1] ){
                    $em->remove($photo);
                } else {
                    $selectposition = $positionExistante + 1;
                    $photosABouger = $em->getRepository('AppBundle:Photo')->findModifPosition($selectposition, $idUser);
                    // var_dump($photosABouger);
                    foreach ($photosABouger as $p) {
                        $entiteP = $em->getRepository('AppBundle:Photo')->find($p['id']);
                        $newPosition = $p['position'] - 1;
                        $entiteP->setPosition($newPosition);
                        $em->persist($entiteP);
                    }
                    $em->remove($photo);
                }
            }
                $em->flush();

            return $this->redirectToRoute('magalerie', array('page' => 1));   
    }


    /**
     * @Route("/galeriepublique/{username}", name="galerie_publique", requirements={"username" = "\w*"})
     */
    public function galeriePubliqueAction($username, Request $request)
    {

        $em = $this->getDoctrine()->getManager();

        

        $user = $this->getUser();

        // $idUser = $user->getId();

        $listeUser = $em->getRepository('AppBundle:User')->findAll();

        $usergalerie = $em->getRepository('AppBundle:User')->findOneBy(array('username' => $username));

        $photospubliques = $em->getRepository('AppBundle:Photo')->findPhotoPublieeDuRand($usergalerie->getId());


        return $this->render('profil/galerie_public.html.twig', array('photospubliques' => $photospubliques, 'username' => $username, 'listeUser' => $listeUser /* , 'nbPages' => $nbPages, 'page' => $page, 'nbP' => $nbP */));

    }

    /**
    * @Route("/administration", name="admin")
    */
    public function administrationAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        $listeUser = $em->getRepository('AppBundle:User')->findAll();

        return $this->render('admin/administration.html.twig' , array('listeUser' => $listeUser /* , 'nbPages' => $nbPages, 'page' => $page, 'nbP' => $nbP */));
    }

    /**
    * @Route("/supprimer/utilisateur/{id}", name="supprimer_user", requirements={"id" = "\d*"})
    */
    public function supprimerUtilisateurAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();

        $user = $em->getRepository('AppBundle:User')->find($id);

        
        $em->remove($user);
        $em->flush();

        return $this->redirectToRoute('admin');   
    }

    /**
    * @Route("/reactiver/utilisateur/{id}", name="reactiver_user", requirements={"id" = "\d*"})
    */
    public function reactiverUtilisateurAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();

        $user = $em->getRepository('AppBundle:User')->find($id);

        $user->setEnabled(1);

        $em->persist($user);

        $em->flush();

        return $this->redirectToRoute('admin');   
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
