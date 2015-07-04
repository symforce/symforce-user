<?php

namespace Symforce\UserBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use Symforce\AdminBundle\Compiler\Annotation as Admin ;

/**
 * @ORM\Entity
 * @ORM\Table(name="sf_fos_message") 
 * Admin\Entity("sf_message", label="Message", class="Symforce\UserBundle\Admin\MessageAdmin" )
 * 
 */
class Message extends \FOS\MessageBundle\Entity\Message
{
    
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\ManyToOne(
     *   targetEntity="MessageThread",
     *   inversedBy="messages"
     * )
     * @var ThreadInterface
     */
    protected $thread;

    /**
     * @ORM\ManyToOne(targetEntity="User")
     * @var ParticipantInterface
     */
    protected $sender;

    /**
     * @ORM\OneToMany(
     *   targetEntity="MessageMetadata",
     *   mappedBy="message",
     *   cascade={"all"}
     * )
     * @var MessageMetadata
     */
    protected $metadata ;
}