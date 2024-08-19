<?php


namespace App\Service;

use App\Repository\SitesettingsRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class SiteSettingsFinder
{

    public function __construct(
        private SitesettingsRepository $repository,
        private UrlGeneratorInterface $urlGeneratorInterface,
    ) {
    }

    public function get(string $label = null): string
    {
        $entity = $this->repository->findSitesettingByLabel($label);
        if (!$entity) return "";
        return $entity->getSettings();
    }

    public function getConst($value, $constant, $object)
    {
        $className = get_class($object);
        $ref = new \ReflectionClass($className);
        $needArray = $ref->getConstant($constant);
        if (!is_array($needArray)) return "Error. Is not Array Contant";
        $flipped = array_flip($needArray);
        if (!isset($flipped[$value])) return '';
        return $flipped[$value];
    }

    public function add_query_arg(Request $request, array $arguments)
    {
        $routeName = $request->get('_route');
        $params = $request->query->all();
        $result = array_merge($params, $arguments);
        return $this->urlGeneratorInterface->generate($routeName, $result);
    }

    public function twigPagination(array $metaArray, Request $request, ?array $routeParams = [])
    {
        $links = [];
        $isFirstNeed = true;
        $dotsRange = 6;

        if ($metaArray['pagesCount'] <= 1) return '';
        foreach (range(1, $metaArray['pagesCount']) as $numLink) {
            if ($metaArray['page'] + $dotsRange < $numLink) {
                $links[] = '<li class="page-item"><span class="page-link">...</span></li>';
                $links[] = '<li class="page-item"><a class="page-link" href="' . $this->add_query_arg($request, array_merge(
                    $routeParams,
                    ['page' => $metaArray['pagesCount']],
                )) . '">' . $metaArray['pagesCount'] . '</a></li>';
                break;
            }
            if ($metaArray['page'] - $dotsRange > $numLink) {
                if ($isFirstNeed) {
                    $links[] = '<li class="page-item"><a class="page-link" href="' . $this->add_query_arg($request, array_merge(
                        $routeParams,
                        ['page' => 1],
                    )) . '">1</a></li>';
                    $links[] = '<li class="page-item"><span class="page-link">...</span></li>';
                }
                $isFirstNeed = false;
                continue;
            }
            if ($metaArray['page'] == $numLink) {
                $links[] = '<li class="page-item active"><span class="page-link">' . $numLink . '</span></li>';
            } else {
                $links[] = '<li class="page-item"><a class="page-link" href="' . $this->add_query_arg($request, array_merge(
                    $routeParams,
                    ['page' => $numLink],
                )) . '">' . $numLink . '</a></li>';
            }
        }
        return implode('', $links);
    }
}
