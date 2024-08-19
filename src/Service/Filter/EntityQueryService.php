<?php

namespace App\Service\Filter;

use Doctrine\ORM\QueryBuilder;
use App\Service\Filter\RequestFilterDto;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\ORM\Mapping\Driver\AttributeReader;

class EntityQueryService
{
    public function __construct()
    {
    }

    public function filter(QueryBuilder $qb, RequestFilterDto $dto, string $entityName)
    {
        if (!class_exists($entityName)) throw new \Error('Класс: ' . $entityName . ' не существует');


        if (str_contains($dto->order, '__')) {
            $orderRelationsChunks = explode('__', $dto->order);
            if (!in_array($orderRelationsChunks[0], $qb->getAllAliases())) {
                throw new \Error('Не найдена связанная сущность: ' . $dto->order);
            }
            $orderFieldName = implode('.', $orderRelationsChunks);
        } else {
            if (!property_exists($entityName, $dto->order)) {
                throw new \Error('Поля: ' . $dto->order . ' нет в сущности: ' . $entityName);
            }
            $orderFieldName = $qb->getAllAliases()[0] . '.' . $dto->order;
        }

        if ($dto->search) {
            $qb = $this->getEntitySearchQuery($entityName, $qb, $dto);
        }

        $qb = $qb->orderBy($orderFieldName, $dto->sort);

        $entityQuery = $qb->getQuery();
        $paginator = new Paginator($entityQuery);
        $totalItems = count($paginator);
        $pagesCount = ($dto->perPage < 0) ? 1 : ceil($totalItems / $dto->perPage);
        $result = $paginator
            ->getQuery()
            ->setFirstResult(($dto->perPage < 0) ? 0 : $dto->perPage * ($dto->page - 1))
            ->setMaxResults(($dto->perPage < 0) ? $totalItems : $dto->perPage);
        return [
            'meta' => $dto->toMetaArray([
                'total' => $totalItems,
                'pagesCount' => $pagesCount,
            ]),
            'result' => $result->getResult(),
        ];
    }

    public function getPropertyType(string $entityName, string $propertyName)
    {
        $annotationReader = new AnnotationReader();
        $refClass = new \ReflectionClass($entityName);
        $annotations = $annotationReader->getPropertyAnnotations($refClass->getProperty($propertyName));
        if (count($annotations) > 0) {
            foreach ($annotations as $annotation) {
                if (
                    $annotation instanceof \Doctrine\ORM\Mapping\Column
                    && property_exists($annotation, 'type')
                ) {
                    return $annotation->type;
                }
            }
        }
        return null;
    }

    public function getPropertyTypeAttribute(string $entityName, string $propertyName)
    {
        $reader = new AttributeReader();
        $refClass = new \ReflectionClass($entityName);
        $attributes = $reader->getPropertyAnnotations($refClass->getProperty($propertyName));
        foreach ($attributes as $annotation) {
            if (
                $annotation instanceof \Doctrine\ORM\Mapping\Column
                && property_exists($annotation, 'type')
            ) {
                return $annotation->type;
            }
        }
        return null;
    }

    public function getEntitySearchQuery(string $entityName, QueryBuilder $qb, RequestFilterDto $dto) : QueryBuilder
    {
        $entity = new $entityName;
        if(!method_exists($entity, 'getSearchFields')){
            throw new \Error('Сущность не имеет метода getSearchFields');
        };
        $conditions = [];
        foreach ($entity::getSearchFields() as $fieldName) {
            if (str_contains($fieldName, '__')) {
                $searchRelationsChunks = explode('__', $fieldName);
                if (!in_array($searchRelationsChunks[0], $qb->getAllAliases())) {
                    throw new \Error('Не найдена связанная сущность: ' . $fieldName);
                }
                $searchRelationsFieldName = implode('.', $searchRelationsChunks);
                $conditions[] = $qb->expr()->like($searchRelationsFieldName, "'%" . $dto->search . "%'");
            } else {
                if(in_array($this->getPropertyTypeAttribute($entityName, $fieldName), ['string', 'text'])){
                    $conditions[] = $qb->expr()->like('e.' . $fieldName, "'%" . $dto->search . "%'");
                } else {
                    $conditions[] = $qb->expr()->eq('e.' . $fieldName , ':search');
                    $qb->setParameter('search', $dto->search);
                }
            }
        }
        $orX = $qb->expr()->orX()->addMultiple($conditions);
        return $qb->andWhere($orX);
    }
}
