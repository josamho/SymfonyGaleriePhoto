<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;


/**
 * Photo
 *
 * @ORM\Table(name="photo")
 * @ORM\HasLifecycleCallbacks
 * @ORM\Entity(repositoryClass="AppBundle\Repository\PhotoRepository")
 */
class Photo
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
    * @ORM\Column(name="url", type="string", length=255)
    */
    private $url;

    /**
    * @ORM\Column(name="alt", type="string", length=255)
    */
    private $alt;

    /**
    * @var integer
    *
    * @ORM\Column(type="integer", options={"default":0})
    */
    private $position;

    /**
    * @var integer
    *
    * @ORM\Column(type="integer", nullable=true)
    */
    private $taille;

    /**
    * @ORM\Column(type="datetime")
    */
    private $datepublication;

    /**
    * @var UploadedFile
    *
    * @Assert\File(
    *     maxSize = "5M",
    *     mimeTypes = {"image/png", "image/jpeg"},
    *     mimeTypesMessage = "Fichier non valide !"
    * )
    */
    private $file;


    // On ajoute cet attribut pour y stocker le nom du fichier temporairement
    private $tempFilename;

    /**
     * @ORM\ManyToOne(targetEntity="User", inversedBy="photos" ,cascade={"persist", "remove"})
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id")
     */
    private $user;

    /**
    * @ORM\PrePersist()
    * @ORM\PreUpdate()
    */
    public function preUpload()
    {
        // Si jamais il n'y a pas de fichier (champ facultatif), on ne fait rien
        if (null === $this->file) {
          return;
        }
        
        // Le nom du fichier est son id, on doit juste stocker également son extension
        $this->url = $this->file->guessExtension();
        $this->alt = $this->file->getClientOriginalName();
        //sauvegarde la taille en octet
        $this->taille = $this->file->getClientSize();

    }

    /**
    * @ORM\PostPersist()
    * @ORM\PostUpdate()
    */
     public function upload()
    {
      // Si jamais il n'y a pas de fichier (champ facultatif), on ne fait rien
      if (null === $this->file) {
        return;
      }

      // On récupère le nom original du fichier de l'internaute
      // $name = $this->file->getClientOriginalName();
      $this->setTaille(filesize($this->file));
      $this->file->move(
      $this->getUploadRootDir(), // Le répertoire de destination
      $this->alt); // Le nom du fichier à créer, ici « id.extension »
    }
  


  /**
   * @ORM\PreRemove()
   */
  public function preRemoveUpload()
  {
    // On sauvegarde temporairement le nom du fichier, car il dépend de l'id
    $this->tempFilename = $this->getUploadRootDir().'/'.$this->getPosition().'_'.$this->getDestination()->getLibelle().'.'.$this->url;
  }

  /**
   * @ORM\PostRemove()
   */
  public function removeUpload()
  {
    // En PostRemove, on n'a pas accès à l'id, on utilise notre nom sauvegardé
    if (file_exists($this->tempFilename)) {
      // On supprime le fichier
      unlink($this->tempFilename);
    }
  }

  public function getUploadDir()
  {
    // On retourne le chemin relatif vers l'image pour un navigateur (relatif au répertoire /web donc)
    return 'uploads/img';
  }

  public function getUploadRootDir()
  {
    // On retourne le chemin relatif vers l'image pour notre code PHP
    return __DIR__.'/../../../web/'.$this->getUploadDir(); 
  }

  public function getWebPath()
  {
    // return $this->getUploadDir().'/'.$this->getAlt
    return $this->getUploadDir().'/'.$this->getId().'.'.$this->getUrl();
  }

  /*
  public function removeFile($file)
  {
    $file = $this->getAbsolutePath();
    if ($file) {
      unlink($file);
    }
  }
  */

  /**
   * @return int
   */
  public function getId()
  {
    return $this->id;
  }

  /**
   * @param string $url
   */
  public function setUrl($url)
  {
    $this->url = $url;
  }

  /**
   * @return string
   */
  public function getUrl()
  {
    return $this->url;
  }

  /**
   * @param string $alt
   */
  public function setAlt($alt)
  {
    $this->alt = $alt;
  }

  /**
   * @return string
   */
  public function getAlt()
  {
    return $this->alt;
  }

  /**
   * @return UploadedFile
   */
  public function getFile()
  {
    return $this->file;
  }

  /**
   * @param UploadedFile $file
   */
  // On modifie le setter de File, pour prendre en compte l'upload d'un fichier lorsqu'il en existe déjà un autre

  public function setFile(UploadedFile $file = null)
  {
    $this->file = $file;
    // On vérifie si on avait déjà un fichier pour cette entité
    if (null !== $this->url) {
      // On sauvegarde l'extension du fichier pour le supprimer plus tard
      $this->tempFilename = $this->url;
      // On réinitialise les valeurs des attributs url et alt
      $this->url = null;
      $this->alt = null;
    }
  }

    /**
     * Set position
     *
     * @param integer $position
     *
     * @return PhotoGroupe
     */
    public function setPosition($position)
    {
        $this->position = $position;

        return $this;
    }

    /**
     * Get position
     *
     * @return integer
     */
    public function getPosition()
    {
        return $this->position;
    }


    /**
     * Set taille
     *
     * @param integer $taille
     *
     * @return Photo
     */
    public function setTaille($taille)
    {
        $this->taille = $taille;

        return $this;
    }

    /**
     * Get taille
     *
     * @return integer
     */
    public function getTaille()
    {
        return $this->taille;
    }

    /**
     * Set datepublication
     *
     * @param \DateTime $datepublication
     *
     * @return Photo
     */
    public function setDatepublication($datepublication)
    {
        $this->datepublication = $datepublication;

        return $this;
    }

    /**
     * Get datepublication
     *
     * @return \DateTime
     */
    public function getDatepublication()
    {
        return $this->datepublication;
    }

    /**
     * Set user
     *
     * @param \AppBundle\Entity\User $user
     *
     * @return Photo
     */
    public function setUser(\AppBundle\Entity\User $user = null)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Get user
     *
     * @return \AppBundle\Entity\User
     */
    public function getUser()
    {
        return $this->user;
    }
}
