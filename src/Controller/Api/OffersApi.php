<?php
namespace App\Controller\Api;

use App\Entity\Calculated;
use App\Service\UploaderService;
use App\Repository\AttachmentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Liip\ImagineBundle\Imagine\Cache\CacheManager;
use Vich\UploaderBundle\Templating\Helper\UploaderHelper;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * @Route("/api/v1/offers")
 */
class OffersApi extends AbstractController
{
    private $uploadService;
    private $helper;
    private $em;
    private $attachRep;
    private $liip;
    private $jsonConst = ['json_encode_options'=> JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_HEX_QUOT ];

    public function __construct(
        UploaderHelper $helper,
        UploaderService $uploaderService,
        EntityManagerInterface $em,
        AttachmentRepository $attachmentRepository,
        CacheManager $liip
    ) {
        $this->helper = $helper;
        $this->em = $em;
        $this->uploadService = $uploaderService;
        $this->attachRep = $attachmentRepository;
        $this->liip = $liip;
    }

    /**
     * @Route("/newcalc/{id}", name="api_delete_newcalc", methods={"DELETE"})
     * Удаляет банк из несхораненную подзаявку
     */
   public function deleteNewSubOffer(Calculated $calculated)
   {
        $offer = $calculated->getOffer();
        if(!$this->isGranted('edit', $offer)){
            throw $this->createAccessDeniedException('У вас нет права на удаление заявки');
        }
        if($offer->getStatus() >= 20 && $calculated->getStatus() >= 20 ){
            return $this->json([
                "message" => "Заявка уже отправлена",
            ], 403, [], $this->jsonConst);
        }
        if($offer->getCalculateds()->count() < 2){
            return $this->json([
                "message" => "Нельзя удалить единственный банк из заявки",
            ], 403, [], $this->jsonConst);
        }
        $this->em->remove($calculated);
        $this->em->flush();
        return $this->json([
            "message" => "Заявка успешно обновлена",
        ], 200, [], $this->jsonConst);
   }
}