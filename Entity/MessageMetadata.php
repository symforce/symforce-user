<?php

namespace Symforce\UserBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use FOS\MessageBundle\Model\ParticipantInterface;
use Symforce\AdminBundle\Compiler\Annotation as Admin ;

/**
 * @ORM\Entity
 * @ORM\Table(name="sf_fos_message_metadata")
 * Admin\Entity("sf_message_metadata")
 * 
 */
class MessageMetadata extends \FOS\MessageBundle\Entity\MessageMetadata
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\ManyToOne(
     *   targetEntity="Message",
     *   inversedBy="metadata"
     * )
     * @var MessageInterface
     */
    protected $message;

    /**
     * @ORM\ManyToOne(targetEntity="User")
     * @var ParticipantInterface
     */
    protected $participant;
}