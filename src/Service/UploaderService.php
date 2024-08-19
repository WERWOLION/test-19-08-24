<?php
namespace App\Service;

use App\Entity\User;
use App\Entity\Offer;
use App\Entity\Attachment;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Constraints\File;
use Vich\UploaderBundle\Templating\Helper\UploaderHelper;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class UploaderService{

    private $contraints;

    private $em;
    private $validator;
    private $offer;
    private $helper;


    public function __construct(EntityManagerInterface $em, ValidatorInterface $validator, UploaderHelper $helper) {
        $this->em = $em;
        $this->validator = $validator;
        $this->helper = $helper;
        $this->contraints = new File([
            'maxSize' => '15M',
            'mimeTypes' => [
                'application/pdf',
                'application/x-pdf',
                'image/*',
                'application/xml',
                'text/xml',
                'application/sig',
                'application/pgp-signature',
                'application/octet-stream',
            ]
        ]);
    }

    public function upload($file, User $user, ?string $description = null, ?Offer $offer = null)
    {
        $errors = $this->validator->validate($file, $this->contraints);
        if(count($errors)){
            throw new \Error("Файл не прошел проверку. Он больше 15мб, либо не изображение/PDF");
            return $errors;
        }
        $attach = new Attachment();

        if($description && $description !== 'undefined'){
            $attach->setDescription($description);
        }

        $attach->setUser($user);
        $attach->setFile($file);
        $attach->setFoldername("other");
        if( $offer ){
            $attach->setFoldername("offer_" . $offer->getId());
            $attach->setOffer($offer);
            $this->em->persist($attach);
        $this->em->flush();
        }
        return $attach;
    }

    public function getUppyOutput(Attachment $attach)
    {
        return [
            'url' => $this->helper->asset($attach, 'file'),
            'id' => $attach->getId(),
        ];
    }
}
