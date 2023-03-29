<?php

declare(strict_types=1);

namespace PBaszak\MessengerDoctrineDTOBundle\Handler;

use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use PBaszak\MessengerDoctrineDTOBundle\Contract\GetObjects;
use PBaszak\MessengerDoctrineDTOBundle\Mapper\Persister\SqlExpressionVisitor;
use PBaszak\MessengerDoctrineDTOBundle\Mapper\Query\GetDTODQL;
use PBaszak\MessengerDoctrineDTOBundle\Utils\GetTargetEntity;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

#[AsMessageHandler()]
class GetObjectsHandler
{
    use HandleTrait;
    use GetTargetEntity;

    public function __construct(
        private EntityManagerInterface $_em,
        private DenormalizerInterface $denormalizer,
        MessageBusInterface $cachedMessageBus,
    ) {
        $this->messageBus = $cachedMessageBus;
    }

    /** @return mixed[] */
    public function __invoke(GetObjects $message): array
    {
        $outputClass = $message->instanceOf;

        [$dql, $mapper] = $this->handle(new GetDTODQL($outputClass));
        $dql = $this->applyCriteriaToDQL($dql, $message->criteria);

        $query = $this->_em->createQuery($dql);
        $this->applyCriteriaToQuery($query, $message->criteria);
        $output = $query->execute(null, Query::HYDRATE_ARRAY);
        $function = eval($mapper);

        if ($message->arrayHydration) {
            return array_map(fn (array $item) => $function($item), $output);
        }

        return array_map(
            fn (array $item) => $this->denormalizer->denormalize(
                $function($item),
                $outputClass
            ),
            $output
        );
    }

    private function normalizeFieldName(string $fieldName, string $dql): string
    {
        $pattern = '/(\w+\.'.preg_quote($fieldName, '/').')/';
        preg_match($pattern, $dql, $matches);

        if (isset($matches[1])) {
            return $matches[1];
        }

        return $fieldName;
    }

    private function applyCriteriaToDQL(string $dql, ?Criteria $criteria): string
    {
        if (null === $criteria) {
            return $dql;
        }

        $dql = $this->applyWhereToDQL($dql, $criteria);
        $dql = $this->applyOrderByToDQL($dql, $criteria);

        return $dql;
    }

    private function applyCriteriaToQuery(Query $query, ?Criteria $criteria): void
    {
        if (null === $criteria) {
            return;
        }

        $this->applyLimitToDQL($query, $criteria);
        $this->applyOffsetToDQL($query, $criteria);
    }

    private function applyWhereToDQL(string $dql, Criteria $criteria): string
    {
        $expression = $criteria->getWhereExpression();
        $where = $expression?->visit(new SqlExpressionVisitor($dql));
        if (null !== $where) {
            $dql .= ' WHERE '.$where;
        }

        return $dql;
    }

    private function applyOrderByToDQL(string $dql, Criteria $criteria): string
    {
        $orderBy = $criteria->getOrderings();
        if (!empty($orderBy)) {
            $dql .= ' ORDER BY '.implode(', ', array_map(
                fn (string $key, string $value) => $this->normalizeFieldName($key, $dql).' '.$value,
                array_keys($orderBy),
                $orderBy
            ));
        }

        return $dql;
    }

    private function applyLimitToDQL(Query $query, Criteria $criteria): void
    {
        if (null === ($limit = $criteria->getMaxResults())) {
            return;
        }

        $query->setMaxResults($limit);
    }

    private function applyOffsetToDQL(Query $query, Criteria $criteria): void
    {
        if (null === ($offset = $criteria->getFirstResult())) {
            return;
        }

        $query->setFirstResult($offset);
    }
}
