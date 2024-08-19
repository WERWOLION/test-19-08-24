<?php
namespace App\Controller\Api;

use App\Service\WebAppraisalService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * @Route("/api/v1/webappraisal")
 */
class WebAppraisalApi extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private WebAppraisalService $webAppraisalService
    ) {
    }

    /**
     * @Route("/check_information", name="api_webappraisal_check_info", methods={"POST"})
     */
   public function checkInfo(Request $request)
   {
        $query = $request->toArray()['query'];

        return $this->json($this->webAppraisalService->checkInformation($query), 200);
   }

   /**
     * @Route("/calculate", name="api_webappraisal_calculate", methods={"POST"})
     */
   public function calculate(Request $request) 
   {
     try {
          return $this->json(['success' => true, 'data' => $this->webAppraisalService->calculate($request->toArray())], 200);
     } catch (\Exception $e) {
          return $this->json(['success' => false, 'message' => 'Невозможно установить стоимость объекта'], 200);
     }
   }
}