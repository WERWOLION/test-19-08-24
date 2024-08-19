<?php
namespace App\Service\Filter;

use Symfony\Component\HttpFoundation\Request;

class RequestFilterDto
{
    public int $page = 1;
    public int $perPage = 12;
    public string $order = 'id';
    public string $sort = 'desc';
    public string $search = '';

    public function toMetaArray(array $addict) : array
    {
      $baseArray = [
        'page' => $this->page,
        'perpage' => $this->perPage,
        'sort' => $this->sort,
        'order' => $this->order,
        'search' => $this->search,
      ];
      return array_merge($baseArray, $addict);
    }

    static public function createFromRequest(Request $request)
    {
        $obj = new self();
        $obj->page = intval($request->get('page')) ? intval($request->get('page')) : 1;
        $obj->perPage = intval($request->get('perpage')) ? intval($request->get('perpage')) : 12;
        $obj->sort = $request->get('sort') && $request->get('sort') === 'asc' ? 'asc' : 'desc';
        $obj->order = $request->get('order', 'id');
        $obj->search = $request->get('search') ? trim($request->get('search')) : '';
        return $obj;
    }
}
